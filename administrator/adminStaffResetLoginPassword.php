<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adminStaffList.php");
    exit();
}


if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token mismatch.");
}

$staffId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$staffId) {
    die("Invalid staff ID.");
}

/*
|--------------------------------------------------------------------------
| GET STAFF
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT id, firstName, lastName, email FROM staff WHERE id = ?");
$stmt->bind_param("i", $staffId);
$stmt->execute();

$result = $stmt->get_result();
$staff = $result->fetch_assoc();

$stmt->close();

if (!$staff) {
    die("Staff account not found.");
}

$password = password_hash('staff_123', PASSWORD_DEFAULT);

/*
|--------------------------------------------------------------------------
| 0 = NEEDS TO CHANGE PASSWORD
| 1 = PASSWORD ALREADY CHANGED
|--------------------------------------------------------------------------
*/

$mustChangePassword = 0;
$stmt = $conn->prepare("UPDATE staff SET password = ?, mustChangePassword = ? WHERE id = ?");
$stmt->bind_param("sii", $password, $mustChangePassword, $staffId);
if ($stmt->execute()) {

    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        /*
        |--------------------------------------------------------------------------
        | GMAIL ACCOUNT
        |--------------------------------------------------------------------------
        */

        $mail->Username = 'princepls17@gmail.com';
        $mail->Password = 'vtrb qvbo ddzj osxe';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom( 'princepls17@gmail.com', 'Staff Security');

        $mail->addAddress($staff['email']);
        $mail->isHTML(true);
        $mail->Subject = 'Staff Password Reset';
        $fullName = htmlspecialchars($staff['firstName'] . ' ' . $staff['lastName'], ENT_QUOTES, 'UTF-8');

        $mail->Body = "
            <h2>Password Reset</h2>

            <p> Hello <b>{$fullName}</b>, </p>
            <p> Your staff account password has been reset by the administrator. </p>
            <p> Your temporary password is: <strong>staff_123</strong> </p>
            <p> Please log in using this temporary password. You will be required to change it before accessing your staff dashboard. </p>

            <hr>

            <p>  If you did not expect this password reset, please contact the system administrator immediately. </p>
        ";

        $mail->send();

    } catch (Exception $e) {
        error_log( "Staff password reset email error: " . $mail->ErrorInfo);
    }


    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: adminStaffList.php?reset=success");
    exit();

} else {
    $stmt->close();

    header("Location: adminStaffList.php?reset=failed");
    exit();
}