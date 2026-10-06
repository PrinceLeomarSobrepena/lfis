<?php
session_start();
include '../config/db.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Required session check
if (!isset($_SESSION['temp_admin_id']) || !isset($_SESSION['otp']) || !isset($_SESSION['otp_expire'])) {
    header("Location: adminLogin.php");
    exit();
}

// OTP attempts limit
if (!isset($_SESSION['otp_attempts'])) {
    $_SESSION['otp_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token!");
    }

    $input_otp = implode('', $_POST['otp']);

    // OTP expired
    if (time() > $_SESSION['otp_expire']) {
        $error = "❌ OTP expired!";
        session_unset();
        session_destroy();

    // OTP correct
    } elseif ($input_otp == $_SESSION['otp']) {

        $adminId = $_SESSION['temp_admin_id'];

        // Fetch admin using ID
        $stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Admin session
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

            // Insert admin log (login)
            $login_time = date("Y-m-d H:i:s");
            $status = 'online';

            $stmtLog = $conn->prepare("
                INSERT INTO adminLogs (adminId, login_time, status)
                VALUES (?, ?, ?)
            ");
            $stmtLog->bind_param("iss", $adminId, $login_time, $status);
            $stmtLog->execute();

            $_SESSION['log_id'] = $stmtLog->insert_id;

            // Cleanup OTP sessions
            unset(
                $_SESSION['otp'],
                $_SESSION['otp_expire'],
                $_SESSION['temp_admin_id'],
                $_SESSION['otp_attempts']
            );

            // Regenerate CSRF
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header("Location: adminDashboard.php");
            exit();

        } else {
            $error = "❌ Account not found.";
        }

    // OTP incorrect
    } else {
        $_SESSION['otp_attempts']++;

        if ($_SESSION['otp_attempts'] >= 3) {
            $error = "🚫 Too many attempts. Session ended.";
            session_unset();
            session_destroy();
            header("Location: adminLogin.php");
            exit();
        } else {
            $error = "❌ Incorrect OTP. Tries left: " . (3 - $_SESSION['otp_attempts']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; font-family: Arial, sans-serif; }
body {
    margin: 0;
    background: #f2f4f8;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.otp-container {
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    width: 100%;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,.1);
}
.otp-input-group {
    display: flex;
    justify-content: center;
    margin-top: 15px;
}
input.otp-box {
    width: 45px;
    height: 55px;
    font-size: 24px;
    text-align: center;
    margin: 0 5px;
    border-radius: 8px;
    border: 2px solid #ccc;
}
input.otp-box:focus {
    border-color: red;
    outline: none;
}
button {
    margin-top: 20px;
    padding: 12px 25px;
    border: none;
    border-radius: 6px;
    background: #007bff;
    color: #fff;
    cursor: pointer;
}
.error {
    color: red;
    font-weight: bold;
}
#timer {
    font-weight: bold;
    color: green;
}
</style>
</head>
<body>

<div class="otp-container">
    <h3>Verify OTP</h3>
    <p>You have 3 attempts</p>

    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="otp-input-group">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <input type="text" name="otp[]" maxlength="1" class="otp-box" required>
            <?php endfor; ?>
        </div>
        <button type="submit">✅ Verify</button>
    </form>

    <p>⏱ OTP expires in: <span id="timer">05:00</span></p>
</div>

<script>
const boxes = document.querySelectorAll('.otp-box');
boxes[0].focus();

// Auto move
boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
        if (box.value.length === 1 && i < boxes.length - 1) {
            boxes[i + 1].focus();
        }
    });
    box.addEventListener('keydown', e => {
        if (e.key === "Backspace" && !box.value && i > 0) {
            boxes[i - 1].focus();
        }
    });
});

// Timer
let expiry = <?= $_SESSION['otp_expire'] ?>;
let timer = setInterval(() => {
    let now = Math.floor(Date.now() / 1000);
    let remaining = expiry - now;

    if (remaining <= 0) {
        document.getElementById('timer').innerText = "EXPIRED";
        clearInterval(timer);
        setTimeout(() => window.location.href = 'adminLogin.php', 2000);
        return;
    }

    let m = Math.floor(remaining / 60);
    let s = remaining % 60;
    document.getElementById('timer').innerText =
        (m < 10 ? '0'+m : m) + ':' + (s < 10 ? '0'+s : s);
}, 1000);

// Allow pasting full OTP
    boxes[0].addEventListener('paste', function (e) {
        let paste = (e.clipboardData || window.clipboardData).getData('text');
        if (paste.length === 6 && /^\d+$/.test(paste)) {
            paste.split('').forEach((char, idx) => {
                if (boxes[idx]) boxes[idx].value = char;
            });
            boxes[5].focus();
            e.preventDefault();
        }
    });
</script>

</body>
</html>
