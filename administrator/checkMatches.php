<?php
include '../config/db.php';

$status = $_POST['status'];
$category = $_POST['category'];
$excludeId = isset($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;

if ($excludeId > 0) {
    $sql = "SELECT * FROM lost_found WHERE status != ? AND category = ? AND id != ? AND is_claimed = 0 AND is_resolved = 0 AND ABS(DATEDIFF(created_at, NOW())) <= 7";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $status, $category, $excludeId);
} else {
    $sql = "SELECT * FROM lost_found WHERE status != ? AND category = ? AND is_claimed = 0 AND is_resolved = 0 AND ABS(DATEDIFF(created_at, NOW())) <= 7";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $status, $category);
}

$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>