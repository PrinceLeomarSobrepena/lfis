<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("CSRF validation failed");
    }

    $location = trim($_POST['reported_location']);
    $category = trim($_POST['category']);
    $status   = trim($_POST['status']);
    $email    = trim($_POST['email'] ?? '');

    $imageName = $_FILES['image']['name'] ?? '';
    $imageTmp  = $_FILES['image']['tmp_name'] ?? '';

    $finalImage = 'default.jpg';

    if (!empty($imageName)) {

        $clean = preg_replace(
            "/[^a-zA-Z0-9\.\-_]/",
            "",
            basename($imageName)
        );

        $finalImage = time() . '_' . $clean;

        move_uploaded_file(
            $imageTmp,
            "../uploads/" . $finalImage
        );
    }

    // CATEGORY FIELDS
    $cash_amount = $_POST['cash_amount'] ?? NULL;

    // GADGET
    $gadget_type = $_POST['gadget_type'] ?? NULL;
    $gadget_brand = $_POST['gadget_brand'] ?? NULL;
    $gadget_color = $_POST['gadget_color'] ?? NULL;
    $gadget_features = $_POST['gadget_features'] ?? NULL;

    // DOCUMENT
    $document_type = $_POST['document_type'] ?? NULL;
    $document_name = $_POST['document_name'] ?? NULL;

    // OTHER
    $other_description = $_POST['other_description'] ?? NULL;

    // INSERT ITEM
    $stmt = $conn->prepare("
        INSERT INTO lost_found (
            reported_location,
            category,
            status,
            email,
            image,

            cash_amount,

            gadget_type,
            gadget_brand,
            gadget_color,
            gadget_features,

            document_type,
            document_name,

            other_description
        )

        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssdsssssss",

        $location,
        $category,
        $status,
        $email,
        $finalImage,

        $cash_amount,

        $gadget_type,
        $gadget_brand,
        $gadget_color,
        $gadget_features,

        $document_type,
        $document_name,

        $other_description
    );

    $stmt->execute();
    $stmt->close();

    // NEW CSRF TOKEN
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));

    header("Location: adminLostFoundList.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Create Lost & Found</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="alert alert-info shadow-sm">
        <h5 class="mb-2">Match Detection Information</h5>
        <p class="mb-1">
            The system automatically detects possible matches
            between lost and found items.
        </p>

        <ul class="mb-2">
            <li><b>Cash</b> → amount comparison</li>
            <li><b>Gadget</b> → brand/model and color</li>
            <li><b>Document</b> → name on document</li>
            <li><b>Other</b> → item description</li>
        </ul>

        <?php
        $currentDate = date('F d');
        $startDate = date('F d', strtotime('-6 days'));
        ?>

        <small class="text-muted">
            Only reports within the last <b>7 days</b> are included
            in the matching process. For example, if today is
            <b><?= $currentDate ?></b>, the system will only check
            reports dated <b><?= $startDate ?> to <?= $currentDate ?></b>.
        </small>
    </div>

    <div class="card shadow p-4">
        <h2 class="mb-4">Create Lost & Found</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- STATUS -->
            <div class="border rounded p-3 mb-3 bg-white">

                <label class="form-label fw-bold">
                    Item Status
                </label>

                <select name="status" class="form-select mb-3" onchange="showLocation(this)" required>
                    <option value="" disabled selected hidden> Select Status </option>
                    <option value="Lost">Lost</option>
                    <option value="Found">Found</option>
                </select>

                <div id="locationContainer"></div>
            </div>

            <!-- CATEGORY -->
            <div class="border rounded p-3 mb-3 bg-white">
                <label class="form-label fw-bold"> Item Category </label>

                <select name="category" class="form-select" onchange="showCategory(this)" required>
                    <option value="" disabled selected hidden> Select Category </option>
                    <option value="Cash">Cash</option>
                    <option value="Gadget">Gadget</option>
                    <option value="Document">Document</option>
                    <option value="Other">Other</option>
                </select>

                <div id="dynamicFields" class="mt-3"></div>
            </div>

            <!-- IMAGE -->
            <input type="file" name="image" class="form-control mb-3">

            <!-- BUTTON -->
            <button class="btn btn-primary"> Create </button>

            <a href="adminLostFoundList.php" class="btn btn-secondary"> Back </a>
        </form>
    </div>
</div>

<!-- LOCATION -->
<script>
function showLocation(select) {
    let html = '';
    const container = document.getElementById('locationContainer');

    if (select.value === 'Lost') {
        html = `
            <input type="text" name="reported_location" class="form-control mb-3" placeholder="Example: Last seen inside room 304" required>
            <input type="email" name="email" class="form-control mb-3" placeholder="Enter Email Address" required>
        `;
    }

    else if (select.value === 'Found') {
        html = `
            <input type="text" name="reported_location" class="form-control mb-3" placeholder="Example: Found in hallway 3rd floor" required>
        `;
    }

    container.innerHTML = html;
}
</script>

<!-- CATEGORY -->
<script>
function showCategory(select) {
    let html = '';
    const container = document.getElementById('dynamicFields');

    // CASH
    if (select.value === 'Cash') {
        html = `
            <input type="number" name="cash_amount" class="form-control mb-3" placeholder="Enter Amount" required>
        `;
    }

    // GADGET
    else if (select.value === 'Gadget') {
        html = `
            <select name="gadget_type" id="gadget_type" class="form-select mb-3" onchange="showGadgetFields(this)" required>
                <option value="" disabled selected hidden> Select Gadget Type </option>
                <option value="Cell Phones"> Cell Phones </option>
                <option value="Laptop"> Laptop </option>
                <option value="Tablet"> Tablet </option>
                <option value="Smart watches"> Smart watches </option>
                <option value="Audio Gadgets"> Audio Gadgets </option>
            </select>

            <div id="gadgetFields"></div>
        `;
    }

    // DOCUMENT
    else if (select.value === 'Document') {
        html = `
            <input type="text" name="document_type" class="form-control mb-2" placeholder="Document Type" required>
            <input type="text" name="document_name" class="form-control mb-3" placeholder="Name on Document" required>
        `;
    }

    // OTHER
    else if (select.value === 'Other') {
        html = `
            <input type="text" name="other_description" class="form-control mb-3" placeholder="Item Description" required>
        `;
    }

    container.innerHTML = html;
}
</script>

<!-- GADGET FIELDS -->
<script>
function showGadgetFields(select) {
    let html = '';
    const container = document.getElementById('gadgetFields');

    if (select.value !== '') {
        html = `
            <input type="text" name="gadget_brand" class="form-control mb-2" placeholder="Brand" required>
            <input type="text" name="gadget_color" class="form-control mb-2" placeholder="Color" required>
            <textarea name="gadget_features" class="form-control mb-3" placeholder="Unique Features" required></textarea>
        `;
    }

    container.innerHTML = html;
}
</script>

</body>
</html>