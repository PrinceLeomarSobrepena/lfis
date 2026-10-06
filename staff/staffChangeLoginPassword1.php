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

if (!$staff) {
    session_destroy();
    header("Location: staffLogin.php");
    exit();
}

$message = "";
$type    = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Live old password check (AJAX)
if (isset($_POST['check_old_password'])) {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['correct' => false, 'locked' => true]);
        exit();
    }

    $_SESSION['old_pw_checks'] = ($_SESSION['old_pw_checks'] ?? 0) + 1;

    // limit para hindi ma-brute force ang old password
    if ($_SESSION['old_pw_checks'] > 20) {
        echo json_encode(['correct' => false, 'locked' => true]);
        exit();
    }

    $val = trim($_POST['old_password'] ?? '');

    echo json_encode(['correct' => password_verify($val, $staff['password'])]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token mismatch.");
    }

    $oldPassword    = trim($_POST['old_password'] ?? '');
    $newPassword    = trim($_POST['new_password'] ?? '');
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

    } elseif (strlen($newPassword) < 8) {
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
            unset($_SESSION['old_pw_checks']);

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
<title>Change Password — Lost And Found Information System</title>

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

.security-steps{
    margin-top:30px;
}

.security-step{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:16px;
}

.security-step .num{
    width:28px;height:28px;
    border-radius:50%;
    background:rgba(255,255,255,0.15);
    display:flex;align-items:center;justify-content:center;
    font-size:12px;
    font-weight:900;
    flex-shrink:0;
}

.security-step.done .num{
    background:#fff;
    color:#198754;
}

.security-step span{
    font-size:13px;
    font-weight:700;
}

.brand-footer{
    position:relative;
    font-weight:600;
    font-size:11.5px;
    opacity:.65;
    z-index:2;
}


/* ============ RIGHT FORM PANEL ============ */
.form-panel{
    flex:1;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px 30px;
}

.login-box{
    width:100%;
    max-width:380px;
}

.mandatory-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:#FFF4E5;
    color:#B8860B;
    font-size:11px;
    font-weight:800;
    padding:6px 13px;
    border-radius:20px;
    margin-bottom:16px;
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
        border:1px solid #E7ECE9;
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

/* Wrong / correct state (old password + verify password) */
.form-control-custom.is-wrong,
.form-control-custom.is-wrong:focus{
    border-color: #dc3545;
    background: #fff5f5;
}

.form-control-custom.is-correct,
.form-control-custom.is-correct:focus{
    border-color: #198754;
    background: #f9fbfa;
}

/* field messages (borrowed from admin change-password page) */
.field-hint{
    font-size:11px;
    color:#9AA6A1;
    font-weight:600;
    margin-top:2px;
    margin-bottom:14px;
}

.field-error{
    font-size:11px;
    color:#E1596B;
    font-weight:700;
    margin-top:2px;
    margin-bottom:14px;
    display:none;
}

.field-error.show{
    display:block;
}

.field-success{
    color:#198754;
}

/* strength meter (borrowed from admin change-password page) */
.strength-meter{
    display:flex;
    gap:5px;
    margin-top:8px;
}

.strength-bar{
    height:5px;
    flex:1;
    border-radius:4px;
    background:#EDEFF0;
    transition:.25s;
}

.strength-label{
    font-size:11px;
    font-weight:700;
    margin-top:6px;
    margin-bottom:14px;
    color:#9AA6A1;
}


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
    .security-step{display:none;}
    .brand-footer{display:none;}
    .form-panel{
        padding:34px 24px 50px;
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
                <h1>Let's secure your account first.</h1>
                <p>This is your first time signing in with a temporary password. Set a new one to continue to your dashboard.</p>

                <div class="security-steps">
                    <div class="security-step done">
                        <div class="num"><i class="bi bi-check"></i></div>
                        <span>Signed in with temporary password</span>
                    </div>
                    <div class="security-step">
                        <div class="num">2</div>
                        <span>Set a new, secure password</span>
                    </div>
                    <div class="security-step">
                        <div class="num">3</div>
                        <span>Continue to your dashboard</span>
                    </div>
                </div>
            </div>

            <div class="brand-footer">
                &copy; 2026 Lost And Found Information System
            </div>
        </div>

        <!-- RIGHT FORM PANEL -->
        <div class="form-panel">
            <div class="login-box">

                <span class="mandatory-badge"><i class="bi bi-shield-exclamation"></i> Required before continuing</span>

                <div class="login-box-head">
                    <h3>Change Your Password</h3>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="field-error <?= $type === 'success' ? 'field-success' : '' ?> show" style="margin-bottom:16px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="changePasswordForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <!-- <label class="form-label">Current Password</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="old_password" id="oldPassword" class="form-control-custom" placeholder="Enter your current password" required>
                        <button type="button" class="toggle-eye" data-target="oldPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div> -->

                    <div class="mb-3">
                        <label class="form-label">Old Password</label>
                        <div class="password-input-wrap">
                            <input type="password" name="old_password" id="oldPassword" class="form-control-custom" placeholder="Enter your old." autocomplete="off" required>
                            <button type="button" class="toggle-eye" data-target="oldPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- <label class="form-label">New Password</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="new_password" id="newPassword" class="form-control-custom" placeholder="Enter a new password" minlength="8" required>
                        <button type="button" class="toggle-eye" data-target="newPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div> -->
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="password-input-wrap">
                            <input type="password" name="new_password" id="newPassword" class="form-control-custom" placeholder="Enter your new password." maxlength="20" required>
                            <button type="button" class="toggle-eye" data-target="newPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="strength-meter">
                        <div class="strength-bar" id="bar1"></div>
                        <div class="strength-bar" id="bar2"></div>
                        <div class="strength-bar" id="bar3"></div>
                        <div class="strength-bar" id="bar4"></div>
                    </div>
                    <div class="strength-label" id="strengthLabel">Password strength</div>
                    <div class="field-error" id="newPasswordError">New password must be at least 6 characters.</div>

                    <!-- <label class="form-label">Verify New Password</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="verify_password" id="verifyPassword" class="form-control-custom" placeholder="Re-enter the new password" minlength="8" required>
                        <button type="button" class="toggle-eye" data-target="verifyPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div> -->

                    <div class="mb-3">
                        <label class="form-label">Verify Password</label>
                        <div class="password-input-wrap">
                            <input type="password" name="verify_password" id="verifyPassword" class="form-control-custom" placeholder="Enter your verify password." required>
                            <button type="button" class="toggle-eye" data-target="verifyPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="loginBtn" class="btn-login-submit"><i class="bi bi-box-arrow-in-right"></i> Save New Password</button>
                </form>

                <div class="access-note">
                    <i class="bi bi-info-circle"></i>
                    <span>Please update your password before accessing the dashboard.</span>
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

<!-- ======== SHOW / HIDE PASSWORD TOGGLE (borrowed from admin change-password page) ======= -->
<script>
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

<!-- ======== PASSWORD STRENGTH METER (borrowed from admin change-password page) ======= -->
<script>
const newPasswordInput = document.getElementById("newPassword");
const bars = [document.getElementById("bar1"), document.getElementById("bar2"), document.getElementById("bar3"), document.getElementById("bar4")];
const strengthLabel = document.getElementById("strengthLabel");
const strengthColors = ["#E1596B", "#E1596B", "#B8860B", "#198754"];
const strengthText = ["Too weak", "Weak", "Good", "Strong"];
let currentStrengthScore = 0;

function getStrength(pw){
    let score = 0;
    if(pw.length >= 8) score++;
    if(/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if(/[0-9]/.test(pw)) score++;
    if(/[^A-Za-z0-9]/.test(pw)) score++;
    return score;
}

const newPasswordError = document.getElementById("newPasswordError");

newPasswordInput.addEventListener("input", function(){
    const pw = this.value;
    const score = pw.length === 0 ? 0 : getStrength(pw);
    currentStrengthScore = score;

    bars.forEach((bar, i)=>{
        if(i < score){
            bar.style.background = strengthColors[score-1];
        } else {
            bar.style.background = "#EDEFF0";
        }
    });

    strengthLabel.textContent = pw.length === 0 ? "Password strength" : strengthText[Math.max(score-1, 0)];
    strengthLabel.style.color = pw.length === 0 ? "#9AA6A1" : strengthColors[Math.max(score-1, 0)];

    validateMatch();
});
</script>


<!-- ========= VERIFY PASSWORD MATCH (setCustomValidity) ========= -->
<script>
const verifyPasswordInput = document.getElementById("verifyPassword");
const VERIFY_MSG = "Passwords do not match.";

function validateMatch(){
    const v = verifyPasswordInput.value;

    if (v.length === 0) {
        verifyPasswordInput.classList.remove("is-wrong", "is-correct");
        verifyPasswordInput.setCustomValidity("");   // required na ang bahala dito
        return;
    }

    if (v !== newPasswordInput.value) {
        verifyPasswordInput.classList.add("is-wrong");
        verifyPasswordInput.classList.remove("is-correct");
        verifyPasswordInput.setCustomValidity(VERIFY_MSG);
    } else {
        verifyPasswordInput.classList.add("is-correct");
        verifyPasswordInput.classList.remove("is-wrong");
        verifyPasswordInput.setCustomValidity("");
    }
}

verifyPasswordInput.addEventListener("input", validateMatch);
</script>


<!-- ========= OLD PASSWORD LIVE CHECK (setCustomValidity) ========= -->
<script>
const oldPasswordInput = document.getElementById("oldPassword");
const csrfToken        = document.querySelector('input[name="csrf_token"]').value;
const OLD_MSG          = "Incorrect old password. Please correct it.";
let oldTimer;
let oldController;

function setOldState(state) {
    // state: 'correct' | 'wrong' | 'none'
    oldPasswordInput.classList.toggle("is-correct", state === "correct");
    oldPasswordInput.classList.toggle("is-wrong",   state === "wrong");

    const msg = (state === "correct" || state === "none") ? "" : OLD_MSG;
    if (oldPasswordInput.validationMessage !== msg) {
        oldPasswordInput.setCustomValidity(msg);
    }
}

oldPasswordInput.addEventListener("input", function () {
    clearTimeout(oldTimer);
    if (oldController) oldController.abort();   // cancel lumang request

    if (this.value === "") {
        setOldState("none");   // required na ang bahala dito
        return;
    }

    // block muna ang submit habang chine-check, pero HUWAG galawin ang kulay (iwas flicker)
    this.setCustomValidity(OLD_MSG);

    const value = this.value;

    oldTimer = setTimeout(() => {
        oldController = new AbortController();

        fetch(window.location.href, {
            method: "POST",
            body: new URLSearchParams({
                check_old_password: 1,
                old_password: value,
                csrf_token: csrfToken
            }),
            signal: oldController.signal
        })
            .then(res => res.json())
            .then(data => {
                if (oldPasswordInput.value !== value) return;   // luma na

                if (data.locked) { setOldState("none"); return; }
                setOldState(data.correct ? "correct" : "wrong");
            })
            .catch(err => {
                if (err.name === "AbortError") return;
                setOldState("none");   // network error: server na ang bahala
            });
    }, 350);
});
</script>


<!-- Spinner -->
<script>
const form = document.getElementById('changePasswordForm');
const spinner = document.getElementById('spinner');

form.addEventListener("submit", function() {
    spinner.style.display = 'flex';
});
</script>


<!-- Form Submit Validation (new password length lang; old at verify ay sa setCustomValidity na) -->
<script>
const passwordForm = document.getElementById('changePasswordForm');

passwordForm.addEventListener("submit", function(e) {
    const newPassword = document.getElementById("newPassword");
    const newPasswordErr = document.getElementById("newPasswordError");

    if (newPassword.value.length < 8) {
        newPasswordErr.classList.add("show");
        e.preventDefault();
        document.getElementById('spinner').style.display = 'none';   // itago ulit ang spinner
    } else {
        newPasswordErr.classList.remove("show");
    }
});
</script>




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