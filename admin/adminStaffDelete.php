<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adminStaffList.php");
    exit();
}

// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF validation failed");
}

$id = intval($_POST['id']);

// Step 1: Get the staff's image first
$stmt = $conn->prepare("SELECT image FROM staff WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

if ($staff) {
    $image = $staff['image'];

    // Step 2: Delete staff from DB
    $stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Step 3: Delete image file if it's not default and not used by any other staff
    if ($image !== 'default.jpg') {
        $check = $conn->prepare("SELECT COUNT(*) AS count FROM staff WHERE image = ?");
        $check->bind_param("s", $image);
        $check->execute();
        $count = $check->get_result()->fetch_assoc()['count'];
        $check->close();

        if ($count == 0 && file_exists("../uploads/" . $image)) {
            unlink("../uploads/" . $image);
        }
    }
}

// Regenerate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Redirect
header("Location: adminStaffList.php");
exit();
?>