<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

$adminId = $_SESSION['admin']['id'];

$stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// ✅ Live old password check (AJAX)
if (isset($_POST['check_old_password'])) {
    header('Content-Type: application/json');

    $_SESSION['old_pw_checks'] = ($_SESSION['old_pw_checks'] ?? 0) + 1;

    // limit para hindi ma-brute force ang old password
    if ($_SESSION['old_pw_checks'] > 20) {
        echo json_encode(['correct' => false, 'locked' => true]);
        exit();
    }

    $val = trim($_POST['old_password'] ?? '');

    echo json_encode(['correct' => password_verify($val, $admin['password'])]);
    exit();
}

// Per-field server-side errors (rendered the same way as the JS field errors)
$oldPasswordServerError    = "";
$newPasswordServerError    = "";
$verifyPasswordServerError = "";
$successMessage            = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword    = trim($_POST['old_password'] ?? '');
    $newPassword    = trim($_POST['new_password'] ?? '');
    $verifyPassword = trim($_POST['verify_password'] ?? '');

    // Check old password
    if (!password_verify($oldPassword, $admin['password'])) {
        $oldPasswordServerError = "Old password is incorrect.";

    } elseif ($newPassword !== $verifyPassword) {
        $verifyPasswordServerError = "New password and Verify password do not match.";

    } elseif (strlen($newPassword) < 8) {
        // Aligned with the front-end rule (min 8 characters)
        $newPasswordServerError = "Password must be at least 8 characters.";

    } elseif (!preg_match('/[A-Z]/', $newPassword) ||
              !preg_match('/[a-z]/', $newPassword) ||
              !preg_match('/[0-9]/', $newPassword) ||
              !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
        // Aligned with the "Strong" requirement in the JS strength meter
        $newPasswordServerError = "Password is too weak. Use a mix of uppercase, lowercase, numbers, and symbols.";

    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $adminId);
        if ($stmt->execute()) {

            unset($_SESSION['old_pw_checks']);

            // =====================
            // SEND EMAIL (hiwalay na file, kagaya ng adminLostFoundCreateEmail.php)
            // =====================
            $fullName = $admin['firstName'] . ' ' . $admin['lastName'];

            $emailData = http_build_query([
                'receiver_email' => $admin['email'],
                'full_name'      => $fullName
            ]);

            $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/adminChangePasswordEmail.php');

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $emailData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_exec($ch);
            curl_close($ch);

            $successMessage = "Password changed successfully.";
        } else {
            $newPasswordServerError = "Failed to update password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../public/css/admin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- font style -->
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* ====================== FORM CARD ===================== */
    .form-card{
        background: #fff;
        border:1px solid #E7ECE9;
        border-radius:14px;
        padding:26px;
        box-shadow:0 4px 20px rgba(20,60,40,0.04);
    }

    .password-card-icon{
        display:flex;
        width:30px;
        height:30px;
        font-size:22px;
        background: #E8F7EF;
        color: #198754;
        border-radius:7px;
        align-items:center;
        justify-content:center;
        margin-bottom:16px;
    }

    .form-label{
        display:block;
        font-weight:700;
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

    /* Wrong / correct state (old password + verify password) */
    .password-input-wrap input.is-wrong,
    .password-input-wrap input.is-wrong:focus{
        border-color: #dc3545;
        background: #fff5f5;
    }

    .password-input-wrap input.is-correct,
    .password-input-wrap input.is-correct:focus{
        border-color: #198754;
        background: #f9fbfa;
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

     /* ====================== ACTION BUTTONS ===================== */
    .form-actions{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        margin-top:22px;
    }

    .btn-submit{
        display:flex;
        font-weight:700;
        font-size:13.5px;
        background:linear-gradient(135deg, #198754, #147a49);
        color: #fff;
        border:none;
        border-radius:10px;
        padding: 11px 24px;
        align-items:center;
        gap:8px;
        box-shadow:0 6px 14px rgba(25,135,84,0.25);
    }

    /* ====================== SIDE PASSWORD GUIDE ===================== */
    .password-card{
        background: #fff;
        border:1px solid #E7ECE9;
        border-radius:14px;
        padding:22px;
         box-shadow:0 4px 20px rgba(20,60,40,0.04);
    }

    .password-card h6{
        font-weight:800;
        font-size:13.5px;
        margin-bottom:12px;
        color: #0F1B2D;
    }

    .password-tip{
        display:flex;
        align-items:flex-start;
        font-weight:600;
        font-size:11.5px;
        color:#147a49;
        padding: 12px 14px;
        background: #E8F7EF;
        border-radius: 10px;
        gap:8px;
        line-height:1.5;
        margin-top:16px;
    }

    .password-tip i{
        margin-top:1px;
    }

    /* ====================== SIDE PASSWORD GUIDE STRENGTH ===================== */
    .strength-list{
        list-style:none;
        padding:0 0 0 15px;
        margin:0;
    }

    .strength-list li{
        display:flex;
        align-items:center;
        font-weight:700;
        font-size:12px;
        color:#4B5A54;
        padding:6px 0;
        gap:8px;
    }

    .strength-list .dot{
        width:9px;
        height:9px;
        border-radius:50%;
        flex-shrink:0;
    }

/* ====================== ??? ===================== */
    .field-hint{
        font-size:11px;
        color:#9AA6A1;
        font-weight:600;
        margin-top:6px;
    }

    .field-error{
        font-size:11px;
        color:#E1596B;
        font-weight:700;
        margin-top:6px;
        display:none;
    }

    .field-error.show{
        display:block;
    }

    .field-success{
        color:#198754;
        margin-bottom:16px;
    }

    /* strength meter */
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
        color:#9AA6A1;
    }
</style>
</head>
<body>
    <!-- OVERLAY -->
    <div id="overlay"></div>

    <!-- SIDEBAR -->
    <div class="sidebar shadow-sm" id="sidebar">
        <div class="btn-close-outside" onclick="toggleSidebar()">
            <i class="fas fa-times" style="margin-left: -1px;"></i>
        </div>

        <div class="brand mb-3 mt-2">
            <i class="bi bi-search-heart"></i>
            L&nbsp;&nbsp;F&nbsp;<span class="text-success" style="margin-left: -5px;">I &nbsp;S</span>
        </div>
        <hr>

        <a href="adminDashboard.php"> <i class="bi bi-speedometer2"></i> Dashboard </a>
        <a href="adminStaffList.php"> <i class="bi bi-people"></i> Staff </a>
        <a href="adminLostFoundList.php"> <i class="bi bi-journal-text"></i> List </a>
        <a href="adminLostFoundMatches.php"> <i class="bi bi-layers"></i> Match Items </a>
        <a href="adminLostFoundItems.php"> <i class="bi bi-search"></i> Found Items </a>
        <a href="adminLostFoundAuditTrail.php"> <i class="bi bi-clipboard-data"></i> Audit Trail </a>
        <a href="adminLostFoundStatistic.php"> <i class="bi bi-graph-up-arrow"></i> Statistic </a>
        <a href="adminLostFoundCalendar.php"> <i class="bi bi-calendar-event"></i> Calendar </a>
        <a href="adminChangePassword.php" class="active"> <i class="bi bi-lock" style="display: inline-block; transform: scaleX(1.4);"></i> Change Password </a>
        <a href="adminLogout.php" class="logout-btn"> <i class="bi bi-box-arrow-right"></i> Logout </a>
    </div>

    <!-- NAVBAR -->
    <nav class="navbar-custom shadow-sm">
        <div class="navbar-left">
            <img src="../uploads/SCHOOL.jpg" class="profile-img ">

            <div>
                <h5 class="navbar-title  text-success">Lost And Found Information System</h5>
                <div class="navbar-subtitle">DR. GLORIA D. LACSON FOUNDATION COLLEGES, INC.</div>
            </div>
        </div>

        <div class="navbar-right">
            <i class="fa-solid fa-bars icon menu-toggle-btn" onclick="toggleSidebar()"></i>
            <h5 class=" d-none d-lg-block">Hi, Admin</h5>
            <img src="https://picsum.photos/200"  class="profile-img d-none d-lg-block">
        </div>
    </nav>

    <!-- MAIN-CONTENT -->
    <div class="main-content">
        <div class="mt-4 mb-3">
            <div class="page-heading">Update your password</div>
            <div class="page-subheading">Update your password to protect your account.</div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="form-card">
                    <div class="password-card-icon"> <i class="bi bi-shield-lock"></i> </div>
                    <div class="form-section-title">Update your password</div>

                    <?php if (!empty($successMessage)): ?>
                        <div class="field-error field-success show"><?= htmlspecialchars($successMessage) ?></div>
                    <?php endif; ?>

                    <form method="POST" id="changePasswordForm">
                         <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label"> Old Password </label>
                                <div class="password-input-wrap">
                                    <input type="password" name="old_password" id="oldPassword" placeholder="Enter your current password" autocomplete="off" required>
                                    <button type="button" class="toggle-eye" data-target="oldPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <?php if (!empty($oldPasswordServerError)): ?>
                                    <div class="field-error show"><?= htmlspecialchars($oldPasswordServerError) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label"> New Password </label>
                                <div class="password-input-wrap">
                                    <input type="password" name="new_password" id="newPassword" placeholder="Enter a new password" required>
                                    <button type="button" class="toggle-eye" data-target="newPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                 <div class="strength-meter">
                                    <div class="strength-bar" id="bar1"></div>
                                    <div class="strength-bar" id="bar2"></div>
                                    <div class="strength-bar" id="bar3"></div>
                                    <div class="strength-bar" id="bar4"></div>
                                </div>
                                <div class="strength-label" id="strengthLabel">Password strength</div>
                                <div class="field-error" id="newPasswordError">New password must be at least 8 characters.</div>
                                <div class="field-error" id="newPasswordWeakError">Password is too weak. Use a mix of uppercase, lowercase, numbers, and symbols.</div>
                                <?php if (!empty($newPasswordServerError)): ?>
                                    <div class="field-error show"><?= htmlspecialchars($newPasswordServerError) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label"> Verify New Password </label>
                                <div class="password-input-wrap">
                                    <input type="password" name="verify_password" id="verifyPassword" placeholder="Re-enter the new password" required>
                                    <button type="button" class="toggle-eye" data-target="verifyPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <?php if (!empty($verifyPasswordServerError)): ?>
                                    <div class="field-error show"><?= htmlspecialchars($verifyPasswordServerError) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-submit"><i class="bi bi-check2-circle"></i> Save New Password</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

            <div class="col-lg-5">
                <div class="password-card">
                    <h6>Password Strength Legend</h6>
                    <ul class="strength-list" style="margin-bottom:12px">
                        <li><span class="dot" style="background:#E1596B;"></span> Too weak</li>
                        <li><span class="dot" style="background:#E1596B;"></span> Weak</li>
                        <li><span class="dot" style="background:#B8860B;"></span> Good </li>
                        <li><span class="dot" style="background:#198754;"></span> Strong (Required)</li>
                    </ul>

                    <h6>Note:</h6>
                    <div class="password-tip">
                        <i class="bi bi-info-circle"></i>
                        <span>Once you successfully change password, the system will automatically send you an email for the changes please checked the email if you received.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>


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

/* PASSWORD STRENGTH METER */
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
const newPasswordWeakError = document.getElementById("newPasswordWeakError");

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

    if(pw.length >= 8 && score < 4){
        newPasswordWeakError.classList.add("show");
    } else {
        newPasswordWeakError.classList.remove("show");
    }

    validateMatch();
});

/* VERIFY PASSWORD MATCH (setCustomValidity) */
const verifyPasswordInput = document.getElementById("verifyPassword");
const VERIFY_MSG = "Passwords do not match.";

function validateMatch(){
    const v = verifyPasswordInput.value;

    if(v.length === 0){
        verifyPasswordInput.classList.remove("is-wrong", "is-correct");
        verifyPasswordInput.setCustomValidity("");   // required na ang bahala dito
        return;
    }

    if(v !== newPasswordInput.value){
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

/* OLD PASSWORD LIVE CHECK (setCustomValidity) */
const oldPasswordInput = document.getElementById("oldPassword");
const OLD_MSG = "Incorrect old password. Please correct it.";
let oldTimer;
let oldController;

function setOldState(state){
    // state: 'correct' | 'wrong' | 'none'
    oldPasswordInput.classList.toggle("is-correct", state === "correct");
    oldPasswordInput.classList.toggle("is-wrong",   state === "wrong");

    const msg = (state === "correct" || state === "none") ? "" : OLD_MSG;
    if(oldPasswordInput.validationMessage !== msg){
        oldPasswordInput.setCustomValidity(msg);
    }
}

oldPasswordInput.addEventListener("input", function(){
    clearTimeout(oldTimer);
    if(oldController) oldController.abort();   // cancel lumang request

    if(this.value === ""){
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
            body: new URLSearchParams({ check_old_password: 1, old_password: value }),
            signal: oldController.signal
        })
            .then(res => res.json())
            .then(data => {
                if(oldPasswordInput.value !== value) return;   // luma na

                if(data.locked){ setOldState("none"); return; }
                setOldState(data.correct ? "correct" : "wrong");
            })
            .catch(err => {
                if(err.name === "AbortError") return;
                setOldState("none");   // network error: server na ang bahala
            });
    }, 350);
});

