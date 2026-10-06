<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

// ALL ITEMS
$allItems = $conn->query("SELECT COUNT(*) as total FROM lost_found")->fetch_assoc()['total'];

// Lost Items
$lost = $conn->query("SELECT COUNT(*) as total FROM lost_found  WHERE status = 'Lost'")->fetch_assoc()['total'];

// Found Items
$found = $conn->query("SELECT COUNT(*) as total FROM lost_found  WHERE status = 'Found'")->fetch_assoc()['total'];

//Claimed Items
$claimed = $conn->query("
    SELECT COUNT(*) as total 
    FROM lost_found 
    WHERE is_claimed = 1
")->fetch_assoc()['total'];
// SELECT COUNT(*) as total    pwedi din ganitong format
// FROM lost_found 
// WHERE is_claimed = 1

//Total Matches
$matches = $conn->query("
    SELECT COUNT(*) as total 
    FROM lost_found_matches
")->fetch_assoc()['total'];

//Resolved Matches
$resolved = $conn->query("
    SELECT COUNT(*) as total 
    FROM lost_found_matches 
    WHERE is_resolved = 1
")->fetch_assoc()['total'];

//Active Matches
$active = $conn->query("
    SELECT COUNT(*) as total 
    FROM lost_found_matches 
    WHERE is_resolved = 0
")->fetch_assoc()['total'];

//Disposed Found Items
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
    transform:translateY(-4px);
    box-shadow:0 10px 26px rgba(20,60,40,0.08);
}

.kpi-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:42px;
    height:42px;
    font-size:18px;
    border-radius:11px;
}

.kpi-value{
    font-weight:800;
    font-size:26px;
    letter-spacing:-.5px;
    margin:12px 0 2px;
     color: #4B5A54; /* #0F1B2D */
}

.kpi-label{
    font-weight:700;
    font-size:12.5px;
    color: #7C8A85;
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

.delta-up{background:#E8F7EF;color:#198754;}
.delta-down{background:#FCEAED;color:#E1596B;}
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
            <!-- <h5 class=" d-none d-lg-block">Hi, Admin</h5>
            <img src="https://picsum.photos/200"  class="profile-img d-none d-lg-block"> -->
        </div>
    </nav>

    <!-- Main Content -->
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
       
        <!-- KPI CARDS -->
        <div class="row g-3 mb-3">

            <!-- all Items -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start" style="margin-bottom:-10px">
                        <div class="kpi-icon" style="background:#EAF1FF;color:#3366CC;"><i class="bi bi-collection"></i></div>
                    

                        <div class="d-flex align-items-start justify-content-center" style="height: 50px;">
                            <i class="bi bi-three-dots" style="color: #7C8A85; font-size: 20px;"></i>
                        </div>
                    </div>

                    <div class="kpi-value"><?= $allItems?></div>
                    <div class="kpi-label">All Items</div>
                    <!-- <span class="kpi-delta delta-up"><i class="bi bi-arrow-up-short"></i> 6.4% this month</span> -->
                </div>
            </div>

            <!-- lost -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start" style="margin-bottom:-10px">
                        <div class="kpi-icon" style="background:#FCEAED;color:#E1596B;"><i class="bi bi-question-circle"></i></div>

                        <div class="d-flex align-items-start justify-content-center" style="height: 50px;">
                            <i class="bi bi-three-dots" style="color: #7C8A85; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div class="kpi-value"><?= $lost ?></div>
                    <div class="kpi-label">Lost Items</div>
                    <!-- <span class="kpi-delta delta-down"><i class="bi bi-arrow-up-short"></i> 4 more than last month</span> -->
                </div>
            </div>
                        
            <!-- found -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start" style="margin-bottom:-10px">
                        <div class="kpi-icon" style="background:#E8F7EF;color:#198754;"><i class="bi bi-box-seam"></i></div>

                        <div class="d-flex align-items-start justify-content-center" style="height: 50px;">
                            <i class="bi bi-three-dots" style="color: #7C8A85; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div class="kpi-value"><?= $found ?></div>
                    <div class="kpi-label">Found Items</div>
                    <!-- <span class="kpi-delta delta-up"><i class="bi bi-arrow-up-short"></i> 8.2% this month</span> -->
                </div>
            </div>
                        
            <!-- claimed -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start" style="margin-bottom:-10px">
                        <div class="kpi-icon" style="background:#EAF1FF;color:#3366CC;"><i class="bi bi-check2-circle"></i></div>

                        <div class="d-flex align-items-start justify-content-center" style="height: 50px;">
                            <i class="bi bi-three-dots" style="color: #7C8A85; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div class="kpi-value"><?= $claimed ?></div>
                    <div class="kpi-label">Claimed Items</div>
                    <!-- <span class="kpi-delta delta-up"><i class="bi bi-arrow-up-short"></i> 3.4% this month</span> -->
                </div>
            </div>
                        
            <!-- matches -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start" style="margin-bottom:-10px">
                        <div class="kpi-icon" style="background:#F1EAFC;color:#7C4DFF;"><i class="bi bi-layers"></i></div>

                        <div class="d-flex align-items-start justify-content-center" style="height: 50px;">
                            <i class="bi bi-three-dots" style="color: #7C8A85; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div class="kpi-value"><?= $matches ?></div>
                    <div class="kpi-label">Total Matches</div>
                    <!-- <span class="kpi-delta delta-up"><i class="bi bi-arrow-up-short"></i> 5.1% this month</span> -->
                </div>
            </div>
                        
            <!-- resolved -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start" style="margin-bottom:-10px">
                        <div class="kpi-icon" style="background:#E8F7EF;color:#198754;"><i class="bi bi-check-circle"></i></div>

                        <div class="d-flex align-items-start justify-content-center" style="height: 50px;">
                            <i class="bi bi-three-dots" style="color: #7C8A85; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div class="kpi-value"><?= $resolved ?></div>
                    <div class="kpi-label">Resolved Matches</div>
                    <!-- <span class="kpi-delta delta-up"><i class="bi bi-arrow-up-short"></i> 3.4% this month</span> -->
                </div>
            </div>
                        
            <!-- active -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start" style="margin-bottom:-10px">
                        <div class="kpi-icon" style="background:#FFF4E5;color:#B8860B;"><i class="bi bi-hourglass-split"></i></div>

                        <div class="d-flex align-items-start justify-content-center" style="height: 50px;">
                            <i class="bi bi-three-dots" style="color: #7C8A85; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div class="kpi-value"><?= $active ?></div>
                    <div class="kpi-label">Active Matches</div>
                    <!-- <span class="kpi-delta delta-up"><i class="bi bi-arrow-up-short"></i> 2.1% this month</span> -->
                </div>
            </div>
                        
            <!-- disposedFound -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start" style="margin-bottom:-10px">
                        <div class="kpi-icon" style="background:#FCEAED;color:#E1596B;"><i class="bi bi-trash3"></i></div>

                        <div class="d-flex align-items-start justify-content-center" style="height: 50px;">
                            <i class="bi bi-three-dots" style="color: #7C8A85; font-size: 20px;"></i>
                        </div>
                    </div>
                    <div class="kpi-value"><?= $disposedFound ?></div>
                    <div class="kpi-label">Disposed Found Items</div>
                    <!-- <span class="kpi-delta delta-down"><i class="bi bi-arrow-up-short"></i> 3 more than last month</span> -->
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