<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: list.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF validation failed");
}

$id = intval($_POST['id']);

// Step 1: Get the customer's image first
$stmt = $conn->prepare("SELECT image FROM student WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if ($student) {
    $image = $student['image'];

    // Step 2: Delete student from DB
    $stmt = $conn->prepare("DELETE FROM student WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Step 3: Delete image file if it's not default and not used by any other student
    if ($image !== 'default.jpg') {
        $check = $conn->prepare("SELECT COUNT(*) AS count FROM student WHERE image = ?");
        $check->bind_param("s", $image);
        $check->execute();
        $count = $check->get_result()->fetch_assoc()['count'];
        $check->close();

        if ($count == 0 && file_exists("../uploads/" . $image)) {
            unlink("../uploads/" . $image);
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header("Location: list.php");
exit();
?>