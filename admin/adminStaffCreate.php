<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token mismatch.");
    }

    $firstName = htmlspecialchars(trim($_POST['firstName']));
    $lastName  = htmlspecialchars(trim($_POST['lastName']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $contact   = trim($_POST['contact']);
    // $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $password = password_hash('staff_123', PASSWORD_DEFAULT);

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

    $mustChangePassword = 0;
    $stmt = $conn->prepare("INSERT INTO staff (firstName, lastName , email, contact, image, password, mustChangePassword) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $firstName,  $lastName , $email, $contact, $finalImageName, $password,  $mustChangePassword);
    $stmt->execute();
    $stmt->close();


    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: adminStaffList.php");
    exit;
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
        <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
    <h2>➕ Create Staff</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="firstName" oninput="formatNameInput(this)" class="form-control" placeholder="First Name" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="lastName" oninput="formatNameInput(this)" class="form-control" placeholder="Last Name" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" name="contact" id="contact" class="form-control" placeholder="Contact Number" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Profile Image</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>


        <button type="submit" class="btn btn-primary">Create</button>
        <a href="adminStaffList.php" class="btn btn-secondary">Back</a>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!--Full name sanitation Letters (A-Z, a-z) Space ( ) Period (.) Apostrophe (') Hyphen (-) -->
<script>
    function formatNameInput(input) {
        let cleaned = input.value.replace(/[^a-zA-Z\s.'-]/g, '');

        cleaned = cleaned.replace(/\b\w/g, function(char) {
            return char.toUpperCase();
        });

        input.value = cleaned;
    }
</script>

<!-- EMAIL LIVE VALIDATION -->
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

        fetch('checkStaff_email.php?email=' + encodeURIComponent(email))
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
    }
);
</script>

</body>
</html>