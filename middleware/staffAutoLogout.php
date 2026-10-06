<?php
// para auto logout ang staff pag inactive sya ng 15 minutes sa dashboard
session_start();
header('Content-Type: application/json');

$response = ['active' => false];

// 15 minutes
$timeoutDuration = 900;

if (isset($_SESSION['staff']) && isset($_SESSION['LAST_ACTIVITY'])) {

    if ((time() - $_SESSION['LAST_ACTIVITY']) > $timeoutDuration) {

        // auto logout log
        if (isset($_SESSION['log_id'])) {
            include '../config/db.php';

            $log_id = $_SESSION['log_id'];
            $logout_time = date("Y-m-d H:i:s");
            $status = 'offline';

            $stmt = $conn->prepare("UPDATE staffLogs SET logout_time = ?, status = ? WHERE id = ?");
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