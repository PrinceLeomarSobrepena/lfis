<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$result = $conn->query("
    SELECT a.*, l.status AS staff_status
    FROM staff a
    LEFT JOIN (
        SELECT staffId, status 
        FROM staffLogs 
        WHERE id IN ( SELECT MAX(id)  FROM staffLogs  GROUP BY staffId)
    ) l ON a.id = l.staffId
");

// TOTAL STAFF
$totalStaff = $conn->query("
    SELECT COUNT(*) as total
    FROM staff
")->fetch_assoc()['total'];

// ONLINE STAFF
$onlineStaff = $conn->query("
    SELECT COUNT(*) AS total
    FROM staff a
    INNER JOIN (
        SELECT staffId, status
        FROM staffLogs
        WHERE id IN (
            SELECT MAX(id)
            FROM staffLogs
            GROUP BY staffId
        )
    ) l ON a.id = l.staffId
    WHERE LOWER(l.status) = 'online'
")->fetch_assoc()['total'];

// ACTIVE STAFF (hindi naka-ban)
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
/* ====================== SPINNER ===================== */
.spinner-wrapper{
    background-color: rgba(255,255,255,0.9);
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
}

.spinner-border{
    height: 60px;
    width: 60px;
}


/* ====================== STAT STRIP ===================== */
.staff-stat-card{
    display:flex;
    align-items:center;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:16px 18px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
    gap:14px;
    height:100%;
}

.staff-stat-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:42px;
    height:42px;
    font-size:18px;
    border-radius:11px;
    flex-shrink:0;
}

.staff-stat-value{
    font-weight:800;
    font-size:21px;
    letter-spacing:-.5px;
    line-height:1;
}

.staff-stat-label{
    font-weight:700;
    font-size:11.5px;
    color: #7C8A85;
    margin-top:4px;
}

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
    background: #fff;
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


/* ====================== RESET BUTTON ===================== */
.btn-reset{
    display:flex;
    align-items:center;
    white-space:nowrap;
    background: #fff;  /*linear-gradient(135deg, #198754, #147a49);    #0F1B2D   */
    border:1px solid #E7ECE9;
    color: #4B5A54;
    /* border:none; */
    padding:10px 20px;
    border-radius:10px;
    font-weight:700;
    font-size:13.5px;
    gap:8px;
}

.btn-reset:hover{
    background:#f5f6fa;
}

/* .btn-reset i {
    font-size: 15px;
    -webkit-text-stroke: 0.7px currentColor;
} */

.btn-reset i {
    font-size: 15px;
    -webkit-text-stroke: 0.7px currentColor;
    display: inline-block;
}

.btn-reset i.spin {
    animation: resetSpin 0.5s ease;
}

@keyframes resetSpin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(-360deg);
    }
}


/* ====================== STAFF CARDS ===================== */
.staff-card{
    position:relative;
    height:100%;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:30px 20px;
    transition:.2s ease;
}

.staff-card:hover{
    transform:translateY(-3px);
    box-shadow:0 14px 30px rgba(20,60,40,0.08);
    border-color: #dcefe4;
}

.staff-status-dot{
    display:flex;
    align-items:center;
    position:absolute;
    top:15px;
    right:20px;
    font-size:10.5px;
    font-weight:800;
    gap:6px;
}

