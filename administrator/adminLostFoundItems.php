<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* FOUND lang, at hindi pa claimed / resolved */
$result = $conn->query("
SELECT
    lf.*,

    EXISTS (
        SELECT 1
        FROM lost_found_matches m
        JOIN lost_found lf2
            ON lf2.id = CASE
                WHEN m.lost_id = lf.id THEN m.found_id
                ELSE m.lost_id
            END
        WHERE (m.lost_id = lf.id OR m.found_id = lf.id)
        AND lf.is_claimed = 0
        AND lf.is_resolved = 0
        AND lf2.is_claimed = 0
        AND lf2.is_resolved = 0
    ) AS has_match,

    /* CREATED BY */
    ac.firstName AS admin_created_first,
    ac.lastName AS admin_created_last,

    sc.firstName AS staff_created_first,
    sc.lastName AS staff_created_last,

    /* EDITED BY */
    ae.firstName AS admin_edited_first,
    ae.lastName AS admin_edited_last,

    se.firstName AS staff_edited_first,
    se.lastName AS staff_edited_last

FROM lost_found lf

/* CREATED BY */
LEFT JOIN admin ac
    ON ac.id = lf.created_by

LEFT JOIN staff sc
    ON sc.id = lf.created_by

/* EDITED BY */
LEFT JOIN admin ae
    ON ae.id = lf.edited_by

LEFT JOIN staff se
    ON se.id = lf.edited_by

WHERE lf.status = 'Found'
  AND lf.is_claimed = 0
  AND lf.is_resolved = 0

ORDER BY lf.created_at DESC
");
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
    background: #fff;
    border:1px solid #E7ECE9;
    color: #4B5A54;
    padding:10px 20px;
    border-radius:10px;
    font-weight:700;
    font-size:13.5px;
    gap:8px;
}

.btn-reset:hover{
    background:#f5f6fa;
}

.btn-reset i {
    font-size: 15px;
    -webkit-text-stroke: 0.7px currentColor;
    display: inline-block;
}

.btn-reset i.spin {
    animation: resetSpin 0.5s ease;
}

@keyframes resetSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(-360deg); }
}

/* ====================== CHIP TOKEN ===================== */
.status-pills{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin:16px 0 18px;
}

.status-pill{
    font-weight:700;
    font-size:12.5px;
    border:1px solid #E7ECE9;
    background:#fff;
    color:#4B5A54;
    padding:8px 16px;
    border-radius:30px;
    cursor:pointer;
    transition:.2s;
}

.status-pill.active{
    background: #0F1B2D;
    color: #fff;
    border-color: #0F1B2D;
}

.status-pill:hover:not(.active){
    background: #f5f6fa;
}

/* ====================== EMPTY STATE ===================== */
.list-empty{
    text-align:center;
    padding:40px 20px;
    color:#7C8A85;
    font-weight:600;
}

