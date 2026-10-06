<?php
session_start();
include '../config/db.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        exit("invalid csrf token!");
    }

    // Default Credentials
    $firstName = 'Admin';
    $lastName  = 'Kirisaki';
    $email     = 'princepls17@gmail.com';
    $contact   = '09708000529';
    $password  = '123456';

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Invalid email format.");
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        // Insert new admin
        $role = 'admin';
        $stmt = $conn->prepare("INSERT INTO admin (firstName, lastName, email, contact, password, role_as) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $firstName, $lastName, $email, $contact, $hashedPassword, $role);

        if ($stmt->execute()) {
            echo "✅ New admin created successfully.";
        } else {
            echo "❌ Error: " . $stmt->error;
        }
    } else {
        echo "⚠️ Admin already exists.";
    }
}
?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <button type="submit">Create Admin</button>
</form>
