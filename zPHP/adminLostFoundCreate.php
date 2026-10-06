<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed");
    }

    $location = trim($_POST['reported_location']);
    $category = trim($_POST['category']);
    $status = trim($_POST['status']);
    $email = trim($_POST['email'] ?? ''); //may ?? '' kasi pweding walang email sa database kana defualt null kasi

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

    // DYNAMIC FIELDS
    $extraDetails = [];

    if ($category === 'Cash') {
        $extraDetails['amount'] = $_POST['cash_amount'] ?? '';
    }

    elseif ($category === 'Gadget') {
        $extraDetails['brand'] = $_POST['gadget_brand'] ?? '';
        $extraDetails['color'] = $_POST['gadget_color'] ?? '';
        $extraDetails['features'] = $_POST['gadget_features'] ?? '';
    }

    elseif ($category === 'Document') {
        $extraDetails['document_type'] = $_POST['document_type'] ?? '';
        $extraDetails['name'] = $_POST['document_name'] ?? '';
    }

    elseif ($category === 'Other') {
        $extraDetails['description'] = $_POST['other_description'] ?? '';
    }

    $extraDetailsJson = json_encode($extraDetails);

    // INSERT
    $stmt = $conn->prepare("INSERT INTO lost_found(reported_location, category, status, email, image, extra_details) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $location, $category, $status, $email, $finalImage, $extraDetailsJson);
    $stmt->execute();
    $stmt->close();

    // NEW CSRF TOKEN
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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
    <div class="card shadow p-4">
        <h2 class="mb-4">Create Lost & Found</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- STATUS -->
            <div class="border rounded p-3 mb-3 bg-white">
                <label class="form-label fw-bold"> Item Status</label>
                <select name="status" class="form-select mb-3" onchange="showLocation(this)" required>
                    <option value="" disabled selected hidden> Select Status </option>
                    <option value="Lost"> Lost </option>
                    <option value="Found"> Found </option>
                </select>

                <!-- dynamic fields inside same box -->
                <div id="locationContainer"></div>
            </div>

            <!-- CATEGORY -->
            <div class="border rounded p-3 mb-3 bg-white">
                <label class="form-label fw-bold"> Item Category</label>
                <select name="category" class="form-select" onchange="showCategory(this)" required>
                    <option value="" disabled selected hidden> Select Category </option>
                    <option value="Cash">Cash</option>
                    <option value="Gadget">Gadget</option>
                    <option value="Document">Document</option>
                    <option value="Other">Other</option>
                </select>

                <!-- dynamic fields inside same box -->
                <div id="dynamicFields" class="mt-3"></div>
            </div>

            <!-- IMAGE -->
            <input type="file" name="image" class="form-control mb-3">

            <!-- BUTTONS -->
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

    if (select.value === 'Cash') {

        html = `
            <input type="number" name="cash_amount" class="form-control mb-3" placeholder="Enter Amount" required>
        `;
    }

    else if (select.value === 'Gadget') {

        html = `
            <input type="text" name="gadget_brand" class="form-control mb-2" placeholder="Brand / Model" required>
            <input type="text" name="gadget_color" class="form-control mb-2" placeholder="Color" required>
            <input type="text" name="gadget_features" class="form-control mb-3" placeholder="Unique Features" required>
        `;
    }

    else if (select.value === 'Document') {

        html = `
            <input type="text" name="document_type" class="form-control mb-2" placeholder="Document Type (ID, Passport, Certificate)" required>
            <input type="text" name="document_name" class="form-control mb-3" placeholder="Name on Document" required>
        `;
    }

    else if (select.value === 'Other') {

        html = `
            <input type="text" name="other_description" class="form-control mb-3" placeholder="Item Description" required>
        `;
    }

    container.innerHTML = html;
}
</script>

</body>
</html>