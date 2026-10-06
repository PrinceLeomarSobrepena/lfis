<?php 
session_start();


// Redirect if already logged in
if (isset($_SESSION['admin'])) {
    header("Location: adminDashboard.php");
    exit();
}

include '../config/db.php';

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

                // ✅ Direct login (no OTP)
                $_SESSION['admin'] = [
                    'id'        => $row['id'],
                    'firstName' => $row['firstName'],
                    'lastName'  => $row['lastName'],
                    'image'     => $row['image'],
                    'role'      => $row['role_as']
                ];

                // Extra session security
                $_SESSION['user_ip']     = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['LAST_ACTIVITY'] = time();

                // Insert admin log
                $login_time = date("Y-m-d H:i:s");
                $status = 'online';

                $stmtLog = $conn->prepare("
                    INSERT INTO adminLogs (adminId, login_time, status)
                    VALUES (?, ?, ?)
                ");
                $stmtLog->bind_param("iss", $row['id'], $login_time, $status);
                $stmtLog->execute();

                $_SESSION['log_id'] = $stmtLog->insert_id;

                // regenerate csrf
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header("Location: adminDashboard.php");
                exit();

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
    /* Spinner styles */
    .spinner-wrapper{
        background-color: rgba(255,255,255,0.9);
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        display: none; /* hidden by default */
        justify-content: center;
        align-items: center;
    }

    .spinner-border{
        height: 60px;
        width: 60px;
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

            <!-- LEFT IMAGE -->
            <!-- <div class="col-md-7 mb-4 mb-md-0">
                <img src="../customer/css/q.jpg" class="img-fluid rounded " alt="Login Image">
            </div> -->
            <div class="col-lg-8 col-md-12 mb-3">
                <div class="card border-1 rounded-3 h-100 overflow-hidden">
                    <img src="../public/img/shidou.png"
                        class="img-fluid w-100 h-100"
                        style="object-fit: cover;"
                        alt="Login Image">
                </div>
            </div>


            <!-- RIGHT FORM -->
            <div class="col-lg-4 col-md-12">
                <!-- <div class="card  p-4 py-5" style="background-color: #f0f4fc;"> -->
                <div class="card border-1 rounded-3 py-5 drawer-login">
                    <div class="card-body px-4 py-4">

                        <div class="drawer-handle d-md-none"></div><!-- drawer handle-->
                
                        <img src="../public/img/shidou.png" class="rounded-circle d-block mx-auto mb-3" width="90" height="90" alt="User">

                        <h4 class="text-center mb-3">Admin Login</h4>

                        <form method="POST" id="loginForm">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                            <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
                            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>

                            <button id="loginBtn" name="login" class="btn login-btn bg-primary  text-white w-100"> Login </button>

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
