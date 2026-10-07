<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| AUDIT TRAIL QUERY
|--------------------------------------------------------------------------
| "a" = isang row bawat activity (Created / Edited / Released / Resolved / Deleted)
| Live items  -> details galing sa lost_found
| Deleted items -> details galing sa snapshot columns ng lost_found_deletions
*/
$result = $conn->query("
SELECT
    a.item_id,
    a.deletion_id,
    a.activity_type,
    a.activity_time,

    COALESCE(lf.image, ld.image)                         AS image,
    COALESCE(lf.status, ld.status)                       AS status,
    COALESCE(lf.category, ld.category)                   AS category,
    COALESCE(lf.reported_location, ld.reported_location) AS reported_location,

    COALESCE(lf.reporter_role, ld.reporter_role)             AS reporter_role,
    COALESCE(lf.reporter_name, ld.reporter_name)             AS reporter_name,
    COALESCE(lf.reporter_id, ld.reporter_id)                 AS reporter_id,
    COALESCE(lf.reporter_year, ld.reporter_year)             AS reporter_year,
    COALESCE(lf.reporter_department, ld.reporter_department) AS reporter_department,

    COALESCE(lf.cash_amount, ld.cash_amount)                 AS cash_amount,
    COALESCE(lf.gadget_type, ld.gadget_type)                 AS gadget_type,
    COALESCE(lf.gadget_brand, ld.gadget_brand)               AS gadget_brand,
    COALESCE(lf.gadget_color, ld.gadget_color)               AS gadget_color,
    COALESCE(lf.gadget_description, ld.gadget_description)   AS gadget_description,
    COALESCE(lf.document_type, ld.document_type)             AS document_type,
    COALESCE(lf.document_name, ld.document_name)             AS document_name,
    COALESCE(lf.other_title, ld.other_title)                 AS other_title,
    COALESCE(lf.other_description, ld.other_description)     AS other_description,

    lf.is_claimed,
    lf.is_resolved,

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

    /* ITEM CREATED BY */
    ca.firstName AS admin_created_first,
    ca.lastName  AS admin_created_last,
    cs.firstName AS staff_created_first,
    cs.lastName  AS staff_created_last,

    /* PERFORMED BY */
    pa.firstName AS admin_first,
    pa.lastName  AS admin_last,
    ps.firstName AS staff_first,
    ps.lastName  AS staff_last

FROM (

    /* CREATED */
    SELECT lf.id AS item_id, NULL AS deletion_id,
           'Created' AS activity_type, lf.created_at AS activity_time,
           lf.created_by AS performer_id, NULL AS performer_role
    FROM lost_found lf
    WHERE lf.created_at IS NOT NULL

    UNION ALL

    /* EDITED */
    SELECT lf.id, NULL,
           'Edited', lf.edited_at,
           lf.edited_by, NULL
    FROM lost_found lf
    WHERE lf.edited_at IS NOT NULL

    UNION ALL

    /* RELEASED */
    SELECT lf.id, NULL,
           'Released', lf.claimed_date,
           lf.released_by, NULL
    FROM lost_found lf
    WHERE lf.claimed_date IS NOT NULL

    UNION ALL

    /* RESOLVED */
    SELECT lf.id, NULL,
           'Resolved', lf.resolved_at,
           lf.resolved_by, NULL
    FROM lost_found lf
    WHERE lf.resolved_at IS NOT NULL

    UNION ALL

    /* DELETED / DISPOSED */
    SELECT ld.lost_found_id, ld.id,
           CASE
               WHEN ld.was_disposed = 1 THEN 'Disposed'
               WHEN ld.was_resolved = 1 THEN 'Resolved Deleted'
               WHEN ld.was_claimed  = 1 THEN 'Claimed Deleted'
               ELSE 'Deleted'
           END,
           ld.deleted_at,
           ld.deleted_by, ld.deleted_role
    FROM lost_found_deletions ld

) a

LEFT JOIN lost_found lf
    ON a.deletion_id IS NULL AND lf.id = a.item_id

LEFT JOIN lost_found_deletions ld
    ON ld.id = a.deletion_id

/* PERFORMED BY (role-specific kung galing sa deletions) */
LEFT JOIN admin pa
    ON pa.id = a.performer_id
    AND (a.performer_role IS NULL OR a.performer_role = 'admin')

LEFT JOIN staff ps
    ON ps.id = a.performer_id
    AND (a.performer_role IS NULL OR a.performer_role = 'staff')

/* ITEM CREATED BY */
LEFT JOIN admin ca
    ON ca.id = COALESCE(lf.created_by, ld.created_by)

LEFT JOIN staff cs
    ON cs.id = COALESCE(lf.created_by, ld.created_by)

ORDER BY a.activity_time DESC
");

/* ================= HELPERS ================= */
function esc($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDateTime($date) {
    if (empty($date)) return '<span style="color:#7C8A85;">--</span>';
    return date('M d, Y', strtotime($date)) . ' ' . date('h:i A', strtotime($date));
}

function getPerformedBy($row) {
    if (!empty($row['admin_first'])) return esc($row['admin_first'] . ' ' . $row['admin_last']) . ' (Admin)';
    if (!empty($row['staff_first'])) return esc($row['staff_first'] . ' ' . $row['staff_last']) . ' (Staff)';
    return '<span style="color:#7C8A85;">--</span>';
}

function getCreatedBy($row) {
    if (!empty($row['admin_created_first'])) return esc($row['admin_created_first'] . ' ' . $row['admin_created_last']) . ' (Admin)';
    if (!empty($row['staff_created_first'])) return esc($row['staff_created_first'] . ' ' . $row['staff_created_last']) . ' (Staff)';
    return '<span style="color:#7C8A85;">--</span>';
}

function getReporterDetails($row) {
    $role = $row['reporter_role'] ?? '';

    if ($role === 'Student') {
        return esc($row['reporter_name']) . '<br><span style="color:#7C8A85;">Student ID: ' . esc($row['reporter_id']) . ' • ' . esc($row['reporter_year']) . ' • ' . esc($row['reporter_department']) . '</span>';
    }
    if ($role === 'Faculty') {
        return esc($row['reporter_name']) . '<br><span style="color:#7C8A85;">Faculty ID: ' . esc($row['reporter_id']) . '</span>';
    }
    if ($role === 'Visitor / Guest') {
        return esc($row['reporter_name']) . '<br><span style="color:#7C8A85;">Visitor / Guest</span>';
    }
    if ($role === 'Anonymous') {
        return '<b>Anonymous</b><br><span style="color:#7C8A85;">Id: ' . esc($row['reporter_id']) . ' • ' . esc($row['reporter_year']) . ' • ' . esc($row['reporter_department']) . '</span>';
    }
    return '-';
}

function getItemDetails($row) {
    $dash = fn($v) => ($v === null || $v === '') ? '-' : esc($v);

    switch ($row['category']) {
        case 'Cash':
            return 'Amount ₱: ' . ($row['cash_amount'] !== null ? number_format((float)$row['cash_amount'], 2) : '-');
        case 'Gadget':
            return 'Type: ' . $dash($row['gadget_type']) . ' • Brand: ' . $dash($row['gadget_brand']) . '<br>' .
                   'Color: ' . $dash($row['gadget_color']) . ' • Item Description: ' . $dash($row['gadget_description']);
        case 'Document':
            return 'Type: ' . $dash($row['document_type']) . '<br>Name: ' . $dash($row['document_name']);
        case 'Other':
            return 'Item: ' . $dash($row['other_title']) . '<br>Desc : ' . $dash($row['other_description']);
    }
    return '-';
}

function getPossibleStatus($row) {
    // Deleted rows: galing sa activity type
    switch ($row['activity_type']) {
        case 'Disposed':         return 'Disposed';
        case 'Resolved Deleted': return 'Resolved';
        case 'Claimed Deleted':  return 'Claimed';
        case 'Deleted':          return 'Deleted';
    }
    // Live rows
    if ($row['is_claimed'])  return 'Claimed';
    if ($row['is_resolved']) return 'Resolved';
    if ($row['has_match'])   return 'Matched';
    return 'Pending';
}

function getActivityMeta($type) {
    switch ($type) {
        case 'Created':          return ['created',  'act-created',  'bi-plus-circle',     'Item Created'];
        case 'Edited':           return ['edited',   'act-edited',   'bi-pencil',          'Item Edited'];
        case 'Released':         return ['released', 'act-released', 'bi-hand-index-thumb','Released / Claimed'];
        case 'Resolved':         return ['resolved', 'act-resolved', 'bi-check2-circle',   'Item Resolved'];
        case 'Disposed':         return ['deleted',  'act-deleted',  'bi-trash',           'Item Disposed'];
        case 'Claimed Deleted':  return ['deleted',  'act-deleted',  'bi-trash',           'Claimed Item Deleted'];
        case 'Resolved Deleted': return ['deleted',  'act-deleted',  'bi-trash',           'Resolved Item Deleted'];
        default:                 return ['deleted',  'act-deleted',  'bi-trash',           'Item Deleted'];
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
    to   { transform: rotate(-360deg); }
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
.type-lost-report {background: #FCEAED; color: #D9534F; padding:5px 16px;}

/* ====================== BADGE ===================== */
.badge-status{
    display:inline-block;
    font-weight:800;
    font-size:10.5px;
    border-radius:20px;
    padding:5px 11px;
}

.status-pending{background: #F1F2F4;color: #6C757D;}
.status-resolved{background: #FFF4E5;color: #B8860B;}
.status-claimed{background: #EAF1FF;color: #3366CC;}
.status-matched{background: #F1EAFC;color: #7C4DFF;}
.status-disposed{background: #FCEAED;color: #D9534F;}
.status-deleted{background: #FCEAED;color: #D9534F;}

/* ====================== ACTIVITY BADGE ===================== */
.badge-activity{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-weight:800;
    font-size:11px;
    border-radius:20px;
    padding:6px 12px;
}

.act-created{background: #EAF7EF;color: #198754;}
.act-edited{background: #FFF4E5;color: #B8860B;}
.act-released{background: #EAF1FF;color: #3366CC;}
.act-resolved{background: #F1EAFC;color: #7C4DFF;}
.act-deleted{background: #FCEAED;color: #D9534F;}

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
        <a href="adminLostFoundItems.php"> <i class="bi bi-search"></i> Found Items </a>
        <a href="adminLostFoundAuditTrail.php" class="active"> <i class="bi bi-clipboard-data"></i> Audit Trail </a>
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
        </div>
    </nav>

    <!-- MAIN-CONTENT -->
    <div class="main-content">
        <div class="mt-4 mb-3">
            <div class="page-heading">Audit Trail</div>
            <div class="page-subheading">A complete history of every item created, edited, released, resolved, and deleted.</div>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search by item id, name, location, or performed by...">
            </div>
            <select class="filter-select" id="typeFilter">
                <option value="">All Type</option>
                <option value="Found">Found</option>
                <option value="Lost">Lost</option>
            </select>
            <select class="filter-select" id="categoryFilter">
                <option value="">All Category</option>
                <option value="Cash">Cash</option>
                <option value="Gadget">Gadget</option>
                <option value="Document">Document</option>
                <option value="Other">Other</option>
            </select>
            <button type="button" class="btn-reset" id="resetBtn"><i class="bi bi-arrow-counterclockwise"></i> Reset </button>
        </div>

        <!-- ACTIVITY FILTER PILLS -->
        <div class="status-pills" id="statusPills">
            <div class="status-pill active" data-activity="" style="padding:8px 24px;">All</div>
            <div class="status-pill" data-activity="created">Created</div>
            <div class="status-pill" data-activity="edited">Edited</div>
            <div class="status-pill" data-activity="released">Released</div>
            <div class="status-pill" data-activity="resolved">Resolved</div>
            <div class="status-pill" data-activity="deleted">Deleted / Disposed</div>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <div class="table-responsive-custom">

            <?php if (!$result || $result->num_rows === 0): ?>
                <div class="list-empty">
                    <i class="bi bi-inboxes"></i>
                    <div class="mt-2">No activity recorded yet.</div>
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
                            <th><i class="bi bi-activity"></i> Activity</th>
                            <th><i class="bi bi-person-check"></i> Performed By</th>
                            <th><i class="bi bi-clock-history"></i> Date &amp; Time</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                                $possibleStatus = getPossibleStatus($row);
                                $typeClass = ($row['status'] === 'Found') ? 'type-found-item' : 'type-lost-report';
                                [$activityKey, $activityClass, $activityIcon, $activityLabel] = getActivityMeta($row['activity_type']);
                                $imageFile = !empty($row['image']) ? $row['image'] : 'default1.png';
                            ?>

                        <tr data-type="<?= esc($row['status']) ?>"
                            data-category="<?= esc($row['category']) ?>"
                            data-activity="<?= $activityKey ?>">

                            <td class="text-center">#<?= esc($row['item_id']) ?></td>

                            <td>
                                <img src="../uploads/<?= esc($imageFile) ?>"
                                     width="35" height="35"
                                     style="object-fit: cover; cursor: pointer; border-radius: 6px;"
                                     onclick="zoomImage(this.src)"
                                     onerror="this.onerror=null; this.src='../uploads/default1.png';"
                                     alt="Item Image">
                            </td>

                            <td><span class="badge-type <?= $typeClass ?>"><?= esc($row['status']) ?></span></td>
                            <td><?= esc($row['category']) ?></td>
                            <td><?= getItemDetails($row) ?></td>
                            <td><?= !empty($row['reported_location']) ? esc($row['reported_location']) : '-' ?></td>
                            <td><?= getReporterDetails($row) ?></td>

                            <td>
                                <span class="badge-status status-<?= strtolower($possibleStatus) ?>"><?= $possibleStatus ?></span>
                            </td>

                            <td><?= getCreatedBy($row) ?></td>

                            <td>
                                <span class="badge-activity <?= $activityClass ?>">
                                    <i class="bi <?= $activityIcon ?>"></i> <?= $activityLabel ?>
                                </span>
                            </td>

                            <td><?= getPerformedBy($row) ?></td>

                            <td><?= formatDateTime($row['activity_time']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div id="noResults" class="list-empty" style="display:none;">
                    <i class="bi bi-search"></i>
                    <div class="mt-2">No activity matches your filter.</div>
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
                <button type="button" class="btn-close btn-close-white position-absolute"
                    style="right: 10px; top: 10px; z-index: 10;"
                    data-bs-dismiss="modal" aria-label="Close"></button>

                <img id="zoomedImage" src="" alt="Zoomed Item Image"
                    style="max-width: 90vw; max-height: 85vh; object-fit: contain; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,.4);">
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
const typeFilter     = document.getElementById('typeFilter');
const categoryFilter = document.getElementById('categoryFilter');
const statusPills    = document.querySelectorAll('#statusPills .status-pill');
const resetButton    = document.getElementById('resetBtn');
const noResults      = document.getElementById('noResults');
const rows           = document.querySelectorAll('.item-table tbody tr');

let activeActivity = '';

// i-cache ang searchable text ng bawat row
rows.forEach(row => {
    row.dataset.search = row.textContent.replace(/\s+/g, ' ').trim().toLowerCase();
});

function applyFilters() {
    const searchTerm = searchInput.value.trim().toLowerCase();
    const type = typeFilter.value;
    const category = categoryFilter.value;
    let visibleCount = 0;

    rows.forEach(row => {
        const matchesSearch   = !searchTerm || row.dataset.search.includes(searchTerm);
        const matchesType     = !type || row.dataset.type === type;
        const matchesCategory = !category || row.dataset.category === category;
        const matchesActivity = !activeActivity || row.dataset.activity === activeActivity;

        const show = matchesSearch && matchesType && matchesCategory && matchesActivity;
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
}

searchInput.addEventListener('input', applyFilters);
typeFilter.addEventListener('change', applyFilters);
categoryFilter.addEventListener('change', applyFilters);

statusPills.forEach(pill => {
    pill.addEventListener('click', function () {
        statusPills.forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        activeActivity = this.dataset.activity;
        applyFilters();
    });
});

resetButton.addEventListener('click', function () {
    const resetIcon = this.querySelector('i');
    resetIcon.classList.remove('spin');
    void resetIcon.offsetWidth;
    resetIcon.classList.add('spin');

    searchInput.value = '';
    typeFilter.value = '';
    categoryFilter.value = '';
    activeActivity = '';

    statusPills.forEach(p => p.classList.remove('active'));
    document.querySelector('#statusPills .status-pill[data-activity=""]').classList.add('active');

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
    }, 900000);
</script>
</body>
</html>