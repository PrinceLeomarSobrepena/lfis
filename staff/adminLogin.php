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
<title>Login — Lost And Found Information System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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


*{
    margin:0;
    padding:0;
    box-sizing:border-box;

}
html,body{height:100%;}

body{
    font-family:'Nunito', Arial, sans-serif;
    background:#f5f6fa;
    overflow-x:hidden;
}
a{text-decoration:none;}

.login-wrap{
    display:flex;
    min-height:100vh;
    align-items:stretch;
}

/* ============ LEFT BRAND PANEL ============ */
.brand-panel{
    display:flex;
    flex:1;
    flex-direction:column;
    justify-content:space-between;
    position:relative;
    overflow:hidden;
    background:linear-gradient(135deg, #198754, #0f5c38);
    color: #fff;
    padding:44px 50px;
}

.brand-panel::before{
    content:"";
    position:absolute;
    width:420px;
    height:420px;
    background:radial-gradient(circle, rgba(255,255,255,0.10), transparent 70%);
    top:-160px; 
    right:-120px;
}

.brand-panel::after{
    content:"";
    position:absolute;
    width:320px;
    height:320px;
    background:radial-gradient(circle, rgba(255,255,255,0.08), transparent 70%);
    bottom:-140px; left:-90px;
}

.brand-logo{
    display:flex; align-items:center; position:relative;
    font-weight:900;
    font-size:16px;
    gap:10px;
    z-index:2;
}

.brand-logo i{
    display:flex; align-items:center; justify-content:center;
    width:36px;
    height:36px;
    font-size:16px;
    background:rgba(255,255,255,0.15);
    border-radius:9px;
}

.brand-content{
    position:relative;
    max-width:420px;
    z-index:2;
}

.brand-content h1{
    font-weight:900;
    font-size:32px;
    line-height:1.2;
    letter-spacing:-.7px;
    margin-bottom:14px;
}

.brand-content p{
    font-weight:600;
    font-size:14px;
    line-height:1.6;
    opacity:.85;
    margin-bottom:30px;
}

.brand-feature{
    display:flex; align-items:center;
    margin-bottom:14px;
    gap:12px;
}

.brand-feature-icon{
    display:flex; align-items:center; justify-content:center;
    width:34px;
    height:34px;
    font-size:14px;
    background:rgba(255,255,255,0.15);
    border-radius:9px;
    flex-shrink:0;
}

.brand-feature span{
    font-weight:700;
    font-size:13px;
}

.brand-footer{
    position:relative;
    font-weight:600;
    font-size:11.5px;
    opacity:.65;
    z-index:2;
}


/* ============ RIGHT FORM PANEL ======================================================================================================== */
.form-panel{
    flex:1;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px 30px;
     /* border: 1px solid red; */
}

.login-box{
    width:100%;
    max-width:380px;
    /* border: 1px solid red; */
}

.login-box-head{
    margin-bottom:30px;
}

.login-box-head h3{
    font-weight:900;
    font-size:24px;
    color: #0F1B2D;
    letter-spacing:-.5px;
    margin-bottom:6px;
}

.login-box-head p{
    font-weight:600;
    font-size:13px;
    color: #7C8A85;
}

.form-label{
    display:block;
    font-weight:800;
    font-size:12.5px;
    color: #4B5A54;
    margin-bottom:6px;
}

    /* ===================== PASSWORD INPUT / form-control-custom ===================== */
    .password-input-wrap{
        position:relative;
    }

    .password-input-wrap input{
        font-size:13.5px;
        font-weight:600;
        width:100%;
       border:1px solid #d3d6d5;
        border-radius:10px;
        padding:11px 42px 11px 14px;
        color: #0F1B2D;
    }

    .password-input-wrap input:focus{
        outline:none;
        border-color: #198754;
        background: #fff;
    }

    /* ===================== toggle eye ===================== */
    .toggle-eye{
        position:absolute;
        right:14px;
        top:50%;
        transform:translateY(-50%);
        color: #8CA298;
        font-size:14px;
        cursor:pointer;
        background:none;
        border:none;
    }

    .toggle-eye:hover{
        color: #198754;
    }

/* .input-icon-wrap{
    position:relative;
    margin-bottom:16px;
}

.input-icon-wrap i{
    position:absolute;
    left:15px;
    top:50%;
    font-size:14px;
    color: #8CA298;
    transform:translateY(-50%);
} */

.form-control-custom{
    font-family:'Nunito', Arial, sans-serif;
    width:100%;
    font-weight:600;
    font-size:13.5px;
    color: #0F1B2D;
    background: #f9fbfa;
    border:1px solid #d3d6d5;
    border-radius:11px;
    /* padding:12px 14px 12px 40px; */
    padding:11px 14px;
}

.form-control-custom:focus{
    outline:none;
    border-color: #198754;
    background: #fff;
    /* box-shadow:0 0 0 3px rgba(25,135,84,0.08); */
}

/* ============ CHECK BOX ============ */
.form-options{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:22px;
}

.remember-check{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:12.5px;
    font-weight:700;
    color: #4B5A54;
}

.remember-check input{
    accent-color:#198754;
    width:15px;
    height:15px;
}

.forgot-link{
    font-size:12.5px;
    font-weight:800;
    color:#198754;
}

.forgot-link:hover{text-decoration:underline;}

/* ============ SUBMIT BUTTON ============ */
.btn-login-submit{
    display:flex; align-items:center; justify-content:center;
    width:100%;
    font-weight:800;
    font-size:14px;
    color:#fff;
    background:linear-gradient(135deg, #198754, #147a49);
    border:none;
    padding:13px;
    border-radius:11px;
    gap:8px;
    box-shadow:0 10px 22px rgba(25,135,84,0.25);
    margin-bottom:18px;
}

.access-note{
    display:flex;
    align-items:flex-start;
    font-weight:600;
    font-size:11.5px;
    color: #147a49;
    background: #F3FBF7;
    border:1px solid #dcefe4;
    border-radius:10px;
    padding:12px 14px;
    line-height:1.5;
    gap:8px;
    align-items:flex-start;
}

.public-search-link{
    text-align:center;
    font-weight:600;
    font-size:12.5px;
    color:#7C8A85;
    margin-top:22px;
}

.public-search-link a{
    font-weight:800;
    color:#198754;
  
}

.public-search-link a:hover{text-decoration:underline;}

/* ================= MOBILE ================= */
@media(max-width:900px){
    .login-wrap{
        flex-direction:column;
    }
    .brand-panel{
        flex:none;
        padding:30px 26px;
        min-height:220px;
    }
    .brand-content h1{font-size:24px;}
    .brand-content p{margin-bottom:16px;}
    .brand-feature{display:none;}
    .brand-footer{display:none;}
    .form-panel{
        padding:34px 24px 50px; /* 34px 24px 50px */
    }
}
</style>
</head>
<body>

    <div class="login-wrap">

        <!-- LEFT BRAND PANEL -->
        <div class="brand-panel">
            <div class="brand-logo">
                <i class="bi bi-search-heart"></i>
                 L&nbsp;&nbsp;F&nbsp;<span class="text-white" style="margin-left: -5px;">I &nbsp;S</span>
            </div>

            <div class="brand-content">
                <h1>Reuniting people with what they've lost.</h1>
                <p>Sign in to manage found items, review matches, and help students and staff get their things back — faster.</p>

                <div class="brand-feature">
                    <div class="brand-feature-icon"><i class="bi bi-layers"></i></div>
                    <span>Automatic matching between lost & found reports</span>
                </div>
                <div class="brand-feature">
                    <div class="brand-feature-icon"><i class="bi bi-clipboard-data"></i></div>
                    <span>Full audit trail for every item logged</span>
                </div>
                <div class="brand-feature">
                    <div class="brand-feature-icon"><i class="bi bi-shield-check"></i></div>
                    <span>Verified claims before any item is released</span>
                </div>
            </div>

            <div class="brand-footer">
                &copy; 2026 Lost And Found Information System
            </div>
        </div>

        <!-- RIGHT FORM PANEL -->
        <div class="form-panel">
            <div class="login-box">

                <div class="login-box-head">
                    <h3>Welcome back</h3>
                    <p>Sign in with your staff or admin account.</p>
                </div>

                <form method="POST" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control-custom" placeholder="Enter your email." required>
                    </div>
                   

                    <!-- <label class="form-label">Password</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="password" class="form-control-custom" placeholder="Enter your password." required>
                    </div> -->

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="password-input-wrap">
                            <input type="password" name="password" id="oldPassword" class="form-control-custom" placeholder="Enter your old." required>
                            <button type="button" class="toggle-eye" data-target="oldPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-check">
                            <input type="checkbox"> Remember me
                        </label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" id="loginBtn" name="login" class="btn-login-submit"><i class="bi bi-box-arrow-in-right"></i> Sign In</button>
                </form>

                <div class="access-note">
                    <i class="bi bi-info-circle"></i>
                    <span>Access is limited to registered staff and admin accounts. Contact your system administrator if you need an account.</span>
                </div>

                <div class="public-search-link">
                    Looking for a lost item? <a href="../zzzLostandFound/index.php">Search found items</a> — no login needed.
                </div>

            </div>
        </div>

    </div>

    <!-- Spinner -->
    <div class="spinner-wrapper" id="spinner">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

<!-- ======== SPINNER ======= -->
<script>
const form = document.getElementById('loginForm');
const spinner = document.getElementById('spinner');

form.addEventListener('submit', () => {
    spinner.style.display = 'flex'; // show spinner immediately
});
</script>


<!-- ========= EMAIL SANITATION ========= --> 
<script>
const emailInput = document.querySelector('input[name="email"]');

emailInput.addEventListener('input', function () {
    if (/[|&]/.test(this.value)) {
        // this.value = this.value.replace(/[|&]/g, '');
        this.value = this.value.replace(/[|&!<>]/g, '');

        this.setCustomValidity('The characters | and & are not allowed.');
    } else {
        this.setCustomValidity('');
    }
});
</script>


<script>
/* SHOW / HIDE PASSWORD TOGGLE */
document.querySelectorAll(".toggle-eye").forEach(btn=>{
    btn.addEventListener("click", function(){
        const input = document.getElementById(this.dataset.target);
        const icon = this.querySelector("i");
        if(input.type === "password"){
            input.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    });
});
</script>


<?php
if ($error === 'banned') {
    echo "<script>
        Swal.fire({
            title: 'Banned',
            text: 'Your account has been banned.',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    </script>";
} elseif ($error === 'incorrect_password') {
    echo "<script>
        Swal.fire({
            title: 'Error',
            text: 'Incorrect Credentials.',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    </script>";
} elseif ($error === 'no_account') {
    echo "<script>
        Swal.fire({
            title: 'Error',
            text: 'Incorrect Credentials',  // No account found with this email.
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    </script>";
} elseif ($error === 'invalid_email') {
    echo "<script>
        Swal.fire({
            title: 'Error',
            text: 'Invalid email format.',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    </script>";
} elseif ($error === 'mail_failed') {
    echo "<script>
        Swal.fire({
            title: 'Error',
            text: 'Cannot send OTP. Check your internet connection.',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    </script>";
}
?>


</body>
</html>