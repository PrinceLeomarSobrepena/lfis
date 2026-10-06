<?php
session_start();
include 'db.php';

$id = (int) $_GET['id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch student
$stmt = $conn->prepare("SELECT * FROM student WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student){
    die("student not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF validation failed");
    }

    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    // IMAGE HANDLING
    $hasNewImage = !empty($_FILES['image']['name']);
    $image = $student['image']; // default current image

    if ($hasNewImage) {
        $imageName = $_FILES['image']['name'];
        $imageTmp  = $_FILES['image']['tmp_name'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExt   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $fileType = mime_content_type($imageTmp);

        // Get file extension
        $fileExt  = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

        if (in_array($fileType, $allowedTypes) && in_array($fileExt, $allowedExt)) {
            $imageNameClean = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", basename($imageName));
            $newImageName = time() . '_' . $imageNameClean;
            $uploadPath = '../uploads/' . $newImageName;

            if (move_uploaded_file($imageTmp, $uploadPath)) {
                // DELETE OLD IMAGE (kung hindi default at hindi ginagamit ng iba)
                if ($student['image'] !== 'default.jpg') {
                    $check = $conn->prepare("SELECT COUNT(*) AS count FROM student WHERE image = ? AND id != ?");
                    $check->bind_param("si", $student['image'], $id);
                    $check->execute();
                    $count = $check->get_result()->fetch_assoc()['count'];
                    $check->close();

                    if ($count == 0 && file_exists("../uploads/" . $student['image'])) {
                        unlink("../uploads/" . $student['image']);
                    }
                }
                $image = $newImageName;
            }
        }
    }

    // UPDATE
    $stmt = $conn->prepare("UPDATE student SET username=?, email=?, image=? WHERE id=?");
    $stmt->bind_param("sssi", $username, $email, $image, $id);
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
    <title>Edit Student</title>
</head>
<body>

<h2>Edit Student</h2>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <label>Username:</label><br>
    <input type="text" name="username" value="<?= htmlspecialchars($student['username'], ENT_QUOTES, 'UTF-8') ?>" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" id="email" value="<?= htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8') ?>" required><br><br>

    <label>Profile Image:</label><br>
    <input type="file" name="image" accept="image/*"><br><br>

    <?php if (!empty($student['image'])): ?>
        <img src="../uploads/<?= htmlspecialchars($student['image']) ?>" width="100"><br><br>
    <?php endif; ?>

    <button type="submit">Update</button>
</form>

<a href="../zCRUD/list.php">Back</a>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
const emailInput = document.getElementById('email');
const currentUserId = <?= $id ?>; // current customer's ID from PHP

emailInput.addEventListener('input', function () {
    const email = this.value.trim();
    const regex = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;

    this.setCustomValidity("");

    if (email === "") return;

    if (!regex.test(email)) {
        this.setCustomValidity("Please enter a valid email address.");
        return;
    }

    fetch('check_email.php?email=' + encodeURIComponent(email)+ '&id=' + currentUserId)
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