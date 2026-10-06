<?php
session_start();

// Session timeout duration (15 minutes = 900 seconds)
$timeoutDuration = 900;

// Redirect if not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/adminLogin.php");
    exit();
}

// Session hijacking protection 
// if ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
//     session_unset();
//     session_destroy();
//     die("Session Validdate Faild");
// }

// (
//     !isset($_SESSION['user_ip'], $_SESSION['user_agent']) ||
//     $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR'] ||
//     $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
// )

// session timeout check
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeoutDuration) {
    
    // Optional: Log auto  logout to adminLogs (if you have  this table and  log_id session)
    if (isset($_SESSION['log_id'])) {
        include '../config/db.php';

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
    header("Location: ../admin/adminLogin.php?timeout=1");
    exit();
}

// update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();
?>  