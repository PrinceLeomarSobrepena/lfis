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

// EXPIRED ITEMS (pending/matched na 8+ days old)
$expired = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found
    WHERE is_claimed = 0
      AND is_resolved = 0
      AND DATEDIFF(CURDATE(), created_at) > 7
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
    <title>Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../public/css/admin.css">

<!-- font style -->
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ====================== KPI ===================== */
.kpi-card{
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:18px 20px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
    height:100%;
    transition:.2s ease;
}

.kpi-card:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 26px rgba(20,60,40,0.08);
}

.kpi-icon{
    width:42px;
    height:42px;
    border-radius:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
}

.kpi-value{
    font-size:26px;
    font-weight:800;
    letter-spacing:-.5px;
    margin:12px 0 2px;
}

.kpi-label{
    font-size:12.5px;
    color:#7C8A85;
    font-weight:700;
}

.kpi-delta{
    font-size:11.5px;
    font-weight:700;
    padding:3px 8px;
    border-radius:20px;
    display:inline-flex;
    align-items:center;
    gap:4px;
    margin-top:10px;
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

        <a href="adminDashboard.php" class="active"> <i class="bi bi-speedometer2"></i> Dashboard </a>
        <a href="adminStaffList.php"> <i class="bi bi-people"></i> Staff </a>
        <a href="adminLostFoundList.php"> <i class="bi bi-journal-text"></i> List </a>
        <a href="adminLostFoundMatches.php"> <i class="bi bi-layers"></i> Match Items </a>
        <a href="adminLostFoundItems.php"> <i class="bi bi-search"></i> Found Items </a>
        <a href="adminLostFoundAuditTrail.php"> <i class="bi bi-clipboard-data"></i> Audit Trail </a>
        <a href="adminLostFoundStatistic.php"> <i class="bi bi-graph-up-arrow"></i> Statistic </a>
        <a href="adminLostFoundCalendar.php"> <i class="bi bi-calendar-event"></i> Calendar </a>
        <a href="adminLostFoundIArchieve.php">Archive</a>
        <a href="adminChangePassword.php"> <i class="bi bi-lock" style="display: inline-block; transform: scaleX(1.4);"></i> Change Password </a>
        <a href="adminLogout.php" class="logout-btn"> <i class="bi bi-box-arrow-right"></i> Logout </a>
    </div>


    <!-- NAVBAR -->
    <nav class="navbar-custom shadow-sm">
        <div class="navbar-left">
            <img src="../uploads/SCHOOL.jpg" class="profile-img ">  <!--  d-lg-none -->

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

    <!-- KPI -->
    <div class="main-content">
         <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mt-4 mb-3">
            <div>
                <div class="page-heading">Welcome back, Admin 👋</div>
                <div class="page-subheading">Here's what's happening across campus today.</div>
            </div>

            <a href="adminLostFoundCreate.php" class="btn btn-success d-flex align-items-center gap-2" style="border-radius:10px; font-weight:700; font-size:13.5px; padding:10px 18px; border:none; text-decoration:none;">
                <i class="bi bi-plus-lg"></i> Report an Item
            </a>
        </div>
       
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

            <!-- EXPIRED -->
<div class="col-md-3">
    <div class="card shadow-sm border-0 bg-danger text-white">
        <div class="card-body text-center">
            <h6>Expired Items</h6>
            <h2><?= $expired ?></h2>
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