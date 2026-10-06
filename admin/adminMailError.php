<?php
session_start();
$message = $_SESSION['mail_error'] = "Oops! We couldn’t send your OTP. Please try again.";// ?? "Cannot connect to the server. Please check your internet.
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>No Internet / OTP Error</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
<div class="text-center">
    <h3 class="text-danger">Oops!</h3>
    <p><?= htmlspecialchars($message) ?></p>
    <a href="adminLogin.php" class="btn btn-primary">Try Again</a>
</div>
</body>
</html>