.list-empty i{
    font-size:28px;
    color:#BFCBC5;
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

.item-table thead th i{
    margin-right: 6px;
    font-size: 14.5px;
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

/* ====================== TYPE ===================== */
.badge-type{
    display:inline-block;
    font-weight:800;
    font-size:10.5px;
    border-radius:20px;
    padding:5px 11px;
}

.type-found-item {background: #EAF7EF; color: #198754;}

/* ====================== BADGE ===================== */
.badge-status{
    display:inline-block;
    font-weight:800;
    font-size:10.5px;
    border-radius:20px;
    padding:5px 11px;
}

.status-pending{background: #F1F2F4;color: #6C757D;}
.status-matched{background: #F1EAFC;color: #7C4DFF;}

/* ====================== ACTION ===================== */
.row-actions{
    display:flex;
    gap:8px;
}

.action-btn{
    display:flex;
    align-items:center;
    justify-content:center;
    width:35px;
    height:35px;
    font-size:15px;
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

.action-btn.claim:hover{color: #3366CC;border-color: #EAF1FF;}
.action-btn.edit:hover{color: #B8860B;border-color: #f2e0b8;}
.action-btn.delete:hover{color: #E1596B;border-color: #f5c7ce;background: #FCEAED;}

/* ====================== ACTION STICKY COLUMN ===================== */
.item-table th:last-child,
.item-table td:last-child{
    position: sticky;
    right: 0;
    z-index: 2;
    background: #FAFBFA;
    box-shadow: -4px 0 6px -4px rgba(0,0,0,0.15);
}

.item-table thead th:last-child{
    background: #FAFBFA;
    z-index: 3;
}

.item-table tbody td:last-child{
    background: #fff;
}

.item-table tbody tr:hover td:last-child{
    background: #FAFEFC;
}

@media(max-width:992px){
    .toolbar{
        flex-direction:column;
        align-items:stretch;
    }
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
        <a href="adminLostFoundItems.php" class="active"> <i class="bi bi-search"></i> Found Items </a>
        <a href="adminLostFoundAuditTrail.php"> <i class="bi bi-clipboard-data"></i> Audit Trail </a>
        <a href="adminLostFoundStatistic.php"> <i class="bi bi-graph-up-arrow"></i> Statistic </a>
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
            <div class="page-heading">Found Items</div>
            <div class="page-subheading">Found items that are still unclaimed and ready to be matched or released.</div>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search by item name, location, or keyword...">
            </div>
            <select class="filter-select" id="categoryFilter">
                <option value="">All Categories</option>
                <option value="Cash">Cash</option>
                <option value="Gadget">Gadget</option>
                <option value="Document">Document</option>
                <option value="Other">Other</option>
            </select>
            <button type="button" class="btn-reset" id="resetBtn"><i class="bi bi-arrow-counterclockwise"></i> Reset </button>
            <a href="adminLostFoundCreate.php" class="btn-create"><i class="bi bi-plus-lg"></i> Add lost found </a>
        </div>

        <!-- STATUS FILTER PILLS (walang Claimed / Resolved) -->
        <div class="status-pills" id="statusPills">
            <div class="status-pill active" data-status="" style="padding:8px 24px;">All</div>
            <div class="status-pill" data-status="Pending">Pending</div>
            <div class="status-pill" data-status="Matched">Matched</div>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <div class="table-responsive-custom">

            <?php if ($result->num_rows === 0): ?>
                <div class="list-empty">
                    <i class="bi bi-inboxes"></i>
                    <div class="mt-2">No found items available.</div>
                </div>
            <?php else: ?>

                <table class="item-table">
                    <thead>
                        <tr>
                            <th><i class="bi bi-view-list"></i> id</th>
                            <th><i class="bi bi-images"></i> image</th>
                            <th><i class="bi bi-tag-fill"></i> Status</th>
                            <th><i class="bi bi-collection"></i> Category</th>
                            <th><i class="bi bi-card-list"></i> Item Details</th>
                            <th><i class="bi bi-geo-alt"></i> Reported Location</th>
                            <th><i class="bi bi-person-badge"></i> Reporter</th>
                            <th><i class="bi bi-shield-check"></i> Possible Status</th>
                            <th><i class="bi bi-person-plus"></i> Created By</th>
                            <th><i class="bi bi-clock-history"></i> Time Create</th>
                            <th><i class="bi bi-pencil-square"></i> Edited By</th>
                            <th><i class="bi bi-clock"></i> Edited Time</th>
                            <th style="text-align:right;"><i class="bi bi-gear"></i> Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                                /* ================= DISPOSAL ELIGIBILITY (8+ days) ================= */
                                $daysOld = (strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($row['created_at'])))) / 86400;
                                $canDispose = ($daysOld > 7);

                                /* ================= REPORTER INFO ================= */
                                $reporterDetails = '-';

                                if ($row['reporter_role'] === 'Student') {
                                    $reporterDetails =
                                        htmlspecialchars($row['reporter_name']) . '<br>' .
                                        '<span style="color: #7C8A85;">Student ID: ' . htmlspecialchars($row['reporter_id']) . ' • ' .
                                        htmlspecialchars($row['reporter_year']) . ' • ' . htmlspecialchars($row['reporter_department']) . '</span>';
                                }
                                elseif ($row['reporter_role'] === 'Faculty') {
                                    $reporterDetails =
                                        htmlspecialchars($row['reporter_name']) . '<br>' .
                                        '<span style="color: #7C8A85;">Faculty ID: ' . htmlspecialchars($row['reporter_id']) . '</span>';
                                }
                                elseif ($row['reporter_role'] === 'Visitor / Guest') {
                                    $reporterDetails =
                                        htmlspecialchars($row['reporter_name']) . '<br>' .
                                        '<span style="color: #7C8A85;">Visitor / Guest</span>';
                                }
                                elseif ($row['reporter_role'] === 'Anonymous') {
                                    $reporterDetails =
                                        '<b>Anonymous</b><br>' .
                                        '<span style="color: #7C8A85;">Id: ' . htmlspecialchars($row['reporter_id']) . ' • ' .
                                        htmlspecialchars($row['reporter_year']) . ' • ' . htmlspecialchars($row['reporter_department']) . '</span>';
                                }

                                /* ================= ITEM DETAILS ================= */
                                $itemDetails = '-';

                                if ($row['category'] === 'Cash') {
                                    $itemDetails = 'Ammount ₱: ' . number_format($row['cash_amount'], 2);
                                }
                                elseif ($row['category'] === 'Gadget') {
                                    $itemDetails =
                                        'Type: ' . ($row['gadget_type'] ?? '-') . ' • ' .
                                        'Brand: ' . ($row['gadget_brand'] ?? '-') . '<br>' .
                                        'Color: ' . ($row['gadget_color'] ?? '-') . ' • ' .
                                        'Item Description: ' . ($row['gadget_description'] ?? '-');
                                }
                                elseif ($row['category'] === 'Document') {
                                    $itemDetails =
                                        'Type: ' . ($row['document_type'] ?? '-') . '<br>' .
                                        'Name: ' . ($row['document_name'] ?? '-');
                                }
                                elseif ($row['category'] === 'Other') {
                                    $itemDetails =
                                        'Item: ' . ($row['other_title'] ?? '-'). '<br>' .
                                        'Desc : ' . ($row['other_description'] ?? '-');
                                }

                                /* ================= POSSIBLE STATUS =================
                                   Claimed / Resolved ay hindi na lumalabas dito */
                                $possibleStatus = $row['has_match'] ? 'Matched' : 'Pending';
                            ?>

                        <tr data-category="<?= htmlspecialchars($row['category']) ?>" data-status="<?= $possibleStatus ?>">
                            <td class="text-center">
                                #<?= htmlspecialchars($row['id']) ?>
                            </td>

                            <td> <img src="../uploads/<?= htmlspecialchars($row['image']) ?>" width="35" height="35" style="object-fit: cover; cursor: pointer; border-radius: 6px;" onclick="zoomImage(this.src)" alt="Item Image"> </td>

                            <td><span class="badge-type type-found-item"><?= htmlspecialchars($row['status']) ?></span></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= $itemDetails ?></td>
                            <td><?= htmlspecialchars($row['reported_location']) ?></td>
                            <td><?= $reporterDetails ?></td>
                            <td>
                                <span class="badge-status status-<?= strtolower($possibleStatus) ?>"><?= $possibleStatus ?></span>
                            </td>

                            <!-- CREATED BY -->
                            <td>
                                <?php
                                    if (!empty($row['admin_created_first'])) {
                                        echo $row['admin_created_first'] . ' ' . $row['admin_created_last']. ' (Admin)';
                                    }
                                    elseif (!empty($row['staff_created_first'])) {
                                        echo $row['staff_created_first'] . ' ' . $row['staff_created_last']. ' (Staff)';
                                    }
                                    else {
                                        echo '--';
                                    }
                                ?>
                            </td>

                            <!-- CREATED TIME -->
                            <td>
                                <?= date('M d, Y', strtotime($row['created_at'])) ?>
                                <?= date('h:i A', strtotime($row['created_at'])) ?>
                            </td>

                            <!-- EDITED BY -->
                            <td>
                                <?php
                                    if (!empty($row['admin_edited_first'])) {
                                        echo $row['admin_edited_first'] . ' ' . $row['admin_edited_last']. ' (Admin)';
                                    }
                                    elseif (!empty($row['staff_edited_first'])) {
                                        echo $row['staff_edited_first'] . ' ' . $row['staff_edited_last']. ' (Staff)';
                                    }
                                    else {
                                        echo '<span style="color: #7C8A85;">--</span>';
                                    }
                                ?>
                            </td>

                            <!-- EDITED TIME -->
                            <td>
                                <?php if (!empty($row['edited_at'])): ?>
                                    <?= date('M d, Y', strtotime($row['edited_at'])) ?>
                                    <?= date('h:i A', strtotime($row['edited_at'])) ?>
                                <?php else: ?>
                                   <span style="color: #7C8A85;">--</span>
                                <?php endif; ?>
                            </td>

                            <!-- ACTION -->
                            <!-- <td>
                                <div class="row-actions justify-content-end">

                                    <a href="adminLostFoundRelease.php?id=<?= $row['id'] ?>" class="action-btn claim" title="release">
                                        <i class="bi bi-hand-index-thumb"></i>
                                    </a>

                                    <a href="adminLostFoundUpdate.php?id=<?= $row['id'] ?>" class="action-btn edit" title="edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form method="POST" action="adminLostFoundDelete.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="action-btn delete" title="delete" onclick="return confirm('Delete?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                </div>
                            </td> -->

                            <td>
                                <div class="row-actions justify-content-center">
                                    <?php if ($canDispose): ?>
                                        <form method="POST" action="adminLostFoundItemsDelete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to dispose this found item?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="action-btn delete" title="delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#C7CFCB; font-size:11px; font-weight:600;" title="Item must be unclaimed for 8+ days before it can be disposed">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div id="noResults" class="list-empty" style="display:none;">
                    <i class="bi bi-search"></i>
                    <div class="mt-2">No items match your filter.</div>
                </div>
            <?php endif; ?>
            </div>

        </div>
    </div>


    <!-- IMAGE ZOOM MODAL -->
<div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: transparent; border: none;">

            <div class="modal-body text-center position-relative p-0">

                <button
                    type="button"
                    class="btn-close btn-close-white position-absolute"
                    style="right: 10px; top: 10px; z-index: 10;"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

                <img
                    id="zoomedImage"
                    src=""
                    alt="Zoomed Item Image"
                    style="
                        max-width: 90vw;
                        max-height: 85vh;
                        object-fit: contain;
                        border-radius: 12px;
                        box-shadow: 0 10px 40px rgba(0,0,0,.4);
                    "
                >

            </div>
        </div>
    </div>
</div>

<script>
    function zoomImage(src) {
        document.getElementById("zoomedImage").src = src;
        const modal = new bootstrap.Modal(document.getElementById("imageZoomModal"));
        modal.show();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<!-- ========= FILTER ========== -->
<script>
const searchInput    = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const statusPills    = document.querySelectorAll('#statusPills .status-pill');
const resetButton    = document.getElementById('resetBtn');
const noResults      = document.getElementById('noResults');
const rows           = document.querySelectorAll('.item-table tbody tr');

let activeStatus = '';

// i-cache ang searchable text ng bawat row (lahat ng columns, kasama Id na may "#")
rows.forEach(row => {
    row.dataset.search = row.textContent.replace(/\s+/g, ' ').trim().toLowerCase();
});

function applyFilters() {
    const searchTerm = searchInput.value.trim().toLowerCase();
    const category = categoryFilter.value;
    let visibleCount = 0;

    rows.forEach(row => {
        const matchesSearch   = !searchTerm || row.dataset.search.includes(searchTerm);
        const matchesCategory = !category || row.dataset.category === category;
        const matchesStatus   = !activeStatus || row.dataset.status === activeStatus;

        const show = matchesSearch && matchesCategory && matchesStatus;
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
}

searchInput.addEventListener('input', applyFilters);
categoryFilter.addEventListener('change', applyFilters);

statusPills.forEach(pill => {
    pill.addEventListener('click', function () {
        statusPills.forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        activeStatus = this.dataset.status;
        applyFilters();
    });
});

resetButton.addEventListener('click', function () {
    const resetIcon = this.querySelector('i');
    resetIcon.classList.remove('spin');
    void resetIcon.offsetWidth;
    resetIcon.classList.add('spin');

    searchInput.value = '';
    categoryFilter.value = '';
    activeStatus = '';

    statusPills.forEach(p => p.classList.remove('active'));
    document.querySelector('#statusPills .status-pill[data-status=""]').classList.add('active');

    applyFilters();
});
</script>

<!-- ========= Default js ========== -->
<script>
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("show");
    document.getElementById("overlay").classList.toggle("show");
}

/* CLOSE WHEN CLICK OVERLAY */
document.getElementById("overlay").addEventListener("click", function(){
    document.getElementById("sidebar").classList.remove("show");
    this.classList.remove("show");
});

/* FIX RESIZE */
window.addEventListener("resize", ()=>{
    if(window.innerWidth >= 992){
        document.getElementById("sidebar").classList.remove("show");
        document.getElementById("overlay").classList.remove("show");
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