.dot-online{width:7px;height:7px;border-radius:50%;background:#198754;}
.dot-offline{width:7px;height:7px;border-radius:50%;background:#C7CFCB;}

.staff-head{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:16px;
}

.staff-avatar{
    object-fit:cover;
    width:54px;
    height:54px;
    border-radius:14px;
    border:2px solid #E7ECE9;
}

.staff-name{
    font-weight:800;
    font-size:14.5px;
    color: #0F1B2D;
    margin-bottom:2px;
}

.staff-status{
    display:inline-block;
    font-size:11.5px;
    font-weight:800;
    padding:3px 10px;
    border-radius:20px;
}

.staff-n{background: #FFF4E5;color: #B8860B;}
.staff-active{background: #E8F7EF;color: #198754;}

.staff-detail{
    display:flex;
    align-items:center;
    font-weight:600;
    font-size:12px;
    color:#7C8A85;
    gap:8px;
    margin-bottom:8px;
}

.staff-detail i{
    width:16px;
    font-size:12px;
    color: #8CA298;
}

/* ====================== DIVIDER ===================== */
.staff-divider{
    border:none;
    border-top:1px solid #c5c5c5;
    margin:16px 0;
}

/* ====================== ACTION ===================== */
.staff-actions{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap:8px;
    margin-top:16px;
}
.staff-actions > * { width: 100%;}
.staff-actions .staff-btn { width: 100%;}

.staff-btn{
    flex:1;
    text-align:center;
    padding:8px 1px;
    border-radius:9px;
    font-size:12px;
    font-weight:700;
    border:1px solid #E7ECE9;
    background:#fff;
    color:#4B5A54;
    text-decoration:none;
    cursor:pointer;
}

.staff-btn:hover{background:#f5f6fa;}

.staff-btn.danger:hover{
    background:#FCEAED;
    color:#E1596B;
    border-color:#f5c7ce;
}

/* ====================== EMPTY STATE ===================== */
.staff-empty{
    text-align:center;
    padding:40px 20px;
    color:#7C8A85;
    font-weight:600;
    width:100%;
}
.staff-empty i{
    font-size:28px;
    color:#BFCBC5;
}

/* ====================== MEDIA ===================== */
@media(max-width:992px){
        .toolbar{
        flex-direction:column;
        align-items:stretch;
    }
}

</style>
</head>
<body>

    <!-- Spinner -->
    <div class="spinner-wrapper" id="spinner">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>


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
        <a href="adminStaffList.php" class="active"> <i class="bi bi-people"></i> Staff </a>
        <a href="adminLostFoundList.php"> <i class="bi bi-journal-text"></i> List </a>
        <a href="adminLostFoundMatches.php"> <i class="bi bi-layers"></i> Match Items </a>
        <a href="adminLostFoundItems.php"> <i class="bi bi-search"></i> Found Items </a>
        <a href="adminLostFoundAuditTrail.php"> <i class="bi bi-clipboard-data"></i> Audit Trail </a>
        <a href="adminLostFoundStatistic.php"><i class="bi bi-graph-up-arrow"></i> Statistic </a>
        <a href="adminLostFoundCalendar.php"> <i class="bi bi-calendar-event"></i> Calendar </a>
        <a href="adminChangePassword.php"> <i class="bi bi-lock" style="display: inline-block; transform: scaleX(1.4);"></i> Change Password </a>
        <a href="adminLogout.php" class="logout-btn"> <i class="bi bi-box-arrow-right"></i> Logout </a>
    </div>


    <!-- NAVBAR -->
    <nav class="navbar-custom shadow-sm">
        <div class="navbar-left">
            <img src="../uploads/SCHOOL.jpg" class="profile-img">

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
            <div class="page-heading">Staff</div>
            <div class="page-subheading">Everyone with access to manage the system.</div>
        </div>

        <!-- STAT STRIP -->
        <div class="row g-2 mb-3">
            <div class="col-xl-3 col-lg-3 col-md-12  ">
                <div class="staff-stat-card">
                    <div class="staff-stat-icon" style="background:#E8F7EF;color:#198754;"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="staff-stat-value"><?= $totalStaff ?></div>
                        <div class="staff-stat-label">Total Staff</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-12">
                <div class="staff-stat-card">
                    <div class="staff-stat-icon" style="background:#EAF1FF;color:#3366CC;"><i class="bi bi-circle-fill" style="font-size:12px;"></i></div>
                    <div>
                        <div class="staff-stat-value" id="onlineStaffCount"><?= $onlineStaff ?></div>
                        <div class="staff-stat-label">Online Now</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-6">
                <div class="staff-stat-card">
                    <div class="staff-stat-icon" style="background:#F1EAFC;color:#7C4DFF;"><i class="bi bi-person-check"></i></div>
                    <div>
                        <div class="staff-stat-value"><?= $activeStaff ?></div>
                        <div class="staff-stat-label">Total Active</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-12">
                <div class="staff-stat-card">
                    <div class="staff-stat-icon" style="background:#FFF4E5;color:#B8860B;"><i class="bi bi-slash-circle"></i></div>
                    <div>
                        <div class="staff-stat-value"><?= $bannedStaff ?></div>
                        <div class="staff-stat-label">Ban</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="staffSearchInput" placeholder="Search by name, email, or contact...">
            </div>
            <select class="filter-select" id="activeFilter">
                <option value="">All Active</option>
                <option value="Active">Active</option>
                <option value="Banned">Ban</option>
            </select>
            <select class="filter-select" id="onlineFilter">
                <option value="">All Status</option>
                <option value="Online">Online</option>
                <option value="Offline">Offline</option>
            </select>

            <!-- reset -->
            <button class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset </button>

           <a href="adminStaffCreate.php" class="btn-create"><i class="bi bi-plus-lg"></i> Add Staff </a>
        </div>

        <div class="results-count" style="font-size:12.5px;color:#7C8A85;font-weight:700;margin-bottom:12px;">
            Showing <b id="staffResultsCount" style="color:#0F1B2D;"><?= $result->num_rows ?></b> out of <b style="color:#0F1B2D;"><?= $result->num_rows ?></b> staff
        </div>

            <!-- STAFF CARDS -->
        <div class="row g-3" > <!-- id="staffGrid" -->
            <?php if ($result->num_rows === 0): ?>
                <div class="staff-empty">
                    <i class="bi bi-people"></i>
                    <div class="mt-2">Wala pang naitalang staff.</div>
                </div>
            <?php else: ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <?php
                    $fullName = trim($row['firstName'] . ' ' . $row['lastName']);
                    $staffStatus = strtolower($row['staff_status'] ?? 'offline');
                    $activeState = $row['banned'] ? 'Banned' : 'Active';
                    $onlineState = ($staffStatus === 'online') ? 'Online' : 'Offline';
                ?>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 staff-col"
                     id="staff-col-<?= $row['id'] ?>"
                     data-name="<?= htmlspecialchars(strtolower($fullName)) ?>"
                     data-email="<?= htmlspecialchars(strtolower($row['email'])) ?>"
                     data-contact="<?= htmlspecialchars(strtolower($row['contact'])) ?>"
                     data-active="<?= $activeState ?>"
                     data-online="<?= $onlineState ?>"
                     data-date="<?= htmlspecialchars(strtolower(date('F d, Y', strtotime($row['created_at'])))) ?>">
                    <div class="staff-card">
                        <div class="staff-status-dot" id="status-<?= $row['id'] ?>">
                            <?php if ($staffStatus === 'online'): ?>
                                <span class="dot-online"></span>
                            <?php else: ?>
                                <span class="dot-offline"></span>
                            <?php endif; ?>
                        </div>
                        <div class="staff-head">
                            <img src="<?= $row['image'] ? '../uploads/' . htmlspecialchars($row['image']) : '' ?>" class="staff-avatar">
                            <div>
                                <div class="staff-name"><?= htmlspecialchars($fullName) ?></div>
                                 <?= $row['banned']
                                    ? '<span class="staff-status staff-n">Banned</span>'
                                    : '<span class="staff-status staff-active">Active</span>' 
                                ?>
                            </div>
                        </div>
                        <div class="staff-detail"><i class="bi bi-envelope"></i><?= htmlspecialchars($row['email']) ?></div>
                        <div class="staff-detail"><i class="bi bi-phone"></i><?= htmlspecialchars($row['contact']) ?></div>
                        <!-- <div class="staff-detail"><i class="bi bi-calendar-plus"></i> Created: <?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['created_at']))) ?></div> -->

                        <hr class="staff-divider">

                        <div class="staff-actions">
                            <a href="adminStaffUpdate.php?id=<?= $row['id'] ?>" class="staff-btn"><i class="bi bi-pencil"></i> Edit</a>

                            <form action="adminStaffResetLoginPassword.php" method="POST" onsubmit="return confirm('Reset this staff password to the default password?');">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <button type="submit" class="staff-btn"><i class="bi bi-key" style="font-size:16px; transform: rotate(130deg); display:inline-block;"></i> Reset Password</button>
                            </form>

                            <form method="post" action="adminStaffBan.php" style="flex:1;"
                                onsubmit="return confirm('<?= $row['banned'] ? 'Unban' : 'Ban' ?> this staff?');">

                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                <button type="submit" class="staff-btn" style="width:100%;">
                                    <i class="bi bi-slash-circle"></i>
                                    <?= $row['banned'] ? 'Unban' : 'Ban' ?>
                                </button>
                            </form>


                            <form action="adminStaffDelete.php" method="POST" style="flex:1;" onsubmit="return confirm('Delete this staff?')">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <button type="submit" class="staff-btn danger" style="width:100%;"><i class="bi bi-trash"></i> Delete</button>
                            </form>
                        </div>

                    </div>
                </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- LAGING NAKA-RENDER, hidden by default; para sa "walang tumugma sa filter" -->
        <div id="staffNoResults" class="staff-empty" style="display:none;">
            <i class="bi bi-search"></i>
            <div class="mt-2">No staff match your filter.</div>
        </div>

    </div>


<!-- FILTER LOGIC -->
<script>
const staffSearchInput = document.getElementById('staffSearchInput');
const activeFilter = document.getElementById('activeFilter');
const onlineFilter = document.getElementById('onlineFilter');
const staffCols = document.querySelectorAll('.staff-col');
const staffNoResults = document.getElementById('staffNoResults');
const staffResultsCount = document.getElementById('staffResultsCount');

function applyStaffFilters() {
    const term = staffSearchInput.value.trim().toLowerCase();
    const active = activeFilter.value;
    const online = onlineFilter.value;

    let visibleCount = 0;

    staffCols.forEach(col => {
        const matchesSearch = !term ||
            col.dataset.name.includes(term) ||
            col.dataset.email.includes(term) ||
            col.dataset.contact.includes(term) ||
            col.dataset.date.includes(term);

        const matchesActive = !active || col.dataset.active === active;
        const matchesOnline = !online || col.dataset.online === online;

        const show = matchesSearch && matchesActive && matchesOnline;
        col.style.display = show ? '' : 'none';

        if (show) visibleCount++;
    });

    staffNoResults.style.display = (visibleCount === 0 && staffCols.length > 0) ? 'block' : 'none';
    if (staffResultsCount) staffResultsCount.textContent = visibleCount;
}

staffSearchInput.addEventListener('input', applyStaffFilters);
activeFilter.addEventListener('change', applyStaffFilters);
onlineFilter.addEventListener('change', applyStaffFilters);

//reest button
const resetButton = document.querySelector('.btn-reset');

resetButton.addEventListener('click', function() {
    const resetIcon = this.querySelector('i');
    resetIcon.classList.remove('spin');
    void resetIcon.offsetWidth;
    resetIcon.classList.add('spin');

    staffSearchInput.value = '';
    activeFilter.value = '';
    onlineFilter.value = '';

    applyStaffFilters();
});
</script>


<!-- Live online/offline refresh -->
<script>
function updateAdminStatuses() {
    fetch('getStaffStatuses.php')
        .then(res => res.json())
        .then(data => {
            Object.entries(data).forEach(([id, status]) => {
                const cell = document.getElementById('status-' + id);
                const isOnline = status.toLowerCase() === 'online';

                if (cell) {
                    cell.innerHTML = isOnline
                        ? "<span class='dot-online'></span> Online"
                        : "<span class='dot-offline'></span> Offline";
                }

                // BAGO: i-sync ang data-online ng staff-col para tumugma ang "Online/Offline" filter
                const col = document.getElementById('staff-col-' + id);
                if (col) {
                    col.dataset.online = isOnline ? 'Online' : 'Offline';
                }
            });

            // BAGO: i-reapply ang filter para makita agad kung may pumasok/lumabas
            if (typeof applyStaffFilters === 'function') {
                applyStaffFilters();
            }
        })
        .catch(err => console.error('Failed to fetch statuses:', err));
}
setInterval(updateAdminStatuses, 5000);
</script>


<!-- Live online/offline refresh sa KPI -->
<script>
function updateOnlineStaffCount() {
    fetch('getStaffStatuses.php')
        .then(res => res.json())
        .then(data => {

            let onlineCount = 0;

            Object.values(data).forEach(status => {
                if (status.toLowerCase() === 'online') {
                    onlineCount++;
                }
            });

            const onlineStaffCount = document.getElementById('onlineStaffCount');

            if (onlineStaffCount) {
                onlineStaffCount.textContent = onlineCount;
            }
        })
        .catch(err => console.error('Failed to fetch online staff count:', err));
}

updateOnlineStaffCount();

setInterval(updateOnlineStaffCount, 5000);
</script>


<!-- Spinner -->
<script>
const spinner = document.getElementById('spinner');

document.querySelectorAll('form[action="adminStaffResetLoginPassword.php"]').forEach(form => {
    form.addEventListener('submit', function () {
        spinner.style.display = 'flex';
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

document.getElementById("overlay")
.addEventListener("click", function(){
    document.getElementById("sidebar")
    .classList.remove("show");

    this.classList.remove("show");
});

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
    setInterval(() => {
        fetch('../middleware/adminAutoLogout.php')
            .then(res => res.json())
            .then(data => {
                if (!data.active) {
                    window.location.href = 'adminLogin.php';
                }
            })
            .catch(err => console.error(err));
    }, 900000);
</script>

</body>
</html>