<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

if (!$staff) {
    die("Staff not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token mismatch.");
    }

    $firstName = htmlspecialchars(trim($_POST['firstName']));
    $lastName  = htmlspecialchars(trim($_POST['lastName']));
    $email     = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $contact   = trim($_POST['contact']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // Check if email already exists except current staff
    // $check = $conn->prepare("SELECT id FROM staff WHERE email = ? AND id != ?");
    // $check->bind_param("si", $email, $id);
    // $check->execute();
    // $check->store_result();

    // if ($check->num_rows > 0) {
    //     die("Email already exists.");
    // }

    // $check->close();

    $finalImageName = $staff['image'];

    // Image upload
    if (!empty($_FILES['image']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExt   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $imageTmp  = $_FILES['image']['tmp_name'];
        $imageName = $_FILES['image']['name'];

        $fileType = mime_content_type($imageTmp);
        $fileExt  = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

        if (in_array($fileType, $allowedTypes) && in_array($fileExt, $allowedExt)) {
            $cleanName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", basename($imageName));
            $finalImageName = time() . '_' . $cleanName;
            $uploadPath = '../uploads/' . $finalImageName;

            if (move_uploaded_file($imageTmp, $uploadPath)) {

                // Delete old image
                if ( $staff['image'] !== 'default.jpg' && file_exists('../uploads/' . $staff['image'])) {
                    unlink('../uploads/' . $staff['image']);
                }

            } else {
                $finalImageName = $staff['image'];
            }
        }
    }

    $stmt = $conn->prepare(" UPDATE staff SET firstName = ?,  lastName = ?,  email = ?,  contact = ?,  image = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $firstName, $lastName, $email, $contact, $finalImageName, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: adminStaffList.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Update Staff</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <div class="container py-5">
        <h2>✏️ Update Staff</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <!-- <div class="mb-3 text-center">
                <img src="../uploads/<?= htmlspecialchars($staff['image']) ?>" width="120" height="120" style="object-fit:cover;border-radius:50%;">
            </div> -->

            <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" name="firstName" class="form-control" oninput="formatNameInput(this)" value="<?= htmlspecialchars($staff['firstName']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" name="lastName" class="form-control" oninput="formatNameInput(this)" value="<?= htmlspecialchars($staff['lastName']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($staff['contact']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>" required>
            </div>

            <!-- <div class="mb-3">
                <label class="form-label">New Profile Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div> -->
            <div class="mb-3">
                <label class="form-label">New Profile Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <?php if (!empty($staff['image'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($staff['image']) ?>" width="50" height="50" class="mt-2">
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">
                Update
            </button>

            <a href="adminStaffList.php" class="btn btn-secondary">
                Back
            </a>
        </form>
    </div>

<script>
function formatNameInput(input) {

    let cleaned = input.value.replace(/[^a-zA-Z\s.'-]/g, '');

    cleaned = cleaned.replace(/\b\w/g, function(char) {
        return char.toUpperCase();
    });

    input.value = cleaned;
}

</script>

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

    fetch('checkStaff_email.php?email=' + encodeURIComponent(email)+ '&id=' + currentUserId)
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