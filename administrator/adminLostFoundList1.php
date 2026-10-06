<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$result = $conn->query("
SELECT
    lf.*,

     /* HAS MATCH? 
    EXISTS (
        SELECT 1 FROM lost_found_matches m
        WHERE m.lost_id = lf.id OR m.found_id = lf.id
    ) AS has_match, */
    
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
    se.lastName AS staff_edited_last,

    /* RELEASED BY */
    ar.firstName AS admin_released_first,
    ar.lastName AS admin_released_last,

    sr.firstName AS staff_released_first,
    sr.lastName AS staff_released_last,

    /* RESOLVED BY */
    av.firstName AS admin_resolved_first,
    av.lastName AS admin_resolved_last,

    sv.firstName AS staff_resolved_first,
    sv.lastName AS staff_resolved_last

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

/* RELEASED BY */
LEFT JOIN admin ar
    ON ar.id = lf.released_by

LEFT JOIN staff sr
    ON sr.id = lf.released_by

/* RESOLVED BY */
LEFT JOIN admin av
    ON av.id = lf.resolved_by

LEFT JOIN staff sv
    ON sv.id = lf.resolved_by

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


/* ====================== EMPTY STATE (list table) ===================== */
/* ginaya mula sa .match-empty ng adminLostFoundMatches.php */
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

    /* Firefox */
    /* scrollbar-width: none; */
}
/* Chrome, Edge, Safari */
/* .table-responsive-custom::-webkit-scrollbar {
    height: 0;
    opacity: 0;
} */

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

.row-check{
    width:16px;
    height:16px;
    accent-color:#198754;
}

/* ====================== TYPE ===================== */
.badge-type{
    display:inline-block;
    font-weight:800;
    font-size:10.5px; /*10.5px*/
    border-radius:20px; 
    padding:5px 11px;
}

