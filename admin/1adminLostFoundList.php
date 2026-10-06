<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';
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
/* ====================== TOOLBAR ===================== */
.toolbar{
    display:flex;
    flex-wrap:wrap;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:16px;
    align-items:center;
    gap:12px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
      margin-bottom:16px;
}

.search-box{
    flex:1;
    min-width:220px;
    position:relative;
}

.search-box i{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color: #8CA298;
    font-size:14px;
}

.search-box input{
    width:100%;
    font-size:13.5px;
    font-weight:600;
    color: #0F1B2D;
    /background: #fff; /* #f9fbfa */
    border:1px solid #E7ECE9;
    border-radius:10px;
    padding:10px 14px 10px 38px;
}

.search-box input:focus{
    outline:none;
    border-color: #198754;
    background: #fff;
}

.filter-select{
    min-width:150px;
    font-size:13px;
    font-weight:700;
    color: #4B5A54;
    background: #fff;  /* #f9fbfa */
    border:1px solid #E7ECE9;
    border-radius:10px;
    padding:10px 14px;
}

.filter-select:focus{
    outline:none;
    border-color: #198754;
}

.btn-create{
    display:flex;
    white-space:nowrap;
    align-items:center;
    text-decoration:none;
    font-weight:700;
    font-size:13.5px;
    color: #fff;
    background:linear-gradient(135deg, #198754, #147a49);
    border:none;
    padding:10px 20px;
    border-radius:10px;
    gap:8px;
    
}

.btn-create:hover{
    color:#fff;
    background:linear-gradient(135deg, #157347, #10633b);
}

/* ====================== TABLE CARD ===================== */
table {
    white-space: nowrap;
}
.table-card{
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
    overflow:hidden;
}

.table-responsive-custom{
    overflow-x:auto;
}

.item-table{
    width:100%;
    border-collapse:collapse;
    min-width:900px;
}

.item-table thead th{
    font-weight:800;
    font-size:11px;
    color: #7C8A85;
    background: #FAFBFA;
    border-bottom:1px solid #E7ECE9;
    text-transform:uppercase;
    text-align:left;
    letter-spacing:.4px;
    padding:14px 16px;
    white-space:nowrap;
}

.item-table tbody td{
    font-weight:600;
    font-size:13px;
    color: #0F1B2D;
    border-bottom:1px solid #F0F2F1;
    padding:14px 16px; 
    vertical-align:middle;
}

.item-table tbody tr:last-child td{
    border-bottom:none;
}

.item-table tbody tr:hover{
    background:#FAFEFC;
}

.row-check{
    width:16px;
    height:16px;
    accent-color:#198754;
}

/* ====================== TYPE ===================== */
.badge-type{
    display:inline-block;
    font-weight:800;
    font-size:12px; /*10.5px*/
    border-radius:20px; 
    padding:5px 11px;
}

.type-found-item{background: #F1EAFC;color: #7C4DFF;}
.type-lost-report{background: #FCEAED;color: #E1596B;}


/* ====================== BADGE ===================== */
.badge-status{
    display:inline-block;
    font-weight:800;
    font-size:12px; /*10.5px*/
    border-radius:20px;
    padding:5px 11px; 
}

.status-found{background: #E8F7EF;color: #198754;}
.status-matched{background: #FFF4E5;color: #B8860B;}
.status-claimed{background: #EAF1FF;color: #3366CC;}

/* ====================== ACTION ===================== */
.row-actions{
    display:flex;
    gap:8px; /*6px*/
}

.action-btn{
    display:flex;
    align-items:center;
    justify-content:center;
    width:35px; /*30px*/
    height:35px;/*30px*/
    font-size:15px; /*12.5px*/
    color: #7C8A85;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:8px;
    cursor:pointer;
    transition:.15s;
}

.action-btn:hover{
    background: #f5f6fa;
}

.action-btn.claim:hover{color: #198754;border-color: #c9e9d7;}
.action-btn.view:hover{color: #198754;border-color: #c9e9d7;}
.action-btn.edit:hover{color: #B8860B;border-color: #f2e0b8;}
.action-btn.delete:hover{color: #E1596B;border-color: #f5c7ce;background: #FCEAED;}

/* ====================== TABLE FOOTER ===================== */
.table-footer{
    display:flex;
     flex-wrap:wrap;
    justify-content:space-between;
    align-items:center;
    padding:14px 18px;
    gap:10px;
}

.table-footer-info{
    font-weight:700;
    font-size:12px;
    color: #7C8A85;
}

.table-footer-info b{
    color: #0F1B2D;
}

.pagination-custom{
    display:flex;
    gap:6px;
}

.page-btn{
    width:32px;
    height:32px;
    border-radius:8px;
    border:1px solid #E7ECE9;
    background: #fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:12.5px;
    font-weight:700;
    color: #4B5A54;
    cursor:pointer;
}

.page-btn.active{
    color: #fff;
    background: #0F1B2D;
    border-color: #0F1B2D;
}

.page-btn:hover:not(.active){
    background: #f5f6fa;
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
        <a href="adminLostFoundCreate.php"> <i class="bi bi-plus-lg"></i> Create </a>
        <a href="adminLostFoundList.php" class="active"> <i class="bi bi-journal-text"></i> List </a>
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
            <img src="../uploads/SCHOOL.jpg" class="profile-img">  <!--  d-lg-none -->

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
            <div class="page-heading">Log a Lost or Found Item</div>
            <div class="page-subheading">Record item details so it can be searched, matched, and claimed.</div>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search by item name, location, or keyword...">
            </div>
            <select class="filter-select">
                <option>All Type</option>
                <option>Found</option>
                <option>Lost</option>
    
            </select>
            <select class="filter-select">
                <option>All Status</option>
                <option>Claimed</option>
                <option>Found</option>
                <option>Matched</option>
                <option>Resolved</option>
            </select>
           <a href="1adminLostFoundCreate.php" class="btn-create"><i class="bi bi-plus-lg"></i> Add lost found </a>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <div class="table-responsive-custom">
                <table class="item-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="row-check"></th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Handled By</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                              <th>Actions</th>
                                <th style="text-align:right;">Actions</th>
                                  <th style="text-align:right;">Actions</th>
                                    <th style="text-align:right;">Actions</th>
                                      <th style="text-align:right;">Actions</th>
                                        <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        <tr>
                            <td><input type="checkbox" class="row-check"></td>
                            <td>Black Wallet</td>
                            <td><span class="badge-type type-found-item">Found Item</span></td>
                            <td>Wallets & Bags</td>
                            <td>Library, 2F</td>
                            <td>Aug 15, 2026</td>
                            <td>J. Ramirez</td>
                            <td><span class="badge-status status-found">Found</span></td>
                            <td>
                                <div class="row-actions justify-content-end">
                                    <div class="action-btn claim"><i class="bi bi-tag"></i></div>
                                    <div class="action-btn view"><i class="bi bi-eye"></i></div> 
                                    <div class="action-btn edit"><i class="bi bi-pencil"></i></div>
                                    <div class="action-btn delete"><i class="bi bi-trash"></i></div>
                                </div>
                            </td>
                            <td style="color: #7C8A85;">N/A</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div class="table-footer-info">Showing <b>1–8</b> of <b>142</b> items</div>
                <div class="pagination-custom">
                    <div class="page-btn"><i class="bi bi-chevron-left"></i></div>
                    <div class="page-btn active">1</div>
                    <div class="page-btn">2</div>
                    <div class="page-btn">3</div>
                    <div class="page-btn">...</div>
                    <div class="page-btn">18</div>
                    <div class="page-btn"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>

        </div>
    </div>


<script>
/* SELECT ALL CHECKBOX */
document.querySelector("thead .row-check").addEventListener("change", function(){
    document.querySelectorAll("tbody .row-check").forEach(cb => cb.checked = this.checked);
});

/* PAGINATION VISUAL */
document.querySelectorAll(".page-btn").forEach(btn=>{
    btn.addEventListener("click", function(){
        if(this.innerText.match(/^\d+$/)){
            document.querySelectorAll(".page-btn").forEach(b=>b.classList.remove("active"));
            this.classList.add("active");
        }
    });
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