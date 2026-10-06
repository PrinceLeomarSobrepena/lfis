<?php
include '../middleware/staffMiddleware.php';
include '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$staffId = $_SESSION['staff']['id'];

$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $staffId);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

$message = "";
$type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = trim($_POST['old_password']);
    $newPassword = trim($_POST['new_password']);
    $verifyPassword = trim($_POST['verify_password']);

    // Check old password
    if (!password_verify($oldPassword, $staff['password'])) {
        $message = "Old password is incorrect.";
        $type = "danger";

    } elseif ($newPassword !== $verifyPassword) {
        $message = "New password and Verify password do not match.";
        $type = "danger";

    } elseif (strlen($newPassword) < 6) {
        $message = "Password must be at least 6 characters.";
        $type = "danger";

    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE staff SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $staffId);
        if ($stmt->execute()) {

            // =====================
            // SEND EMAIL
            // =====================
            try {

                $mail = new PHPMailer(true);

                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;

                $mail->Username = 'princepls17@gmail.com';
                $mail->Password = 'vtrb qvbo ddzj osxe';

                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom( 'princepls17@gmail.com', 'Staff Security');
                $mail->addAddress($staff['email']);
                $mail->isHTML(true);
                $mail->Subject = 'Password Changed Successfully';
                $mail->Body = "
                    <h2>Password Updated</h2>

                    <p>
                        Hello
                        <b>{$staff['firstName']} {$staff['lastName']}</b>,
                    </p>
                    <p> Your staff account password was successfully changed.</p>

                    <hr>
                    <p> If this was not you, please contact the system  administrator immediately. </p>
                ";
                $mail->send();
            } catch (Exception $e) {
                // optional log
            }
            $message = "Password changed successfully.";
            $type = "success";
        } else {
            $message = "Failed to update password.";
            $type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container py-5">
    <h4>Change Password</h4>
    <!-- <div class="card shadow">
        <div class="card-body"> -->

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $type ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label"> Old Password </label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"> New Password </label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"> Verify Password </label>
                    <input type="password" name="verify_password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary"> Change Password </button>
                <a href="staffDashboard.php" class="btn btn-secondary"> Back </a>
            </form>
        <!-- </div>
    </div> -->
</div>

</body>
</html>