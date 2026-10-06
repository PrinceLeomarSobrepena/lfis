<?php
include '../middleware/staffMiddleware.php';
include '../config/db.php';

// LOST
$lost = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found
    WHERE status = 'Lost'
")->fetch_assoc()['total'];

// FOUND
$found = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found
    WHERE status = 'Found'
")->fetch_assoc()['total'];

// TOTAL MATCHES
$matches = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found_matches
")->fetch_assoc()['total'];

// RESOLVED MATCHES
$resolved = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found_matches
    WHERE is_resolved = 1
")->fetch_assoc()['total'];

// ACTIVE MATCHES
$active = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found_matches
    WHERE is_resolved = 0
")->fetch_assoc()['total'];

// CLAIMED ITEMS
$claimed = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found
    WHERE is_claimed = 1
")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>

<title>Staff Dashboard</title>
</head>
<body>
    <h5><?= $_SESSION['staff']['firstName'] . ' ' . $_SESSION['staff']['lastName'] ?></h5>
    <p><?= $_SESSION['staff']['role'] ?></p>

<a href="staffLostFoundList.php">Lost and Found List</a><br>
<a href="staffLostFoundListCard.php">Card View</a><br>
<a href="staffLostFoundClaim.php">Claim</a><br><br>

<a href="staffLostFoundCalendar.php">Calendar</a><br><br>

<a href="staffLostFoundMatches.php">Matches</a><br><br>

<a href="staffChangePassword.php" class="btn btn-danger">staffChangePassword</a>

<a href="staffLogout.php" class="btn btn-danger">Logout</a>

<div class="container py-5">

    <h2 class="mb-4">Staff Dashboard</h2>

    <div class="row g-3">

        <!-- LOST -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted">Lost Items</h6>
                    <h2><?= $lost ?></h2>
                </div>
            </div>
        </div>

        <!-- FOUND -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted">Found Items</h6>
                    <h2><?= $found ?></h2>
                </div>
            </div>
        </div>

        <!-- CLAIMED -->
        <div class="col-md-3">
            <div class="card border-1">
                <div class="card-body text-center">
                    <h6>Claimed Items</h6>
                    <h2><?= $claimed ?></h2>
                </div>
            </div>
        </div>

        <!-- MATCHES -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Matches</h6>
                    <h2><?= $matches ?></h2>
                </div>
            </div>
        </div>

        <!-- RESOLVED -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body text-center">
                    <h6>Resolved Matches</h6>
                    <h2><?= $resolved ?></h2>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mt-3">

        <!-- ACTIVE -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-warning">
                <div class="card-body text-center">
                    <h6>Active Matches</h6>
                    <h2><?= $active ?></h2>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    setInterval(() => {
        fetch('../middleware/staffAutoLogout.php')
            .then(res => res.json())
            .then(data => {
                if (!data.active) {
                    window.location.href = 'staffLogin.php';
                }
            })
            .catch(err => console.error(err));
    }, 900000);
</script>

</body>
</html>