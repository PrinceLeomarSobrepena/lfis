<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    $stmt = $conn->prepare("UPDATE lost_found SET claimed_by=?, claimed_id=?, claimed_email=?, claimed_contact=?, claimed_department=?, claimed_address=?, claimed_date=NOW(), is_claimed=1 WHERE id=?");
    $stmt->bind_param("ssssssi", $name, $student_id, $email, $contact, $department, $address, $id);
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

    if (!empty($email)) {

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
                'Lost & Found System'
            );

            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Item Successfully Claimed';

            $mail->Body = "
                <h2>Claim Confirmation</h2>

                <p>Good day <b>{$name}</b>,</p>

                <p>
                    Your item has been successfully claimed
                    from the Lost & Found Office.
                </p>

                <hr>

                <p><b>Category:</b> {$item['category']}</p>
                <p><b>Claim Date:</b> " . date('F d, Y h:i A') . "</p>

                <br>

                <p>Thank you for using the system.</p>
            ";

            $mail->send();

        } catch (Exception $e) {
            // optional log
        }
    }

    header("Location: adminLostFoundList.php");
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

<style>
    /* Spinner styles */
    .spinner-wrapper{
        background-color: rgba(255,255,255,0.9);
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
    }

    .spinner-border{
        height: 60px;
        width: 60px;
    }
</style>
</head>
<body>
<!-- Spinner -->
<div class="spinner-wrapper" id="spinner">
    <div class="spinner-border text-info" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>


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

            <a href="adminLostFoundList.php"
            class="btn btn-secondary">
                Back
            </a>
        </form>
    </div>


    <script>
const form = document.getElementById('claimForm');
const spinner = document.getElementById('spinner');

form.addEventListener('submit', () => {
    spinner.style.display = 'flex'; // show spinner immediately
});
</script>

</body>
</html>