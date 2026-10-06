<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
    $email = trim($_POST['email'] ?? '');

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

    // INSERT ITEM
    $stmt = $conn->prepare(" INSERT INTO lost_found(reported_location, category, status, email, image, extra_details) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $location, $category, $status, $email, $finalImage, $extraDetailsJson);
    $stmt->execute();

    // GET NEW ITEM ID
    $newId = $conn->insert_id;

    $hasMatch = false;

    // MATCHING SYSTEM
    if ($status === 'Lost') {
        $matchStmt = $conn->prepare(" SELECT * FROM lost_found  WHERE status = 'Found' AND category = ? AND is_claimed = 0 AND ABS(DATEDIFF(created_at, NOW())) <= 7 ");
        $matchStmt->bind_param("s", $category);
    } 
    else {
        $matchStmt = $conn->prepare(" SELECT * FROM lost_found WHERE status = 'Lost' AND category = ? AND is_claimed = 0 AND ABS(DATEDIFF(created_at, NOW())) <= 7");
        $matchStmt->bind_param("s", $category);
    }
    $matchStmt->execute();
    $matches = $matchStmt->get_result();
    while ($match = $matches->fetch_assoc()) {
        $matched = false;
        $matchDetails = json_decode($match['extra_details'], true);

        // CASH MATCH
        if ($category === 'Cash') {
            if (
                ($extraDetails['amount'] ?? '') == ($matchDetails['amount'] ?? '')
            ) {
                $matched = true;
            }
        }

        // GADGET MATCH
        elseif ($category === 'Gadget') {
            if (
                strtolower($extraDetails['brand'] ?? '') === strtolower($matchDetails['brand'] ?? '')
                &&
                strtolower($extraDetails['color'] ?? '') === strtolower($matchDetails['color'] ?? '')
            ) {
                $matched = true;
            }
        }

        // DOCUMENT MATCH
        elseif ($category === 'Document') {
            if (
                strtolower($extraDetails['name'] ?? '') === strtolower($matchDetails['name'] ?? '')
            ) {
                $matched = true;
            }
        }

        // OTHER MATCH
        elseif ($category === 'Other') {
            if (
                strtolower($extraDetails['description'] ?? '') === strtolower($matchDetails['description'] ?? '')
            ) {
                $matched = true;
            }
        }

        // SAVE MATCH
        if ($matched) {

            $hasMatch = true;

            if ($status === 'Lost') {
                $lostId = $newId;
                $foundId = $match['id'];
            }
            else {
                $lostId = $match['id'];
                $foundId = $newId;
            }

            // CHECK DUPLICATE MATCH
            $checkMatch = $conn->prepare("SELECT id FROM lost_found_matches WHERE lost_id = ?  AND found_id = ?");
            $checkMatch->bind_param("ii", $lostId, $foundId);
            $checkMatch->execute();
            $existingMatch = $checkMatch->get_result();

            if ($existingMatch->num_rows === 0) {
                $saveMatch = $conn->prepare("INSERT INTO lost_found_matches(lost_id, found_id) VALUES (?, ?)");
                $saveMatch->bind_param("ii", $lostId, $foundId);
                $saveMatch->execute();

                // AUTO EMAIL NOTIFICATION
                $receiverEmail = '';

                // kapag gumawa ng FOUND
                // email ng LOST owner ang rereceive
                if ($status === 'Found') {
                    $receiverEmail = $match['email'];
                }

                // kapag gumawa ng LOST
                // sariling email ng bagong LOST ang rereceive
                else {
                    $receiverEmail = $email;
                }

                // send only if may email
                if (!empty($receiverEmail)) {

                    try {

                        $mail = new PHPMailer(true);

                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;

                        $mail->Username   = 'princepls17@gmail.com';
                        $mail->Password   = 'vtrb qvbo ddzj osxe';

                        $mail->SMTPSecure = 'tls';
                        $mail->Port       = 587;

                        $mail->setFrom(
                            'princepls17@gmail.com',
                            'Amamiya Jodai Lost & Found'
                        );

                        $mail->addAddress($receiverEmail);

                        $mail->isHTML(true);

                        $mail->Subject = 'Possible Match Found For Your Lost Item';

                        $mail->Body = "
                            <h2>Lost & Found Notification</h2>

                            <p>
                                Good day,
                            </p>

                            <p>
                                A possible match has been found for your item.
                            </p>

                            <p>
                                Please visit the school Lost & Found office
                                for verification and claiming.
                            </p>

                            <hr>

                            <p>
                                <b>Category:</b> {$category}
                            </p>

                            <p>
                                <b>Reported Location:</b>
                                {$match['reported_location']}
                            </p>

                            <br>

                            <p>
                                Thank you.
                            </p>
                        ";

                        $mail->send();

                    } catch (Exception $e) {

                        // optional:
                        // pwede mo i-log error dito

                    }
                }
            }
        }

    }

    $stmt->close();

    // NEW CSRF TOKEN
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // header("Location: adminLostFoundList.php");
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

<style>
    html {
    scroll-behavior: smooth;
}
#possibleMatchesCard {
    scroll-margin-top: 20px;
}
</style>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        $startDate = date('F d', strtotime('-6 days'));
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
                    <h4 class="mb-0">
                        Create Lost & Found
                    </h4>
                </div>

                <div class="card-body">

                    <form method="POST" enctype="multipart/form-data">

                        <input 
                            type="hidden" 
                            name="csrf_token" 
                            value="<?= $_SESSION['csrf_token'] ?>"
                        >

                        <!-- STATUS -->
                        <div class="border rounded-3 p-3 mb-3 bg-light">

                            <label class="form-label fw-bold">
                                Item Status
                            </label>

                            <select 
                                name="status" 
                                class="form-select mb-3"
                                onchange="showLocation(this); loadMatches();"
                                required
                            >
                                <option value="" disabled selected hidden>
                                    Select Status
                                </option>

                                <option value="Lost">Lost</option>

                                <option value="Found">Found</option>
                            </select>

                            <!-- Dynamic Location -->
                            <div id="locationContainer"></div>

                        </div>

                        <!-- CATEGORY -->
                        <div class="border rounded-3 p-3 mb-3 bg-light">

                            <label class="form-label fw-bold">
                                Item Category
                            </label>

                            <select 
                                name="category" 
                                class="form-select"
                                onchange="showCategory(this); loadMatches();"
                                required
                            >
                                <option value="" disabled selected hidden>
                                    Select Category
                                </option>

                                <option value="Cash">Cash</option>

                                <option value="Gadget">Gadget</option>

                                <option value="Document">Document</option>

                                <option value="Other">Other</option>
                            </select>

                            <!-- Dynamic Fields -->
                            <div id="dynamicFields" class="mt-3"></div>

                        </div>

                        <!-- IMAGE -->
                        <div class="mb-3">

                            <label class="form-label fw-bold">
                                Upload Image
                            </label>

                            <input 
                                type="file" 
                                name="image" 
                                class="form-control"
                            >

                        </div>

                        <!-- BUTTONS -->
                        <div class="d-flex gap-2">

                            <button class="btn btn-primary w-100">
                                Create
                            </button>

                            <a 
                                href="adminLostFoundList.php" 
                                class="btn btn-secondary w-100"
                            >
                                Back
                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

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
        const container = document.getElementById('locationContainer');

        if (select.value === 'Lost') {
            html = `
                <input type="text" name="reported_location" class="form-control mb-3" placeholder="Example: Room 304" required >
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
                <input type="text" name="document_type" class="form-control mb-2" placeholder="Document Type (ID, Document, Cert.)" required>
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




<!-- FIlter -->
<script>

async function loadMatches() {

    const status = document.querySelector('[name="status"]').value;
    const category = document.querySelector('[name="category"]').value;

    if (!status || !category) return;

    const formData = new FormData();

    formData.append('status', status);
    formData.append('category', category);

    const response = await fetch('checkMatches.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    let html = '';

    if (data.length === 0) {
        html = `
            <div class="alert alert-secondary">
                No possible matches found.
            </div>
        `;
    }

    data.forEach(item => {
        let details = '';
        if (item.category === 'Cash') {
            details = `
                <div class="mb-2">
                    💰 Amount: ₱${item.details.amount}
                </div>
            `;
        }

        else if (item.category === 'Gadget') {
            details = `
                <div class="mb-2">
                    📱 ${item.details.brand}<br>
                    🎨 ${item.details.color}<br>
                    🔧 ${item.details.features}
                </div>
            `;
        }

        else if (item.category === 'Document') {
            details = `
                <div class="mb-2">
                    📄 ${item.details.document_type}<br>
                    👤 ${item.details.name}
                </div>
            `;
        }

        else {
            details = `
                <div class="mb-2">
                    📝 ${item.details.description}
                </div>
            `;
        }

        html += `
            <div class="card mb-3 border-success shadow-sm">
                <div class="card-body">
                    <h5>
                        ${item.category}
                    </h5>

                    ${details}

                    <p>
                        <!--<b>ID:</b> ${item.id}<br>-->
                        <b>Location:</b> ${item.reported_location}
                    </p>

                    <hr>

                    <small class="text-muted">
                        ${item.created_at}
                    </small>
                </div>
            </div>
        `;
    });

    // document.getElementById('matchResults').innerHTML = html;
    const matchCard = document.getElementById('possibleMatchesCard');

    matchResults.innerHTML = html;

    if (data.length > 0) {
        matchCard.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

document.addEventListener('change', function(e) {
    if (
        e.target.name === 'status' ||
        e.target.name === 'category'
    ) {
        loadMatches();
    }
});

</script>
</body>
</html>