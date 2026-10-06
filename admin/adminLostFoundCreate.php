<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';

$created_by = $_SESSION['admin']['id'];

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// HANDLE POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed");
    }

    $location = trim($_POST['reported_location']);
    $category = trim($_POST['category']);
    $status   = trim($_POST['status']);
    $email    = trim($_POST['email'] ?? '');

    // IMAGE
    $imageName = $_FILES['image']['name'] ?? '';
    $imageTmp  = $_FILES['image']['tmp_name'] ?? '';

    $finalImage = 'default.jpg';

    if (!empty($imageName)) {
        $clean = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", basename($imageName));
        $finalImage = time() . '_' . $clean;

        move_uploaded_file($imageTmp, "../uploads/" . $finalImage);
    }

    // FIELDS
    $cash_amount = $_POST['cash_amount'] ?? NULL;

    $gadget_type = $_POST['gadget_type'] ?? NULL;
    $gadget_brand = $_POST['gadget_brand'] ?? NULL;
    $gadget_color = $_POST['gadget_color'] ?? NULL;
    $gadget_features = $_POST['gadget_features'] ?? NULL;

    $document_type = $_POST['document_type'] ?? NULL;
    $document_name = $_POST['document_name'] ?? NULL;

    $other_description = $_POST['other_description'] ?? NULL;

    // INSERT
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
            other_description,
             created_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
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
        $created_by
    );

    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    $hasMatch = false;

    // MATCH SYSTEM
    if ($status === 'Lost') {
        $matchStmt = $conn->prepare("SELECT * FROM lost_found WHERE status = 'Found' AND category = ? AND is_claimed = 0 AND is_resolved = 0 AND ABS(DATEDIFF(created_at, NOW())) <= 7");
    } else {
        $matchStmt = $conn->prepare("SELECT * FROM lost_found WHERE status = 'Lost' AND category = ? AND is_claimed = 0 AND is_resolved = 0 AND ABS(DATEDIFF(created_at, NOW())) <= 7");
    }
    $matchStmt->bind_param("s", $category);
    $matchStmt->execute();
    $matches = $matchStmt->get_result();

    while ($match = $matches->fetch_assoc()) {

        $matched = false;

        if ($category === 'Cash') {
            if ($cash_amount == $match['cash_amount']) $matched = true;
        }
        elseif ($category === 'Gadget') {
            if (
                strtolower($gadget_brand) == strtolower($match['gadget_brand']) &&
                strtolower($gadget_color) == strtolower($match['gadget_color'])
            ) $matched = true;
        }
        elseif ($category === 'Document') {
            if (strtolower($document_name) == strtolower($match['document_name'])) $matched = true;
        }
        elseif ($category === 'Other') {
            if (strtolower($other_description) == strtolower($match['other_description'])) $matched = true;
        }

        if ($matched) {
            $hasMatch = true;

            $lostId = ($status === 'Lost') ? $newId : $match['id'];
            $foundId = ($status === 'Found') ? $newId : $match['id'];

            $check = $conn->prepare("SELECT id FROM lost_found_matches WHERE lost_id = ? AND found_id = ?");
            $check->bind_param("ii", $lostId, $foundId);
            $check->execute();

            if ($check->get_result()->num_rows === 0) {
                $insertMatch = $conn->prepare("INSERT INTO lost_found_matches (lost_id, found_id) VALUES (?, ?)");
                $insertMatch->bind_param("ii", $lostId, $foundId);
                $insertMatch->execute();

                // Email Send
                $receiverEmail = '';

                if ($status === 'Found') {
                    $receiverEmail = $match['email'];
                } else {
                    $receiverEmail = $email;
                }

                if (!empty($receiverEmail)) {

                    $emailData = http_build_query([
                        'receiver_email' => $receiverEmail,
                        'category' => $category,
                        'reported_location' => $match['reported_location']
                    ]);

                    $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/adminLostFoundCreateEmail.php');

                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $emailData);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    curl_exec($ch);
                    curl_close($ch);
                }


            }
        }
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // header("Location: " . ($hasMatch ? "adminLostFoundMatches.php" : "adminLostFoundList.php"));

    if ($hasMatch) {
        header("Location: adminLostFoundMatches.php");
    } else {
        header("Location: adminLostFoundList.php");
    }

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

<style>
html { 
    scroll-behavior: smooth; 
}

#possibleMatchesCard {
    scroll-margin-top: 20px; 
}
</style>
</head>
<body class="bg-light">