/* .type-found-item{background: #F1EAFC;color: #7C4DFF;}
.type-lost-report{background: #FCEAED;color: #E1596B; padding:5px 16px;} */
.type-found-item {background: #EAF7EF; color: #198754;}
.type-lost-report {background: #FCEAED; color: #D9534F; padding:5px 16px;}


/* ====================== BADGE ===================== */
.badge-status{
    display:inline-block;
    font-weight:800;
    font-size:10.5px; /*10.5px*/
    border-radius:20px;
    padding:5px 11px; 
}

.status-pending{background: #F1F2F4;color: #6C757D;}
.status-resolved{background: #FFF4E5;color: #B8860B;}
.status-claimed{background: #EAF1FF;color: #3366CC;}
.status-matched{background: #F1EAFC;color: #7C4DFF;}
.status-expired {background: #FFE4E8; color: #D6336C;}
/*.status-expired{background: #FDECEA;color: #C0392B;}*/
.status-renewed{background: #E8F0FE;color: #1A56DB;}
/* .status-renewed-matched{background: linear-gradient(90deg, #E8F0FE 50%, #F1EAFC 50%);color: #4B3FA6;} */
.status-renewed-matched{background: #EAF7EF; color: #198754;}

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

.action-btn.claim:hover{color: #3366CC;border-color: #EAF1FF;}
.action-btn.view:hover{color: #198754;border-color: #c9e9d7;}
.action-btn.edit:hover{color: #B8860B;border-color: #f2e0b8;}
.action-btn.delete:hover{color: #E1596B;border-color: #f5c7ce;background: #FCEAED;}
.action-btn.renew:hover{color: #198754;border-color: #c9e9d7;background: #F3FBF7;}

/* ====================== ACTION STICKY COLUMN ===================== */
.item-table th:last-child,
.item-table td:last-child{
    position: sticky;
    right: 0;
    z-index: 2;
    background: #FAFBFA; /* dapat may bg para hindi nagti-transparent habang nag-sscroll */
    box-shadow: -4px 0 6px -4px rgba(0,0,0,0.15); /* subtle divider papuntang left */
}

.item-table thead th:last-child{
    background: #FAFBFA; /* same sa header bg  */
    z-index: 3; /* mas mataas para nasa ibabaw pa rin ng ibang sticky cells habang naka-scroll vertically */
}

.item-table tbody td:last-child{
    background: #fff; /* dapat match sa row bg (default white) */
}

.item-table tbody tr:hover td:last-child{
    background: #FAFEFC; /* match sa hover sa buong row */
}


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
                <option>Expired</option>   <!-- BAGO -->
            </select>
           <a href="adminLostFoundCreate.php" class="btn-create"><i class="bi bi-plus-lg"></i> Add lost found </a>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <div class="table-responsive-custom">

            <?php if ($result->num_rows === 0): ?>
                <!-- WALANG LAMAN - kaparehong pattern ng match-empty sa adminLostFoundMatches.php -->
                <div class="list-empty">
                    <i class="bi bi-inboxes"></i>
                    <div class="mt-2">No lost or found items yet.</div>
                </div>
            <?php else: ?>

                <table class="item-table">
                    <thead>
                        <tr>
                            <!-- <th><input type="checkbox" class="row-check"></th> -->
                            <th><i class="bi bi-view-list"></i> id</th>
                            <th><i class="bi bi-images"></i> image</th>
                            <th><i class="bi bi-tag-fill"></i> Status</th>
                            <th><i class="bi bi-collection"></i> Category</th>
                            <th><i class="bi bi-card-list"></i> Item Details</th>
                            <th><i class="bi bi-geo-alt"></i> Reported Location</th>

                            <th><i class="bi bi-person-badge"></i> Reporter</th>   <!-- BAGO -->

                            <th><i class="bi bi-shield-check"></i> Possible Status</th>
                            <th><i class="bi bi-person-plus"></i> Created By</th>
                            <th><i class="bi bi-clock-history"></i> Time Create</th>
                            <th><i class="bi bi-pencil-square"></i> Edited By</th>
                            <th><i class="bi bi-clock"></i> Edited Time</th>
                            <th><i class="bi bi-box-arrow-right"></i> Release By</th>
                            <th><i class="bi bi-clock"></i> Release Time</th>
                            <th><i class="bi bi-check2-circle"></i> Resolve By</th>
                            <th><i class="bi bi-clock"></i> Resolve Time</th>
                            <th style="text-align:right;"><i class="bi bi-gear"></i> Actions</th>


                            <!-- <th><input type="checkbox" class="row-check"></th>
                            <th>Status</th>
                            <th>Category</th>
                            <th>Item Details</th>
                            <th>Reported Location</th>
                            <th>Possible Status</th>
                            <th>Time Create</th>
                            <th>Created By</th>
                            <th>Edited By</th>
                            <th>Edited Time</th>
                            <th>relis by</th>
                            <th>relis time</th>
                            <th>resolve by</th>
                            <th>resolve time</th>
                            <th style="text-align:right;">Actions</th> -->

                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                                /* ================= REPORTER INFO ================= */
                                $reporterDetails = '-';

                                if ($row['reporter_role'] === 'Student') {
                                    $reporterDetails =
                                        htmlspecialchars($row['reporter_name']) . '<br>' .
                                        '<span style="color:#7C8A85;">Student Id: ' . htmlspecialchars($row['reporter_id']) . ' - ' .
                                        htmlspecialchars($row['reporter_year']) . ' - ' . htmlspecialchars($row['reporter_department']) . '</span>';
                                }
                                elseif ($row['reporter_role'] === 'Faculty') {
                                    $reporterDetails =
                                        htmlspecialchars($row['reporter_name']) . '<br>' .
                                        '<span style="color:#7C8A85;">Faculty Id: ' . htmlspecialchars($row['reporter_id']) . '</span>';
                                }
                                elseif ($row['reporter_role'] === 'Visitor / Guest') {
                                    $reporterDetails =
                                        htmlspecialchars($row['reporter_name']) . '<br>' .
                                        '<span style="color:#7C8A85;">Visitor / Guest</span>';
                                }
                                // elseif ($row['reporter_role'] === 'Anonymous') {
                                //     $reporterDetails = '<span style="color:#7C8A85;">Anonymous</span>';
                                // }
                                elseif ($row['reporter_role'] === 'Anonymous') {
                                    $reporterDetails =
                                        '<b>Anonymous</b><br>' .
                                        '<span style="color:#7C8A85;">Id: ' . htmlspecialchars($row['reporter_id']) . ' - ' .
                                        htmlspecialchars($row['reporter_year']) . ' - ' . htmlspecialchars($row['reporter_department']) . '</span>';
                                }

                                /* ================= ITEM DETAILS ================= */
                                $itemDetails = '-';

                                if ($row['category'] === 'Cash') {
                                    $itemDetails = 'Ammount ₱: ' . number_format($row['cash_amount'], 2);
                                }

                                elseif ($row['category'] === 'Gadget') {
                                    $itemDetails =
                                        'Type: ' . ($row['gadget_type'] ?? '-') . ' - ' .
                                        'Brand: ' . ($row['gadget_brand'] ?? '-') . '<br>' .
                                        'Color: ' . ($row['gadget_color'] ?? '-') . ' - ' .
                                        'Features: ' . ($row['gadget_features'] ?? '-');
                                }

                                elseif ($row['category'] === 'Document') {
                                    $itemDetails =
                                        'Type: ' . ($row['document_type'] ?? '-') . '<br>' .
                                        'Name: ' . ($row['document_name'] ?? '-');
                                }

                                elseif ($row['category'] === 'Other') {
                                    $itemDetails = 'Item: ' . ($row['other_description'] ?? '-');
                                }

                                /* ================= TYPE ================= */
                                if ($row['status'] === 'Found') {
                                    $typeClass = 'type-found-item';
                                } else {
                                    $typeClass = 'type-lost-report';
                                }

                                /* ================= EXPIRED CHECK ================= */
                                // $isExpired = (time() - strtotime($row['created_at']) >= 86400) && !$row['is_claimed'] && !$row['is_resolved'];
                                // Kaparehong 7-day window ng match system, pero para sa "Expired" status
                                $daysOld = (strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($row['created_at'])))) / 86400;
                                $isExpired = ($daysOld > 7) && !$row['is_claimed'] && !$row['is_resolved'];

                            ?>

                        <tr>
                            <!-- <td><input type="checkbox" class="row-check"></td> -->
                            <td class="text-center"> 
                                #<?= htmlspecialchars($row['id']) ?> 
                            </td>
                             <!-- <td>
                                <img src="../uploads/<?= htmlspecialchars($row['image']) ?>" width="35">
                            </td> -->


                            <td> <img src="../uploads/<?= htmlspecialchars($row['image']) ?>" width="35" height="35" style="object-fit: cover; cursor: pointer; border-radius: 6px;" onclick="zoomImage(this.src)" alt="Item Image" > </td>


                            <td><span class="badge-type <?= $typeClass ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= $itemDetails ?></td>
                            <td><?= htmlspecialchars($row['reported_location']) ?></td>
                            <td><?= $reporterDetails ?></td>   <!-- BAGO -->
                            <td>
                                <?php
                                    if ($row['is_claimed']) {
                                        echo '<span class="badge-status status-claimed">Claimed</span>';
                                    }
                                    elseif ($row['is_resolved']) {
                                        echo '<span class="badge-status status-resolved">Resolved</span>';
                                    }
                                    elseif ($isExpired) {
                                        echo '<span class="badge-status status-expired">Expired</span>';
                                    }
                                    elseif (!empty($row['renewed_at']) && $row['has_match']) {                                             //renew with match
                                        echo '<span class="badge-status status-renewed-matched">Renewed/Matched</span>';
                                    }
                                    elseif (!empty($row['renewed_at'])) {
                                        echo '<span class="badge-status status-renewed">Renewed</span>';
                                    }
                                    elseif ($row['has_match']) {
                                        echo '<span class="badge-status status-matched">Matched</span>';
                                    } else {
                                        echo '<span class="badge-status status-pending">Pending</span>';
                                    }
                                ?>
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
                                        echo '<span style="color: #7C8A85;">-- N/A --</span>';
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

                            <!-- RELEASED BY -->
                            <td>
                                <?php
                                if (!empty($row['admin_released_first'])) {
                                    echo $row['admin_released_first'] . ' ' . $row['admin_released_last']. ' (Admin)';
                                }

                                elseif (!empty($row['staff_released_first'])) {
                                    echo $row['staff_released_first'] . ' ' . $row['staff_released_last']. ' (Staff)';
                                }

                                else {
                                    echo '<span style="color: #7C8A85;">--</span>';
                                }
                                ?>
                            </td>

                            <!-- RELEASED TIME -->
                            <td>
                                <?php if (!empty($row['claimed_date'])): ?>
                                    <?= date('M d, Y', strtotime($row['claimed_date'])) ?>
                                    <?= date('h:i A', strtotime($row['claimed_date'])) ?>
                                <?php else: ?>
                                   <span style="color: #7C8A85;">--</span>
                                <?php endif; ?>
                            </td>


                            <!-- RESOLVED BY -->
                            <td>
                                <?php
                                if (!empty($row['admin_resolved_first'])) {
                                    echo $row['admin_resolved_first'] . ' ' . $row['admin_resolved_last']. ' (Admin)';
                                }

                                elseif (!empty($row['staff_resolved_first'])) {
                                    echo $row['staff_resolved_first'] . ' ' . $row['staff_resolved_last']. ' (Staff)';
                                }

                                else {
                                    echo '<span style="color: #7C8A85;">--</span>';
                                }
                                ?>
                            </td>

                            <!-- RESOLVED AT -->
                            <td>
                                <?php if (!empty($row['resolved_at'])): ?>
                                    <?= date('M d, Y', strtotime($row['resolved_at'])) ?>
                                    <?= date('h:i A', strtotime($row['resolved_at'])) ?>
                                <?php else: ?>
                                   <span style="color: #7C8A85;">--</span>
                                <?php endif; ?>
                            </td>

                            <!-- ACTION -->
                            <!-- <td>
                                <div class="row-actions justify-content-end">
                                    <a href="adminLostFoundClaim.php?id=<?= $row['id'] ?>" class="action-btn claim" title="claim">
                                            <i class="bi bi-hand-index-thumb"></i>
                                        </a>
                                    <a href="adminLostFoundView.php?id=<?= $row['id'] ?>" class="action-btn view" title="view">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <a href="adminLostFoundUpdate.php?id=<?= $row['id'] ?>" class="action-btn edit" title="edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <div class="action-btn delete" title="delete"><i class="bi bi-trash"></i></div>
                                </div>
                            </td> -->

                            <!-- ACTION -->
                            <td>
                                <div class="row-actions justify-content-end">

                                    <?php if ($row['status'] === 'Found' && !$row['is_claimed'] && !$row['is_resolved'] && !$isExpired): ?>
                                        <a href="adminLostFoundRelease.php?id=<?= $row['id'] ?>" class="action-btn claim" title="release">
                                            <i class="bi bi-hand-index-thumb"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($row['is_claimed']): ?>
                                        <a href="adminLostFoundView.php?id=<?= $row['id'] ?>" class="action-btn view" title="view">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!$row['is_claimed'] && !$row['is_resolved'] && !$isExpired): ?>
                                        <a href="adminLostFoundUpdate.php?id=<?= $row['id'] ?>" class="action-btn edit" title="edit">  <!-- adminLostFoundUpdate.php -->
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!$row['is_claimed'] && !$row['is_resolved'] && $isExpired): ?>
                                        <a href="adminLostFoundRenew.php?id=<?= $row['id'] ?>" class="action-btn renew" title="renew">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </a>
                                    <?php endif; ?>

                                    <form method="POST" action="adminLostFoundDelete.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="action-btn delete" title="delete" onclick="return confirm('Delete?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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




    <!-- IMAGE ZOOM MODAL -->
<div
    class="modal fade"
    id="imageZoomModal"
    tabindex="-1"
    aria-hidden="true"
>
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

        const modal = new bootstrap.Modal(
            document.getElementById("imageZoomModal")
        );

        modal.show();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>





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