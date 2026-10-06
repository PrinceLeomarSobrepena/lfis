<?php

require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $receiverEmail = trim($_POST['receiver_email'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $reportedLocation = trim($_POST['reported_location'] ?? '');

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
                'noreply@lfisystem.gt.tc'
            );

            $mail->addAddress($receiverEmail);

            $mail->isHTML(true);

            $mail->Subject = 'Possible Match Found For Your Lost Item';

            $mail->Body = "
                <h2>Lost & Found Notification</h2>

                <p>Good day,</p>

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
                    {$reportedLocation}
                </p>

                <br>

                <p>Thank you.</p>
            ";

            $mail->send();

        } catch (Exception $e) {

            // optional:
            // pwede mo i-log error dito

        }
    }
}
?>