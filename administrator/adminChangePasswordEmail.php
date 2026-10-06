<?php
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$receiverEmail = $_POST['receiver_email'] ?? '';
$fullName      = $_POST['full_name'] ?? 'Admin';

if (empty($receiverEmail)) {
    http_response_code(400);
    exit('Missing receiver_email');
}

try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    // NOTE: move these into environment variables / a non-committed
    // config file (e.g. getenv('MAIL_USERNAME')) instead of hardcoding
    // real credentials directly in source code.
    $mail->Username = getenv('MAIL_USERNAME') ?: 'princepls17@gmail.com';
    $mail->Password = getenv('MAIL_APP_PASSWORD') ?: 'vtrb qvbo ddzj osxe';

    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom($mail->Username, 'Admin Security');
    $mail->addAddress($receiverEmail);
    $mail->isHTML(true);
    $mail->Subject = 'Password Changed Successfully';
    $mail->Body = "
        <h2>Password Updated</h2>
        <p>
            Hello
            <b>" . htmlspecialchars($fullName) . "</b>,
        </p>
        <p>Your administrator password was successfully changed.</p>
        <hr>
        <p>If this was not you, please contact the system administrator immediately.</p>
    ";
    $mail->send();

    echo 'sent';
} catch (Exception $e) {
    error_log('PasswordChange mail error: ' . $mail->ErrorInfo);
    http_response_code(500);
    echo 'failed';
}