<?php
include '../middleware/staffMiddleware.php';
include '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$released_by = $_SESSION['staff']['id'];

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM lost_found WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) die("Not found");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['claimed_by']);
    $student_id = trim($_POST['claimed_id']);
    $email = trim($_POST['claimed_email']);
    $contact = trim($_POST['claimed_contact']);
    $department = trim($_POST['claimed_department']);
    $address = trim($_POST['claimed_address']);

    $stmt = $conn->prepare("UPDATE lost_found SET claimed_by=?, claimed_id=?, claimed_email=?, claimed_contact=?, claimed_department=?, claimed_address=?, claimed_date=NOW(), is_claimed=1, released_by = ? WHERE id=?");
    $stmt->bind_param("ssssssii", $name, $student_id, $email, $contact, $department, $address, $released_by, $id);
    $stmt->execute();


     /*
    |--------------------------------------------------------------------------
    | Delete related matches after successful claim
    |--------------------------------------------------------------------------
    | Kapag na-claim na ang item, hindi na ito dapat lumabas
    | sa matching records kaya buburahin natin lahat ng
    | connected records sa lost_found_matches table.
    */

    $deleteMatches = $conn->prepare("DELETE FROM lost_found_matches WHERE lost_id = ? OR found_id = ?");
    $deleteMatches->bind_param("ii", $id, $id);
    $deleteMatches->execute();
    $deleteMatches->close();

    // =========================
    // SEND EMAIL
    // =========================



    header("Location: staffLostFoundList.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Claim Item</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

</head>
<body>


    <div class="container mt-5">
        <h2>Claim Item</h2>
        <h4><?= htmlspecialchars($item['category']) ?></h4>
        <h5>Item Details</h5>

        <?php if ($item['category'] === 'Cash'): ?>

            <p>
                Amount:
                ₱<?= number_format($item['cash_amount'], 2) ?>
            </p>

        <?php elseif ($item['category'] === 'Gadget'): ?>

            <p>
                Type:
                <?= htmlspecialchars($item['gadget_type']) ?>
            </p>

            <p>
                Brand:
                <?= htmlspecialchars($item['gadget_brand']) ?>
            </p>

            <p>
                Color:
                <?= htmlspecialchars($item['gadget_color']) ?>
            </p>

            <p>
                Features:
                <?= htmlspecialchars($item['gadget_features']) ?>
            </p>

        <?php elseif ($item['category'] === 'Document'): ?>

            <p>
                Document Type:
                <?= htmlspecialchars($item['document_type']) ?>
            </p>

            <p>
                Name:
                <?= htmlspecialchars($item['document_name']) ?>
            </p>

        <?php elseif ($item['category'] === 'Other'): ?>

            <p>
                Description:
                <?= htmlspecialchars($item['other_description']) ?>
            </p>

        <?php endif; ?>

        <img src="../uploads/<?= htmlspecialchars($item['image']) ?>"
            width="120"
            class="mb-3">

        <form method="POST" id="claimForm">
            <input type="text" name="claimed_by" class="form-control mb-2" placeholder="Full Name" required>
            <input type="text" name="claimed_id" class="form-control mb-2" placeholder="ID" required>
            <input type="email" name="claimed_email" class="form-control mb-2" placeholder="Email Address" required>
            <input type="text" name="claimed_contact" class="form-control mb-2" placeholder="Contact Number" required>

            <select name="claimed_department" class="form-control mb-2" required>
                <option value="">Select Department</option>
                <option value="IT">IT</option>
                <option value="CRIM">CRIM</option>
            </select>

            <input type="text" name="claimed_address" class="form-control mb-3" placeholder="Address" required>

            <button class="btn btn-success">
                Confirm Claim
            </button>

            <a href="staffLostFoundList.php"
            class="btn btn-secondary">
                Back
            </a>
        </form>
    </div>




</body>
</html>