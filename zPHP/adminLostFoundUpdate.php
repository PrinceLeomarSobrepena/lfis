<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM lost_found WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Record not found.");
}

$row = $result->fetch_assoc();
$stmt->close();

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

    $cash_amount = $_POST['cash_amount'] ?? NULL;

    $gadget_type = $_POST['gadget_type'] ?? NULL;
    $gadget_brand = $_POST['gadget_brand'] ?? NULL;
    $gadget_color = $_POST['gadget_color'] ?? NULL;
    $gadget_features = $_POST['gadget_features'] ?? NULL;

    $document_type = $_POST['document_type'] ?? NULL;
    $document_name = $_POST['document_name'] ?? NULL;

    $other_description = $_POST['other_description'] ?? NULL;

    $finalImage = $row['image'];

    if (!empty($_FILES['image']['name'])) {

        $clean = preg_replace(
            "/[^a-zA-Z0-9\.\-_]/",
            "",
            basename($_FILES['image']['name'])
        );

        $finalImage = time() . '_' . $clean;

        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            "../uploads/" . $finalImage
        );
    }

    $update = $conn->prepare("
        UPDATE lost_found SET
            reported_location = ?,
            category = ?,
            status = ?,
            email = ?,
            image = ?,
            cash_amount = ?,
            gadget_type = ?,
            gadget_brand = ?,
            gadget_color = ?,
            gadget_features = ?,
            document_type = ?,
            document_name = ?,
            other_description = ?
        WHERE id = ?
    ");

    $update->bind_param(
        "sssssdsssssssi",
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
        $other_description,
        $id
    );

    $update->execute();
    $update->close();



    

    // REMOVE OLD MATCHES OF THIS ITEM STARRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRTTTTTTTTTTTTTTTTTTTTTTTTTTTTTT
    $deleteMatch = $conn->prepare("
        DELETE FROM lost_found_matches
        WHERE lost_id = ? OR found_id = ?
    ");
    $deleteMatch->bind_param("ii", $id, $id);
    $deleteMatch->execute();
    $deleteMatch->close();

    $hasMatch = false;

    // FIND OPPOSITE STATUS ITEMS
    if ($status === 'Lost') {

        $matchStmt = $conn->prepare("
            SELECT *
            FROM lost_found
            WHERE status = 'Found'
            AND category = ?
            AND id != ?
            AND is_claimed = 0
            AND is_resolved = 0
            AND ABS(DATEDIFF(created_at, NOW())) <= 7
        ");

    } else {

        $matchStmt = $conn->prepare("
            SELECT *
            FROM lost_found
            WHERE status = 'Lost'
            AND category = ?
            AND id != ?
            AND is_claimed = 0
            AND is_resolved = 0
            AND ABS(DATEDIFF(created_at, NOW())) <= 7
        ");
    }

    $matchStmt->bind_param("si", $category, $id);
    $matchStmt->execute();
    $matches = $matchStmt->get_result();

    while ($match = $matches->fetch_assoc()) {

        $matched = false;

        if ($category === 'Cash') {

            if ($cash_amount == $match['cash_amount']) {
                $matched = true;
            }

        } elseif ($category === 'Gadget') {

            if (
                strtolower(trim($gadget_brand)) === strtolower(trim($match['gadget_brand'])) &&
                strtolower(trim($gadget_color)) === strtolower(trim($match['gadget_color']))
            ) {
                $matched = true;
            }

        } elseif ($category === 'Document') {

            if (
                strtolower(trim($document_name)) === strtolower(trim($match['document_name']))
            ) {
                $matched = true;
            }

        } elseif ($category === 'Other') {

            if (
                strtolower(trim($other_description)) === strtolower(trim($match['other_description']))
            ) {
                $matched = true;
            }
        }

        if ($matched) {

            $hasMatch = true;

            $lostId = ($status === 'Lost') ? $id : $match['id'];
            $foundId = ($status === 'Found') ? $id : $match['id'];

            $check = $conn->prepare("
                SELECT id
                FROM lost_found_matches
                WHERE lost_id = ?
                AND found_id = ?
            ");

            $check->bind_param("ii", $lostId, $foundId);
            $check->execute();

            if ($check->get_result()->num_rows === 0) {

                $insertMatch = $conn->prepare("
                    INSERT INTO lost_found_matches
                    (lost_id, found_id)
                    VALUES (?, ?)
                ");

                $insertMatch->bind_param("ii", $lostId, $foundId);
                $insertMatch->execute();
                $insertMatch->close();
            }

            $check->close();
        }
    }

    $matchStmt->close();
    // REMOVE OLD MATCHES OF THIS ITEM ENDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDD





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
<title>Update Lost & Found</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="container py-5">

    <div class="card shadow-sm border-0 rounded-4">

        <div class="card-header bg-warning">
            <h4 class="mb-0">Update Lost & Found</h4>
        </div>

        <div class="card-body">

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="mb-3">
                    <label class="fw-bold">Status</label>

                    <select name="status" class="form-select" onchange="showLocation(this)" required>
                        <option value="Lost" <?= $row['status'] == 'Lost' ? 'selected' : '' ?>> Lost </option>
                        <option value="Found" <?= $row['status'] == 'Found' ? 'selected' : '' ?>> Found </option>
                    </select>

                    <div id="locationContainer" class="mt-3"></div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Category</label>

                    <select name="category" class="form-select" onchange="showCategory(this)" required>
                        <option value="Cash" <?= $row['category'] == 'Cash' ? 'selected' : '' ?>> Cash </option>
                        <option value="Gadget" <?= $row['category'] == 'Gadget' ? 'selected' : '' ?>> Gadget </option>
                        <option value="Document" <?= $row['category'] == 'Document' ? 'selected' : '' ?>> Document </option>
                        <option value="Other" <?= $row['category'] == 'Other' ? 'selected' : '' ?>> Other </option>
                    </select>

                    <div id="dynamicFields" class="mt-3"></div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Current Image</label>
                    <br>
                    <img src="../uploads/<?= $row['image'] ?>" width="120" class="img-thumbnail">
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Change Image</label>
                    <input type="file" name="image" class="form-control">
                </div>

                <button class="btn btn-warning w-100"> Update Record </button>
            </form>

        </div>
    </div>
</div>

<script>

const record = <?= json_encode($row) ?>;

function showLocation(select){

    let html = '';

    if(select.value === 'Lost'){

        html = `
            <input type="text" name="reported_location" class="form-control mb-3" placeholder="Last seen location" value="${record.reported_location ?? ''}" required>
            <input type="email" name="email" class="form-control mb-3" placeholder="Email" value="${record.email ?? ''}" required>
        `;
    }
    else{

        html = `
            <input type="text" name="reported_location" class="form-control mb-3" placeholder="Found location" value="${record.reported_location ?? ''}" required>
        `;
    }

    document.getElementById('locationContainer').innerHTML = html;
}

function showCategory(select){

    let html = '';

    if(select.value === 'Cash'){

        html = `
            <input
                type="number"
                name="cash_amount"
                class="form-control"
                placeholder="Amount"
                value="${record.cash_amount ?? ''}"
                required
            >
        `;
    }

    else if(select.value === 'Gadget'){

        html = `
            <select
                name="gadget_type"
                class="form-select mb-3"
                onchange="showGadgetFields(this)"
                required
            >
                <option value="">Select Gadget Type</option>

                <option value="Cell Phone"
                ${record.gadget_type === 'Cell Phone' ? 'selected' : ''}>
                Cell Phone
                </option>

                <option value="Laptop"
                ${record.gadget_type === 'Laptop' ? 'selected' : ''}>
                Laptop
                </option>

                <option value="Tablet"
                ${record.gadget_type === 'Tablet' ? 'selected' : ''}>
                Tablet
                </option>

                <option value="Smart watches"
                ${record.gadget_type === 'Smart watches' ? 'selected' : ''}>
                Smart watches
                </option>

                <option value="Audio Gadgets"
                ${record.gadget_type === 'Audio Gadgets' ? 'selected' : ''}>
                Audio Gadgets
                </option>
            </select>

            <div id="gadgetFields">
                <input
                    type="text"
                    name="gadget_brand"
                    class="form-control mb-3"
                    placeholder="Brand / Model"
                    value="${record.gadget_brand ?? ''}"
                    required
                >

                <input
                    type="text"
                    name="gadget_color"
                    class="form-control mb-3"
                    placeholder="Color"
                    value="${record.gadget_color ?? ''}"
                    required
                >

                <textarea
                    name="gadget_features"
                    class="form-control mb-3"
                    required
                >${record.gadget_features ?? ''}</textarea>
            </div>
        `;
    }

    else if(select.value === 'Document'){

        html = `
            <input
                type="text"
                name="document_type"
                class="form-control mb-3"
                placeholder="Document Type"
                value="${record.document_type ?? ''}"
                required
            >

            <input
                type="text"
                name="document_name"
                class="form-control mb-3"
                placeholder="Name on Document"
                value="${record.document_name ?? ''}"
                required
            >
        `;
    }

    else if(select.value === 'Other'){

        html = `
            <input
                type="text"
                name="other_description"
                class="form-control"
                placeholder="Description"
                value="${record.other_description ?? ''}"
                required
            >
        `;
    }

    document.getElementById('dynamicFields').innerHTML = html;
}

function showGadgetFields(){}

window.onload = function(){

    showLocation(
        document.querySelector('[name="status"]')
    );

    showCategory(
        document.querySelector('[name="category"]')
    );
};

</script>

</body>
</html>