<?php
session_start();

include '../middleware/adminMiddleware.php';
include '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// check id
if (!isset($_GET['id'])) {
    die("Invalid ID");
}

$id = intval($_GET['id']);

// kunin lost item
$stmt = $conn->prepare("SELECT * FROM lost_found WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Item not found");
}

$row = $result->fetch_assoc();

// check if may email
if (empty($row['email'])) {
    die("No email found for this item.");
}

try {

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;

    // YOUR GMAIL
    $mail->Username   = 'princepls17@gmail.com';

    // APP PASSWORD
    $mail->Password   = 'vtrb qvbo ddzj osxe';

    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // sender
    $mail->setFrom(
        'princepls17@gmail.com',
        'Amamiya Jodai Lost & Found'
    );

    // receiver
    $mail->addAddress($row['email']);

    $mail->isHTML(true);

    $mail->Subject = 'Possible Match Found For Your Lost Item';

    $mail->Body = "
        <h2>Lost & Found Notification</h2>

        <p>
            Good day,
        </p>

        <p>
            Someone has reported an item that may match the item you lost.
        </p>

        <p>
            Please visit the school Lost & Found office for verification and claiming.
        </p>

        <hr>

        <p>
            <b>Reported Location:</b> {$row['reported_location']}
        </p>

        <p>
            <b>Category:</b> {$row['category']}
        </p>

        <br>

        <p>
            Thank you.
        </p>
    ";

    $mail->send();

    echo "
    <script>
        alert('Email sent successfully.');
        window.location.href='adminLostFoundList.php';
    </script>
    ";

} catch (Exception $e) {

    echo "
    <script>
        alert('Email sending failed.');
        window.location.href='adminLostFoundList.php';
    </script>
    ";
}
?>