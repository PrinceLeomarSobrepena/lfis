<?php

include '../middleware/staffMiddleware.php';
include '../config/db.php';

$staffId = $_SESSION['staff']['id'];

$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $staffId);
$stmt->execute();

$result = $stmt->get_result();
$staff = $result->fetch_assoc();

$stmt->close();

if (!$staff) { session_destroy();
    header("Location: staffLogin.php");
    exit();
}

$message = "";
$type = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token mismatch.");
    }

    $oldPassword = trim($_POST['old_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $verifyPassword = trim($_POST['verify_password'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | CHECK CURRENT PASSWORD
    |--------------------------------------------------------------------------
    */

    if (!password_verify($oldPassword, $staff['password'])) {
        $message = "Current password is incorrect.";
        $type = "danger";

    } elseif ($newPassword !== $verifyPassword) {
        $message = "New password and Verify password do not match.";
        $type = "danger";

    } elseif (strlen($newPassword) < 6) {
        $message = "Password must be at least 6 characters.";
        $type = "danger";

    } elseif (password_verify($newPassword, $staff['password'])) {
        $message = "New password must be different from your current password.";
        $type = "danger";

    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        /*
        |--------------------------------------------------------------------------
        | UPDATE PASSWORD
        | 0 = First login / needs password change
        | 1 = Password already changed
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("UPDATE staff SET password = ?, mustChangePassword = 1 WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $staffId);
        if ($stmt->execute()) {

            //s
            $login_time = date("Y-m-d H:i:s");
            $status = 'online';

            $stmtLog = $conn->prepare("
                INSERT INTO staffLogs (staffId, login_time, status)
                VALUES (?, ?, ?)
            ");

            if ($stmtLog) {

                $stmtLog->bind_param(
                    "iss",
                    $staff['id'],
                    $login_time,
                    $status
                );

                if ($stmtLog->execute()) {

                    // Save the staffLogs ID
                    $_SESSION['log_id'] = $stmtLog->insert_id;
                }

                $stmtLog->close();
            }


            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $message = "Password changed successfully.";
            $type = "success";

        } else {

            $message = "Failed to update password.";
            $type = "danger";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="mb-3">
                            Change Your Password
                        </h4>

                        <p class="text-muted">
                            This is your first login. Please change your
                            default password before continuing.
                        </p>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= htmlspecialchars($type) ?>">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                            <div class="mb-3">
                                <label class="form-label"> Current Password </label>
                                <input type="password" name="old_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"> New Password </label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"> Verify New Password </label>
                                <input type="password" name="verify_password" class="form-control" minlength="6" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message === "Password changed successfully."): ?>

<script>

Swal.fire({
    icon: 'success',
    title: 'Password Changed',
    text: 'Your password has been changed successfully.',
    confirmButtonText: 'Continue'
}).then(() => {

    window.location.href = 'staffDashboard.php';

});

</script>

<?php endif; ?>

</body>

</html>