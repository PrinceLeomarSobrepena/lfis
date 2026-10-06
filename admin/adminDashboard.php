<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

// ALL ITEMS
$allItems = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found
")->fetch_assoc()['total'];

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

// RESOLVED MATCHES (IMPORTANT KPI)
$resolved = $conn->query("
    SELECT COUNT(*) as total 
    FROM lost_found_matches 
    WHERE is_resolved = 1
")->fetch_assoc()['total'];

// ACTIVE (unresolved)
$active = $conn->query("
    SELECT COUNT(*) as total 
    FROM lost_found_matches 
    WHERE is_resolved = 0
")->fetch_assoc()['total'];

// CLAIMED ITEMS ✅ NEW
$claimed = $conn->query("
    SELECT COUNT(*) as total 
    FROM lost_found 
    WHERE is_claimed = 1
")->fetch_assoc()['total'];
// SELECT COUNT(*) as total    pwedi din ganitong format
// FROM lost_found 
// WHERE is_claimed = 1


// TOTAL STAFF
$totalStaff = $conn->query("
    SELECT COUNT(*) as total
    FROM staff
")->fetch_assoc()['total'];

// ACTIVE STAFF
$activeStaff = $conn->query("
    SELECT COUNT(*) as total
    FROM staff
    WHERE banned = 0
")->fetch_assoc()['total'];

// BANNED STAFF
$bannedStaff = $conn->query("
    SELECT COUNT(*) as total
    FROM staff
    WHERE banned = 1
")->fetch_assoc()['total'];

// DISPOSED FOUND ITEMS
$disposedFound = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found_deletions
    WHERE status = 'Found'
      AND was_disposed = 1
")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
<title>Document</title>

</head>
<body>
    <h5><?= $_SESSION['admin']['firstName'] . ' ' . $_SESSION['admin']['lastName'] ?></h5>
    <p><?= $_SESSION['admin']['role'] ?></p>

    <a href="adminChangePassword.php" class="btn btn-warning">
        Change Password
    </a><br>


    <a href="adminLostFoundList.php">lost and found list</a> <br>
    <a href="adminLostFoundListCard.php">card</a><br>
    <a href="adminLostFoundAuditTrail.php">Autid Trail</a><br><br>

     <a href="adminLostFoundItems.php">Found items</a><br><br>

    <a href="adminLostFoundCalendar.php">calendar</a><br><br>

    <a href="adminLostFoundMatches.php">match</a><br>
    <a href="adminStaffList.php">Staff
    </a><br><br>
    <a href="adminLogout.php">Logout</a>

<!-- KPI -->
<div class="container py-5">

    <h2 class="mb-4">Admin Dashboard</h2><br>
     <h2 class="mb-4">Lost and Found Staff Verification</h2>
     <p>Category	Background	Text/Icon
Cash	#DCFCE7	#15803D
Gadget	#DBEAFE	#1D4ED8
Document	#FEF3C7	#B45309
Other	#EDE9FE	#6D28D9</p>

    <div class="row g-3">

            <!-- ALL ITEMS -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    <h6 class="text-muted">All Items</h6>
                    <h2><?= $allItems?></h2>
                </div>
            </div>
        </div>

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


        <!-- TOTAL STAFF -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-info text-white">
                <div class="card-body text-center">
                    <h6>Total Staff</h6>
                    <h2><?= $totalStaff ?></h2>
                </div>
            </div>
        </div>

        <!-- ACTIVE STAFF -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body text-center">
                    <h6>Active Staff</h6>
                    <h2><?= $activeStaff ?></h2>
                </div>
            </div>
        </div>

        <!-- BANNED STAFF -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-danger text-white">
                <div class="card-body text-center">
                    <h6>Banned Staff</h6>
                    <h2><?= $bannedStaff ?></h2>
                </div>
            </div>
        </div>

                    <!-- DISPOSED FOUND ITEMS -->
<div class="col-md-3">
    <div class="card shadow-sm border-0">
        <div class="card-body text-center">
            <h6 class="text-muted">Disposed Found Items</h6>
            <h2><?= $disposedFound ?></h2>
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
    // check session every 10 seconds
    setInterval(() => {
        fetch('../middleware/adminAutoLogout.php')
            .then(res => res.json())
            .then(data => {
                if (!data.active) {
                    window.location.href = 'adminLogin.php';
                }
            })
            .catch(err => console.error(err));
    }, 900000); // Every 15 mins and 5 seconds
</script>

</body>
</html>