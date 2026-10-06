<?php
session_start();
include '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if (isset($_POST['login'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "invalid_email";
    } else {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if ($row['banned']) {
                $error = "banned";
            } elseif (password_verify($password, $row['password'])) {

                // Generate OTP
                $otp = rand(100000, 999999);

                $_SESSION['temp_admin_id'] = $row['id'];
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_expire'] = time() + 300;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Send OTP via PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'princepls17@gmail.com';
                    $mail->Password = 'vtrb qvbo ddzj osxe';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('princepls17@gmail.com', 'Amamiya Jodai System');
                    $mail->addAddress($row['email']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your OTP Code';
                    $mail->Body = "<h3>Your OTP Code is: <b>$otp</b></h3><p>Expires in 5 minutes.</p>";

                    $mail->send();

                    // OTP sent successfully → redirect to adminOtp.php
                    header("Location: adminOtp.php");
                    exit();

                } catch (Exception $e) {
                   $_SESSION['mail_error'] = $e->getMessage();
                    header("Location: adminMailError.php");
                    exit();
                }

            } else {
                $error = "incorrect_password";
            }
        } else {
            $error = "no_account";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* SPINNER */
.spinner-wrapper{
    background-color: rgba(255,255,255,0.9);
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
}

.spinner-border{
    height: 60px;
    width: 60px;
}

/* DRAWER HANDLE */
.drawer-handle{
    width: 100px;
    height: 4px;
    background-color: #ccc;
    border-radius: 4px;
    margin: 0 auto 10px;
}

/* MOBILE DRAWER */
@media (max-width: 576px) {
    .drawer-login {
        position: fixed;
        top: 20px;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100% !important;
        margin: 0;
        padding: 0 130px;
        border-radius: 32px 32px 0 0 !important;
        z-index: 1050;
    }

    body {
        background-color: #cdd0d3;
    }
}

/* DESKTOP */
@media (min-width: 577px) {
    .drawer-login {
        position: static;
        border-radius: 17px;
    }
}

@media (min-width: 1200px) {
    .custom-container {
        padding-left: 109px;
        padding-right: 109px;
    }
}
</style>
</head>

<body>

<div class="container custom-container py-5 mt-5">
    <div class="row justify-content-center mx-2">

        <!-- IMAGE -->
        <div class="col-lg-8 col-md-12 mb-3 d-none d-sm-block">
            <div class="card border-1 rounded-3 h-100 overflow-hidden">
                <img src="../customer/css/q.jpg"
                    class="img-fluid w-100 h-100"
                    style="object-fit: cover;"
                    alt="Login Image">
            </div>
        </div>

        <!-- FORM -->
        <div class="col-lg-4 col-md-12">
            <div class="card border-1 rounded-3 shadow-lg py-5 drawer-login">
                <div class="card-body px-4 py-4">

                    <div class="drawer-handle d-md-none"></div>

                    <img src="../customer/css/q.jpg" class="rounded-circle d-block mx-auto mb-3" width="90" height="90">

                    <h4 class="text-center mb-3">Customer Login</h4>

                    <form method="POST" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
                        <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>

                        <button id="loginBtn" name="login" class="btn btn-primary w-100"> Login </button>

                        <div class="text-center mt-3">
                            <span class="text-muted">Don't have an account?</span>
                            <a href="customerRegistration.php" class="text-decoration-none fw-semibold">
                                Register now
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>

    </div>
</div>

    <!-- Spinner -->
    <div class="spinner-wrapper" id="spinner">
        <div class="spinner-border text-info" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script>
    const form = document.getElementById('loginForm');
    const spinner = document.getElementById('spinner');

    form.addEventListener('submit', () => {
        spinner.style.display = 'flex'; // show spinner immediately
    });
    </script>

    <?php
    // SweetAlert error messages
    if ($error === 'banned') {
        echo "<script>Swal.fire('Banned','Your account has been banned.','error');</script>";
    } elseif ($error === 'incorrect_password') {
        echo "<script>Swal.fire('Error','Incorrect password.','error');</script>";
    } elseif ($error === 'no_account') {
        echo "<script>Swal.fire('Error','No account found with this email.','error');</script>";
    } elseif ($error === 'invalid_email') {
        echo "<script>Swal.fire('Error','Invalid email format.','error');</script>";
    } elseif ($error === 'mail_failed') {
        echo "<script>Swal.fire('Error','Cannot send OTP. Check your internet connection.','error');</script>";
    }
?>
</body>
</html>
