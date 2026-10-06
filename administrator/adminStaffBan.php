<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $staffId = intval($_POST['id']);

    // Toggle banned status
    $stmt = $conn->prepare("UPDATE staff SET banned = NOT banned WHERE id = ?");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $stmt->close();

    // Regenerate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: adminStaffList.php");
    exit;
}
?>