/* FORM SUBMIT VALIDATION (new password lang; old at verify ay sa setCustomValidity na) */
document.getElementById("changePasswordForm").addEventListener("submit", function(e){
    const newPassword = document.getElementById("newPassword");

    let valid = true;

    if(newPassword.value.length < 8){
        newPasswordError.classList.add("show");
        valid = false;
    } else {
        newPasswordError.classList.remove("show");
    }

    if(newPassword.value.length >= 8 && currentStrengthScore < 4){
        newPasswordWeakError.classList.add("show");
        valid = false;
    } else {
        newPasswordWeakError.classList.remove("show");
    }

    /* Only block submission when invalid — otherwise let the form
       submit normally so PHP can process it. */
    if(!valid){
        e.preventDefault();
    }
});
</script>


<!-- ========= Default js ========== -->
<script>
function toggleSidebar(){
    document.getElementById("sidebar")
    .classList.toggle("show");

    document.getElementById("overlay")
    .classList.toggle("show");
}

/* CLOSE WHEN CLICK OVERLAY */
document.getElementById("overlay")
.addEventListener("click", function(){
    document.getElementById("sidebar")
    .classList.remove("show");

    this.classList.remove("show");
});

/* FIX RESIZE */
window.addEventListener("resize", ()=>{
    if(window.innerWidth >= 992){
        document.getElementById("sidebar")
        .classList.remove("show");

        document.getElementById("overlay")
        .classList.remove("show");
    }
});
</script>

<!-- Auto Log-out -->
<script>
    // check session every 15 minutes
    setInterval(() => {
        fetch('../middleware/adminAutoLogout.php')
            .then(res => res.json())
            .then(data => {
                if (!data.active) {
                    window.location.href = 'adminLogin.php';
                }
            })
            .catch(err => console.error(err));
    }, 900000); // Every 15 minutes
</script>
</body>
</html>