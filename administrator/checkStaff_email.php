<?php
include '../config/db.php';
header('Content-Type: application/json');

$response = ['exists' => false];

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $currentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT id FROM staff WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $currentId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $response['exists'] = true;
        }

        $stmt->close();
    }
}

echo json_encode($response);
