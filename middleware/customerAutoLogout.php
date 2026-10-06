<?php
session_start();
header('Content-Type: application/json');

$response = ['active' => false];
$timeoutDuration = 900; // 15 minutes

if (isset($_SESSION['customer']) && isset($_SESSION['LAST_ACTIVITY'])) {

    if ((time() - $_SESSION['LAST_ACTIVITY']) > $timeoutDuration) {

        if (isset($_SESSION['log_id'])) {
            include '../config/db.php';

            $log_id = $_SESSION['log_id'];
            $logout_time = date("Y-m-d H:i:s");
            $status = 'offline';

            $stmt = $conn->prepare("UPDATE customerLogs SET logout_time = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $logout_time, $status, $log_id);
            $stmt->execute();
            $stmt->close();
        }

        session_unset();
        session_destroy();
        $response['active'] = false;

    } else {
        $response['active'] = true;
    }
}

echo json_encode($response);
?>
