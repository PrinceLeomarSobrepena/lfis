<?php
session_start();
include 'db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token mismatch.");
    }

    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    } 

    // ✅ Image handling
    $imageName = $_FILES['image']['name'] ?? '';
    $imageTmp  = $_FILES['image']['tmp_name'] ?? '';
    $finalImageName = 'default.jpg';

    if (!empty($imageName)) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExt   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $fileType = mime_content_type($imageTmp);

        // Get file extension
        $fileExt = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

        if (in_array($fileType, $allowedTypes) && in_array($fileExt, $allowedExt)) {
            $imageNameClean = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", basename($imageName));
            $finalImageName = time() . '_' . $imageNameClean;
            $uploadPath = '../uploads/' . $finalImageName;

            if (!move_uploaded_file($imageTmp, $uploadPath)) {
                $finalImageName = 'default.jpg';
            }
        } else {
            $finalImageName = 'default.jpg';
        }
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO student (username, email, image, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $finalImageName, $hashedPassword);
    $stmt->execute();
    $stmt->close();


    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: list.php");
    exit;
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

    <h2>Add New Student</h2>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" id="email" required><br><br>

        <label>Profile Image:</label><br>
        <input type="file" name="image" accept="image/*"><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit" name="submit">Submit</button>
    </form>

    <a href="../zCRUD/list.php">
    <button>Back</button>
    </a>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- ✅ EMAIL LIVE VALIDATION -->
<script>
const emailInput = document.getElementById('email');

emailInput.addEventListener('input', function () {
    const email = this.value.trim();
    const regex = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;

    this.setCustomValidity("");

    if (email === "") return;

    if (!regex.test(email)) {
        this.setCustomValidity("Please enter a valid email address.");
        return;
    }

    fetch('check_email.php?email=' + encodeURIComponent(email))
        .then(res => res.json())
        .then(data => {
            if (data.exists) {
                this.setCustomValidity("This email is already registered.");
            } else {
                this.setCustomValidity("");
            }
        })
        .catch(() => {
            this.setCustomValidity("Unable to verify email.");
        });
});
</script>
</body>
</html>