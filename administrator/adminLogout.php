<?php
session_start();
include '../config/db.php';

date_default_timezone_set('Asia/Manila');

if (isset($_SESSION['log_id'])) {
    $log_id = $_SESSION['log_id'];
    $logout_time = date("Y-m-d H:i:s");
    $status = 'offline';

    $stmt = $conn->prepare("UPDATE adminLogs SET logout_time = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $logout_time, $status, $log_id);
    $stmt->execute();
    $stmt->close();
}

session_unset();
session_destroy();
header("Location: adminLogin.php");
exit();
?>