<div class="container my-5">

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
        $startDate = date('F d', strtotime('-7 days'));
        ?>

        <small class="text-muted">
            Only reports within the last <b>7 days</b> are included
            in the matching process. For example, if today is
            <b><?= $currentDate ?></b>, the system will only check
            reports dated <b><?= $startDate ?> to <?= $currentDate ?></b>.
        </small>
    </div>

    <div class="row g-4">

        <!-- LEFT SIDE -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-primary text-white rounded-top-4">
                    <h4 class="mb-0">Create Lost & Found</h4>
                </div>

                <div class="card-body">

                    <form method="POST" enctype="multipart/form-data">

                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <!-- STATUS -->
                        <div class="border rounded-3 p-3 mb-3 ">

                            <label class="form-label fw-bold">
                                Item Status
                            </label>
                            <select name="status" class="form-select" onchange="showLocation(this); loadMatches();" required>
                                <option value="" disabled selected hidden>Select Status</option>
                                <option value="Lost">Lost</option>
                                <option value="Found">Found</option>
                            </select>

                            <div id="locationContainer" class="mt-3"></div>
                        </div>

                        <!-- CATEGORY -->
                        <div class="mb-3">
                            <label class="fw-bold">Category</label>
                            <select name="category" class="form-select" onchange="showCategory(this); loadMatches();" required>
                                <option value="" disabled selected>Select Category</option>
                                <option value="Cash">Cash</option>
                                <option value="Gadget">Gadget</option>
                                <option value="Document">Document</option>
                                <option value="Other">Other</option>
                            </select>

                            <div id="dynamicFields" class="mt-3"></div>
                        </div>

                        <!-- IMAGE -->
                        <div class="mb-3">
                            <label class="fw-bold">Upload Image</label>
                            <input type="file" name="image" class="form-control">
                        </div>

                        <button class="btn btn-primary w-100">Create</button>

                    </form>

                </div>
            </div>

        </div>

        <!-- RIGHT SIDE -->
                <!-- RIGHT SIDE -->
            <div class="col-lg-6">

                <div id="possibleMatchesCard" class="card shadow-sm border-0 rounded-4 h-100">

                    <div class="card-header bg-success text-white rounded-top-4">

                        <h4 class="mb-0">
                            Possible Matches
                        </h4>

                    </div>

                    <div class="card-body">

                        <div id="matchResults">

                            <div class="text-center text-muted py-5">

                                <h5>
                                    No matches yet
                                </h5>

                                <p>
                                    Start filling up the form to see possible matches.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

    </div>
</div>

<!-- LOCATION -->
<script>
function showLocation(select) {
    let html = '';

    if (select.value === 'Lost') {
        html = `
            <input type="text" name="reported_location" class="form-control mb-3" placeholder="Last seen location" required>
            <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
        `;
    } else {
        html = `
            <input type="text" name="reported_location" class="form-control mb-3" placeholder="Found location" required>
        `;
    }

    document.getElementById('locationContainer').innerHTML = html;
}
</script>

<!-- CATEGORY -->
<script>
function showCategory(select) {

    let html = '';

    if (select.value === 'Cash') {
        html = `<input type="number" name="cash_amount" class="form-control mb-3" placeholder="Amount" required>`;
    }

    else if (select.value === 'Gadget') {
        html = `
            <select name="gadget_type" class="form-select mb-3" onchange="showGadgetFields(this)" required>
                <option value="" disabled selected>Select Gadget Type</option>
                <option value="Cell Phone">Cell Phone</option>
                <option value="Laptop">Laptop</option>
                <option value="Tablet">Tablet</option>
                <option value="Smart watches">Smart watches</option>
                <option value="Audio Gadgets">Audio Gadgets</option>
            </select>

            <div id="gadgetFields"></div>
        `;
    }

    else if (select.value === 'Document') {
        html = `
            <!-- <input type="text" name="document_name" class="form-control mb-3" placeholder="Name" required> -->
            <input type="text" name="document_type" class="form-control mb-3" placeholder="Document Type (ID, Passport, Cert.)" required>
                <input type="text" name="document_name" class="form-control mb-3" placeholder="Name on Document" required>
        `;
    }

    else if (select.value === 'Other') {
        html = `<input type="text" name="other_description" class="form-control mb-3" placeholder="Description" required>`;
    }

    document.getElementById('dynamicFields').innerHTML = html;
}
</script>

<!-- GADGET -->
<script>
function showGadgetFields(select) {
    let html = '';

    if (select.value !== '') {
        html = `
            <input type="text" name="gadget_brand" class="form-control mb-3" placeholder="Brand / Model (Samsung, Apple, Asus)" required>
            <input type="text" name="gadget_color" class="form-control mb-3" placeholder="Color" required>
            <textarea name="gadget_features" class="form-control mb-3" placeholder="Features" required></textarea>
        `;
    }

    document.getElementById('gadgetFields').innerHTML = html;
}
</script>

<!-- MATCHES -->
<script>
async function loadMatches() {

    const status = document.querySelector('[name="status"]').value;
    const category = document.querySelector('[name="category"]').value;

    if (!status || !category) return;

    const formData = new FormData();
    formData.append('status', status);
    formData.append('category', category);

    const res = await fetch('checkMatches.php', {
        method: 'POST',
        body: formData
    });

    const data = await res.json();

    let html = '';

    if (data.length === 0) {
        html = `<div class="alert alert-secondary">No matches found</div>`;
    }

    data.forEach(item => {

        let details = '';

        if (item.category === 'Cash') {
            details = `Amount: ₱${item.cash_amount}`; //💰
        } else if (item.category === 'Gadget') {
            details = `
            Type: ${item.gadget_type}<br>
            Brand/Model: ${item.gadget_brand} | ${item.gadget_color}`; //📱
        } else if (item.category === 'Document') {
            details = `📄 ${item.document_name}`;
        } else {
            details = `Description: ${item.other_description}`; //📝
        }

        html += `
            <div class="card mb-2 border-success">
                <div class="card-body">
                    <b>${item.category}</b><br>
                    ${details}<br>
                    <small>${item.reported_location}</small>
                </div>
            </div>
        `;
    });

    document.getElementById('matchResults').innerHTML = html;

    if (data.length > 0) {
        document.getElementById('possibleMatchesCard')
        .scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
</script>

</body>
</html>