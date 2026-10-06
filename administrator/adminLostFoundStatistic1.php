<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

/* =====================================================
   KPI QUERIES
   ===================================================== */

// TOTAL ITEMS FOUND (all-time)
$totalFound = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found
    WHERE status = 'Found'
")->fetch_assoc()['total'];

// TOTAL MATCHES
$totalMatched = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found_matches
")->fetch_assoc()['total'];

// TOTAL CLAIMED
$totalClaimed = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found
    WHERE is_claimed = 1
")->fetch_assoc()['total'];

// PENDING / UNCLAIMED (Found items not yet claimed)
$pendingUnclaimed = $conn->query("
    SELECT COUNT(*) as total
    FROM lost_found
    WHERE status = 'Found' AND is_claimed = 0
")->fetch_assoc()['total'];

/* =====================================================
   MONTH-OVER-MONTH DELTAS
   ===================================================== */

function getMonthCount($conn, $sql) {
    $res = $conn->query($sql);
    return $res ? (int)$res->fetch_assoc()['total'] : 0;
}

function calcDelta($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

$foundThisMonth = getMonthCount($conn, "
    SELECT COUNT(*) as total FROM lost_found
    WHERE status='Found'
    AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
");
$foundLastMonth = getMonthCount($conn, "
    SELECT COUNT(*) as total FROM lost_found
    WHERE status='Found'
    AND MONTH(created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH)
    AND YEAR(created_at) = YEAR(CURDATE() - INTERVAL 1 MONTH)
");
$foundDelta = calcDelta($foundThisMonth, $foundLastMonth);

$matchedThisMonth = getMonthCount($conn, "
    SELECT COUNT(*) as total FROM lost_found_matches
    WHERE MONTH(matched_at) = MONTH(CURDATE()) AND YEAR(matched_at) = YEAR(CURDATE())
");
$matchedLastMonth = getMonthCount($conn, "
    SELECT COUNT(*) as total FROM lost_found_matches
    WHERE MONTH(matched_at) = MONTH(CURDATE() - INTERVAL 1 MONTH)
    AND YEAR(matched_at) = YEAR(CURDATE() - INTERVAL 1 MONTH)
");
$matchedDelta = calcDelta($matchedThisMonth, $matchedLastMonth);

$claimedThisMonth = getMonthCount($conn, "
    SELECT COUNT(*) as total FROM lost_found
    WHERE is_claimed = 1
    AND MONTH(claimed_date) = MONTH(CURDATE()) AND YEAR(claimed_date) = YEAR(CURDATE())
");
$claimedLastMonth = getMonthCount($conn, "
    SELECT COUNT(*) as total FROM lost_found
    WHERE is_claimed = 1
    AND MONTH(claimed_date) = MONTH(CURDATE() - INTERVAL 1 MONTH)
    AND YEAR(claimed_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)
");
$claimedDelta = calcDelta($claimedThisMonth, $claimedLastMonth);

$pendingLastMonthSnapshot = getMonthCount($conn, "
    SELECT COUNT(*) as total FROM lost_found
    WHERE status='Found' AND is_claimed = 0
    AND created_at < (CURDATE() - INTERVAL DAY(CURDATE())-1 DAY)
");
$pendingDeltaAbs = $pendingUnclaimed - $pendingLastMonthSnapshot;

/* =====================================================
   WEEKLY CHART DATA (last 7 days, Found vs Lost vs Matched)
   ===================================================== */

$weeklyLabels = [];
$foundSeries  = [];
$lostSeries   = [];
$matchedSeries = [];

for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $weeklyLabels[] = date('D', strtotime($day));

    $f = $conn->query("
        SELECT COUNT(*) as total FROM lost_found
        WHERE status = 'Found' AND DATE(created_at) = '$day'
    ")->fetch_assoc()['total'];
    $foundSeries[] = (int)$f;

    $l = $conn->query("
        SELECT COUNT(*) as total FROM lost_found
        WHERE status = 'Lost' AND DATE(created_at) = '$day'
    ")->fetch_assoc()['total'];
    $lostSeries[] = (int)$l;

    $m = $conn->query("
        SELECT COUNT(*) as total FROM lost_found_matches
        WHERE DATE(matched_at) = '$day'
    ")->fetch_assoc()['total'];
    $matchedSeries[] = (int)$m;
}

/* =====================================================
   RECENT ACTIVITY FEED (merged from multiple tables)
   ===================================================== */

$activity = [];

// Newly logged items
$res = $conn->query("
    SELECT id, category, status, reported_location, created_at
    FROM lost_found
    ORDER BY created_at DESC
    LIMIT 5
");
while ($row = $res->fetch_assoc()) {
    $activity[] = [
        'type' => 'logged',
        'text' => "Item logged &mdash; <b>{$row['category']}</b> ({$row['status']})",
        'time' => $row['created_at'],
    ];
}

// Recent matches
$res = $conn->query("
    SELECT id, lost_id, found_id, status, matched_at
    FROM lost_found_matches
    ORDER BY matched_at DESC
    LIMIT 5
");
while ($row = $res->fetch_assoc()) {
    $activity[] = [
        'type' => 'matched',
        'text' => "System matched Lost #{$row['lost_id']} with Found #{$row['found_id']}",
        'time' => $row['matched_at'],
    ];
}

// Recent claims
$res = $conn->query("
    SELECT id, category, claimed_by, claimed_date
    FROM lost_found
    WHERE is_claimed = 1 AND claimed_date IS NOT NULL
    ORDER BY claimed_date DESC
    LIMIT 5
");
while ($row = $res->fetch_assoc()) {
    $claimant = $row['claimed_by'] ? htmlspecialchars($row['claimed_by']) : 'A claimant';
    $activity[] = [
        'type' => 'claimed',
        'text' => "<b>{$claimant}</b> claimed a {$row['category']} item",
        'time' => $row['claimed_date'],
    ];
}

// Sort merged activity by time desc, keep top 5
usort($activity, function($a, $b) {
    return strtotime($b['time']) <=> strtotime($a['time']);
});
$activity = array_slice($activity, 0, 5);

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return "just now";
    if ($diff < 3600) return floor($diff / 60) . " minutes ago";
    if ($diff < 86400) return floor($diff / 3600) . " hours ago";
    if ($diff < 172800) return "Yesterday";
    return floor($diff / 86400) . " days ago";
}

/* =====================================================
   RECENTLY LOGGED ITEMS TABLE
   ===================================================== */

$recentItems = $conn->query("
    SELECT id, category, status, reported_location, is_claimed, is_resolved, created_at
    FROM lost_found
    ORDER BY created_at DESC
    LIMIT 5
");

function statusBadge($row) {
    if ($row['is_claimed']) {
        return '<span class="badge-status status-claimed">Claimed</span>';
    }
    if ($row['is_resolved']) {
        return '<span class="badge-status status-matched">Matched</span>';
    }
    return '<span class="badge-status status-found">' . htmlspecialchars($row['status']) . '</span>';
}

$categoryIcon = [
    'Cash'     => ['bi-cash-coin', '#DCFCE7', '#15803D'],
    'Gadget'   => ['bi-phone', '#DBEAFE', '#1D4ED8'],
    'Document' => ['bi-file-earmark-text', '#FEF3C7', '#B45309'],
    'Other'    => ['bi-box-seam', '#EDE9FE', '#6D28D9'],
];

/* =====================================================
   PENDING CLAIMS (oldest unclaimed Found items)
   ===================================================== */

$pendingClaims = $conn->query("
    SELECT id, category, reported_location, created_at
    FROM lost_found
    WHERE status = 'Found' AND is_claimed = 0
    ORDER BY created_at ASC
    LIMIT 3
");

/* =====================================================
   MOST FREQUENTLY LOST ITEMS
   ===================================================== */

// Ranking by category (Cash / Gadget / Document / Other) among LOST reports
$lostByCategory = [];
$res = $conn->query("
    SELECT category, COUNT(*) as total
    FROM lost_found
    WHERE status = 'Lost'
    GROUP BY category
    ORDER BY total DESC
");
while ($row = $res->fetch_assoc()) {
    $lostByCategory[] = $row;
}
$maxCategoryCount = 0;
foreach ($lostByCategory as $c) {
    $maxCategoryCount = max($maxCategoryCount, (int)$c['total']);
}

// Ranking by gadget type among LOST gadget reports (most common electronics lost)
$lostByGadgetType = [];
$res = $conn->query("
    SELECT gadget_type, COUNT(*) as total
    FROM lost_found
    WHERE status = 'Lost' AND category = 'Gadget' AND gadget_type IS NOT NULL
    GROUP BY gadget_type
    ORDER BY total DESC
");
while ($row = $res->fetch_assoc()) {
    $lostByGadgetType[] = $row;
}
$maxGadgetCount = 0;
foreach ($lostByGadgetType as $g) {
    $maxGadgetCount = max($maxGadgetCount, (int)$g['total']);
}

$categoryColors = [
    'Cash'     => ['#DCFCE7', '#15803D'],
    'Gadget'   => ['#DBEAFE', '#1D4ED8'],
    'Document' => ['#FEF3C7', '#B45309'],
    'Other'    => ['#EDE9FE', '#6D28D9'],
];

/* =====================================================
   MOST FREQUENTLY FOUND ITEMS
   ===================================================== */

// Ranking by category among FOUND reports
$foundByCategory = [];
$res = $conn->query("
    SELECT category, COUNT(*) as total
    FROM lost_found
    WHERE status = 'Found'
    GROUP BY category
    ORDER BY total DESC
");
while ($row = $res->fetch_assoc()) {
    $foundByCategory[] = $row;
}
$maxFoundCategoryCount = 0;
foreach ($foundByCategory as $c) {
    $maxFoundCategoryCount = max($maxFoundCategoryCount, (int)$c['total']);
}

// Ranking by gadget type among FOUND gadget reports
$foundByGadgetType = [];
$res = $conn->query("
    SELECT gadget_type, COUNT(*) as total
    FROM lost_found
    WHERE status = 'Found' AND category = 'Gadget' AND gadget_type IS NOT NULL
    GROUP BY gadget_type
    ORDER BY total DESC
");
while ($row = $res->fetch_assoc()) {
    $foundByGadgetType[] = $row;
}
$maxFoundGadgetCount = 0;
foreach ($foundByGadgetType as $g) {
    $maxFoundGadgetCount = max($maxFoundGadgetCount, (int)$g['total']);
}

/* =====================================================
   HIGHEST CASH LOST & HIGHEST CASH RETURNED
   ===================================================== */

// Highest single cash amount reported as LOST
$highestCashLost = $conn->query("
    SELECT cash_amount, reported_location, created_at
    FROM lost_found
    WHERE category = 'Cash' AND status = 'Lost' AND cash_amount IS NOT NULL
    ORDER BY cash_amount DESC
    LIMIT 1
")->fetch_assoc();

// Highest single cash amount that was RETURNED to its owner (claimed)
$highestCashReturned = $conn->query("
    SELECT cash_amount, reported_location, claimed_by, claimed_date
    FROM lost_found
    WHERE category = 'Cash' AND is_claimed = 1 AND cash_amount IS NOT NULL
    ORDER BY cash_amount DESC
    LIMIT 1
")->fetch_assoc();

/* =====================================================
   MOST COMMON DOCUMENT TYPES (LOST & FOUND)
   ===================================================== */

// Document types among LOST reports
$lostByDocumentType = [];
$res = $conn->query("
    SELECT document_type, COUNT(*) as total
    FROM lost_found
    WHERE status = 'Lost' AND category = 'Document' AND document_type IS NOT NULL AND document_type != ''
    GROUP BY document_type
    ORDER BY total DESC
");
while ($row = $res->fetch_assoc()) {
    $lostByDocumentType[] = $row;
}
$maxLostDocCount = 0;
foreach ($lostByDocumentType as $d) {
    $maxLostDocCount = max($maxLostDocCount, (int)$d['total']);
}

// Document types among FOUND reports
$foundByDocumentType = [];
$res = $conn->query("
    SELECT document_type, COUNT(*) as total
    FROM lost_found
    WHERE status = 'Found' AND category = 'Document' AND document_type IS NOT NULL AND document_type != ''
    GROUP BY document_type
    ORDER BY total DESC
");
while ($row = $res->fetch_assoc()) {
    $foundByDocumentType[] = $row;
}
$maxFoundDocCount = 0;
foreach ($foundByDocumentType as $d) {
    $maxFoundDocCount = max($maxFoundDocCount, (int)$d['total']);
}

/* =====================================================
   MOST COMMON "OTHER" ITEMS (LOST & FOUND)
   ===================================================== */

// "Other" category items among LOST reports, grouped by exact description text
$lostByOtherDesc = [];
$res = $conn->query("
    SELECT other_description, COUNT(*) as total
    FROM lost_found
    WHERE status = 'Lost' AND category = 'Other' AND other_description IS NOT NULL AND other_description != ''
    GROUP BY other_description
    ORDER BY total DESC
");
while ($row = $res->fetch_assoc()) {
    $lostByOtherDesc[] = $row;
}
$maxLostOtherCount = 0;
foreach ($lostByOtherDesc as $o) {
    $maxLostOtherCount = max($maxLostOtherCount, (int)$o['total']);
}

// "Other" category items among FOUND reports, grouped by exact description text
$foundByOtherDesc = [];
$res = $conn->query("
    SELECT other_description, COUNT(*) as total
    FROM lost_found
    WHERE status = 'Found' AND category = 'Other' AND other_description IS NOT NULL AND other_description != ''
    GROUP BY other_description
    ORDER BY total DESC
");
while ($row = $res->fetch_assoc()) {
    $foundByOtherDesc[] = $row;
}
$maxFoundOtherCount = 0;
foreach ($foundByOtherDesc as $o) {
    $maxFoundOtherCount = max($maxFoundOtherCount, (int)$o['total']);
}

function shortLabel($text, $limit = 40) {
    $text = trim($text);
    return strlen($text) > $limit ? substr($text, 0, $limit) . '…' : $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Nunito', Arial, sans-serif;
    overflow-x:hidden;
    background:#f5f6fa;
}

/* ================= SIDEBAR ================= */

.sidebar{
    position:fixed;
    top:15px;
    left:15px;
    bottom:15px;
    width:250px;
    background: white;
    border:1px solid #dee2e6;
    border-radius:12px;
    padding:20px 15px;
    display:flex;
    flex-direction:column;
    transition: transform 0.7s ease-in-out;
    z-index:1050;
}

.sidebar hr{
    border-color:#E7ECE9;
    margin:0 0 14px;
    opacity:1;
}

.sidebar .nav-section-label{
    font-size:10.5px;
    font-weight:700;
    color: #7C8A85;
    text-transform:uppercase;
    letter-spacing:.6px;
    padding:6px 15px 8px;
}

.sidebar a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:11px 14px;
    margin-bottom:4px;
    border-radius:10px;
    text-decoration:none;
    color:#4B5A54;
    font-weight:600;
    font-size:14px;
    position:relative;
    transition:.2s ease;
}

.sidebar a i{
    font-size:16px;
    width:18px;
    text-align:center;
    color:#8CA298;
    transition:.2s ease;
}

.sidebar a:hover{
    background: #E8F7EF;
    color: #198754;
}

.sidebar a:hover i{
    color: #198754;
}

.sidebar a.active{
    background:linear-gradient(135deg, #198754, #147a49);
    color:#fff;
    box-shadow:0 6px 14px rgba(25,135,84,0.25);
}

.sidebar a.active i{
    color:#fff;
}

.logout-btn{
    margin-top:auto;
    border:1px solid #ddd;
}

.logout-btn:hover{
    background:#FCEAED !important;
    color: #E1596B !important;
    border: 1px solid #E1596B;
}

.logout-btn:hover i{
    color: #E1596B !important;
}

/* ================= NAVBAR ================= */

.navbar-custom{
    position: sticky;
    top:15px;
    margin-top:15px;
    margin-left:290px;
    margin-right:15px;
    background:#fff;
    border:1px solid #dee2e6;
    border-radius:12px;
    padding:16px 25px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    z-index:1020;
}

.navbar-title{
    font-size:18px;
    font-weight: 900;
    margin:0;
}

.navbar-left{
    display:flex;
    align-items:center;
    gap:10px;
}

.navbar-right{
    display:flex;
    align-items:center;
    gap:15px;
}

.icon{
    font-size:20px;
    color:#6c757d;
    cursor:pointer;
}

.profile-img{
    width:35px;
    height:35px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #ddd;
}

/* ================= OVERLAY ================= */

#overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.3);
    backdrop-filter:blur(2px);
    opacity:0;
    visibility:hidden;
    transition:.3s;
    z-index:1040;
}

#overlay.show{
    opacity:1;
    visibility:visible;
}

.btn-close-outside{
    display:none;
}

.menu-toggle-btn{
    display:none;
}

/* ================= MAIN CONTENT ================= */

.main-content{
    margin-left:290px;
    margin-right:15px;
    margin-top:15px;
    padding-bottom:60px;
}

.page-heading{
    font-weight:800;
    font-size:21px;
    margin-bottom:2px;
    color: #0F1B2D;
}

.page-subheading{
    font-size:13px;
    color: #7C8A85;
    font-weight:600;
}

.btn-report-item{
    background: linear-gradient(135deg, #198754, #147a49);
    color:#fff;
    border-radius:10px;
    font-weight:700;
    font-size:13.5px;
    padding:10px 18px;
    border:none;
    display:flex;
    align-items:center;
    gap:8px;
}

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

.delta-up{background:#E8F7EF;color:#198754;}
.delta-down{background:#FCEAED;color:#E1596B;}

/* ====================== CHART / ACTIVITY CARD ===================== */

.dash-card{
    background:#fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:20px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
    height:100%;
}

.dash-card-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:16px;
}

.dash-card-title{
    font-weight:800;
    font-size:14.5px;
    color:#0F1B2D;
    margin-bottom:2px;
}

.dash-card-sub{
    font-size:11.5px;
    color:#7C8A85;
    font-weight:600;
}

.link-view-all{
    font-size:12px;
    font-weight:800;
    color:#198754;
    display:flex;
    align-items:center;
    gap:4px;
}

.chart-wrap{
    position:relative;
    height:230px;
}

/* ACTIVITY FEED */
.activity-item{
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:11px 0;
    border-bottom:1px solid #F0F2F1;
}

.activity-item:last-child{border-bottom:none;padding-bottom:0;}

.activity-icon{
    width:36px;
    height:36px;
    border-radius:9px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
    flex-shrink:0;
}

.activity-info .t{
    font-weight:700;
    font-size:12.5px;
    color:#0F1B2D;
    line-height:1.4;
}

.activity-info .t b{font-weight:800;}

.activity-info .time{
    font-size:10.5px;
    color:#9AA6A1;
    font-weight:700;
    margin-top:2px;
}

/* ====================== TABLE (RECENT ITEMS) ===================== */

.recent-table{
    width:100%;
    border-collapse:collapse;
}

.recent-table thead th{
    font-size:10.5px;
    font-weight:800;
    color:#7C8A85;
    text-transform:uppercase;
    letter-spacing:.4px;
    padding:0 10px 10px;
    text-align:left;
    border-bottom:1px solid #E7ECE9;
}

.recent-table tbody td{
    padding:12px 10px;
    font-size:12.5px;
    font-weight:600;
    color:#0F1B2D;
    border-bottom:1px solid #F0F2F1;
    vertical-align:middle;
}

.recent-table tbody tr:last-child td{border-bottom:none;}
.recent-table tbody tr:hover{background:#FAFEFC;}

.mini-item{
    display:flex;
    align-items:center;
    gap:10px;
}

.mini-icon{
    width:32px;
    height:32px;
    border-radius:8px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:13px;
    flex-shrink:0;
}

.badge-status{
    font-size:10px;
    font-weight:800;
    padding:4px 10px;
    border-radius:20px;
    display:inline-block;
}

.status-found{background:#E8F7EF;color:#198754;}
.status-matched{background:#FFF4E5;color:#B8860B;}
.status-claimed{background:#EAF1FF;color:#3366CC;}

/* ====================== PENDING CLAIMS ===================== */

.upcoming-item{
    display:flex;
    gap:12px;
    padding:10px 0;
    border-bottom:1px solid #F0F2F1;
}

.upcoming-item:last-child{border-bottom:none;padding-bottom:0;}

.upcoming-date{
    width:42px;
    height:42px;
    border-radius:10px;
    background:#F3FBF7;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
}

.upcoming-date .d{font-weight:900;font-size:14px;color:#198754;line-height:1;}
.upcoming-date .m{font-size:8.5px;font-weight:800;color:#198754;text-transform:uppercase;}

.upcoming-info .t{font-weight:800;font-size:12.5px;color:#0F1B2D;margin-bottom:2px;}
.upcoming-info .s{font-size:11px;color:#7C8A85;font-weight:600;}

/* ====================== CASH SPOTLIGHT ===================== */

.cash-card{
    background:#fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:22px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
    height:100%;
    display:flex;
    align-items:center;
    gap:16px;
}

.cash-card-icon{
    width:52px;
    height:52px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    flex-shrink:0;
}

.cash-card-label{
    font-size:12.5px;
    color:#7C8A85;
    font-weight:700;
}

.cash-card-value{
    font-size:26px;
    font-weight:800;
    letter-spacing:-.5px;
    color:#0F1B2D;
    margin:2px 0 4px;
}

.cash-card-meta{
    font-size:11.5px;
    color:#9AA6A1;
    font-weight:600;
}

/* ====================== FREQUENTLY LOST RANKING ===================== */

.rank-item{
    margin-bottom:16px;
}

.rank-item:last-child{
    margin-bottom:0;
}

.rank-item-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:6px;
}

.rank-item-label{
    display:flex;
    align-items:center;
    gap:8px;
    font-weight:700;
    font-size:12.5px;
    color:#0F1B2D;
}

.rank-item-dot{
    width:9px;
    height:9px;
    border-radius:50%;
    flex-shrink:0;
}

.rank-item-count{
    font-weight:800;
    font-size:12.5px;
    color:#0F1B2D;
}

.rank-item-count span{
    font-weight:600;
    font-size:10.5px;
    color:#9AA6A1;
}

.rank-bar-track{
    width:100%;
    height:8px;
    background:#F0F2F1;
    border-radius:20px;
    overflow:hidden;
}

.rank-bar-fill{
    height:100%;
    border-radius:20px;
}

/* ====================== CARD STACK (multiple cards in one column) ===================== */

.dash-card-stack{
    display:flex;
    flex-direction:column;
    gap:16px;
    height:100%;
}

.dash-card-stack .dash-card{
    height:auto;
}

/* ====================== QUICK ACTIONS ===================== */

.quick-action{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px;
    border:1px solid #EEF1F0;
    border-radius:11px;
    margin-bottom:10px;
    cursor:pointer;
    transition:.15s;
}

.quick-action:last-child{margin-bottom:0;}

.quick-action:hover{
    border-color:#c9e9d7;
    background:#FAFEFC;
}

.quick-action-icon{
    width:38px;
    height:38px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:15px;
    flex-shrink:0;
}

.quick-action-text{
    font-weight:800;
    font-size:12.5px;
    color:#0F1B2D;
}

.quick-action-text .sub{
    display:block;
    font-size:10.5px;
    color:#7C8A85;
    font-weight:600;
    margin-top:1px;
}

/* ================= MOBILE ================= */

@media(max-width:992px){

    .sidebar{
        top:0px;
        left:0px;
        bottom:0px;
        width:300px;
        border-radius:0;
        transform:translateX(-200%);
    }

    .sidebar.show{
        transform:translateX(0);
    }

    .sidebar .btn-close-outside {
        display: none;
    }

    .navbar-custom{
        margin:0;
        border-radius:0;
        top:0;
        border-left:none;
        border-right:none;
    }

    .main-content{
        margin-left:15px;
    }

    .menu-toggle-btn{
        display:block;
    }

    .btn-close-outside{
        position:absolute;
        top:10px;
        right:-50px;
        width:40px;
        height:40px;
        border-radius:50%;
        background:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        box-shadow:0 5px 15px rgba(0,0,0,0.2);
    }

    .sidebar.show .btn-close-outside{
        display:flex;
    }

    .recent-table{
        min-width:600px;
    }

    .table-scroll{
        overflow-x:auto;
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

    <hr>

    <div class="nav-section-label">Main</div>
    <a href="adminDashboard.php" class="active"> <i class="bi bi-speedometer2"></i> Dashboard </a>
    <a href="adminStaffList.php"> <i class="bi bi-people"></i> Staff </a>

    <a href="#"> <i class="bi bi-plus-lg"></i> Create </a>
    <a href="adminLostFoundList.php"> <i class="bi bi-journal-text"></i> List </a>
    <a href="adminLostFoundMatches.php"> <i class="bi bi-layers"></i> Match Items </a>
    <a href="adminLostFoundItems.php"> <i class="bi bi-search"></i> Found Items </a>
    <a href="adminLostFoundAuditTrail.php"> <i class="bi bi-clipboard-data"></i> Audit Trail </a>
    <a href="#"> <i class="bi bi-graph-up-arrow"></i> Statistic </a>
    <div class="nav-section-label">Other</div>
    <a href="adminLostFoundCalendar.php"> <i class="bi bi-calendar-event"></i> Calendar </a>
    <a href="adminChangePassword.php"> <i class="bi bi-lock" style="display: inline-block; transform: scaleX(1.4);"></i> Change Password </a>
    <a href="adminLogout.php" class="logout-btn"> <i class="bi bi-box-arrow-right"></i> Logout </a>
</div>

<!-- NAVBAR -->
<nav class="navbar-custom shadow-sm">
    <div class="navbar-left">
        <img src="https://picsum.photos/200" class="profile-img d-lg-none">
        <h5 class="navbar-title text-success" style="font-family:'Nunito', Arial, sans-serif;">
            Lost And Found Information System
        </h5>
    </div>

    <div class="navbar-right">
        <i class="fa-solid fa-bars icon menu-toggle-btn" onclick="toggleSidebar()"></i>
    </div>
</nav>

<!-- TOP NAVBAR -->

<!-- MAIN CONTENT -->
<div class="main-content">

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mt-4 mb-3">
        <div>
            <div class="page-heading">Welcome back, <?= htmlspecialchars($_SESSION['admin']['firstName'] ?? 'Admin') ?> 👋</div>
            <div class="page-subheading">Here's what's happening across campus today.</div>
        </div>
        <button class="btn-report-item"><i class="bi bi-plus-lg"></i> Report an Item</button>
    </div>

    <!-- KPI CARDS -->
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-lg-3 col-md-6 col-6">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="kpi-icon" style="background:#E8F7EF;color:#198754;"><i class="bi bi-box-seam"></i></div>
                </div>
                <div class="kpi-value"><?= $totalFound ?></div>
                <div class="kpi-label">Total Items Found</div>
                <span class="kpi-delta <?= $foundDelta >= 0 ? 'delta-up' : 'delta-down' ?>">
                    <i class="bi bi-arrow-<?= $foundDelta >= 0 ? 'up' : 'down' ?>-short"></i>
                    <?= abs($foundDelta) ?>% this month
                </span>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-6">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="kpi-icon" style="background:#F1EAFC;color:#7C4DFF;"><i class="bi bi-layers"></i></div>
                </div>
                <div class="kpi-value"><?= $totalMatched ?></div>
                <div class="kpi-label">Items Matched</div>
                <span class="kpi-delta <?= $matchedDelta >= 0 ? 'delta-up' : 'delta-down' ?>">
                    <i class="bi bi-arrow-<?= $matchedDelta >= 0 ? 'up' : 'down' ?>-short"></i>
                    <?= abs($matchedDelta) ?>% this month
                </span>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-6">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="kpi-icon" style="background:#EAF1FF;color:#3366CC;"><i class="bi bi-check2-circle"></i></div>
                </div>
                <div class="kpi-value"><?= $totalClaimed ?></div>
                <div class="kpi-label">Items Claimed</div>
                <span class="kpi-delta <?= $claimedDelta >= 0 ? 'delta-up' : 'delta-down' ?>">
                    <i class="bi bi-arrow-<?= $claimedDelta >= 0 ? 'up' : 'down' ?>-short"></i>
                    <?= abs($claimedDelta) ?>% this month
                </span>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-6">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="kpi-icon" style="background:#FCEAED;color:#E1596B;"><i class="bi bi-hourglass-split"></i></div>
                </div>
                <div class="kpi-value"><?= $pendingUnclaimed ?></div>
                <div class="kpi-label">Pending / Unclaimed</div>
                <span class="kpi-delta <?= $pendingDeltaAbs <= 0 ? 'delta-up' : 'delta-down' ?>">
                    <i class="bi bi-arrow-<?= $pendingDeltaAbs <= 0 ? 'down' : 'up' ?>-short"></i>
                    <?= abs($pendingDeltaAbs) ?> vs last month
                </span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">

        <!-- WEEKLY TREND CHART -->
        <div class="col-lg-8">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Weekly Activity</div>
                        <div class="dash-card-sub">Items found and items lost vs matched, last 7 days</div>
                    </div>
                    <a href="adminLostFoundList.php" class="link-view-all">Full Stats <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="chart-wrap">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- RECENT ACTIVITY FEED -->
        <div class="col-lg-4">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Recent Activity</div>
                        <div class="dash-card-sub">Latest actions across the system</div>
                    </div>
                </div>

                <?php if (empty($activity)): ?>
                    <p class="text-muted" style="font-size:12.5px;">No recent activity yet.</p>
                <?php else: ?>
                    <?php foreach ($activity as $a):
                        $iconMap = [
                            'logged'  => ['bi-box-seam', '#E8F7EF', '#198754'],
                            'matched' => ['bi-layers', '#F1EAFC', '#7C4DFF'],
                            'claimed' => ['bi-check2-circle', '#EAF1FF', '#3366CC'],
                        ];
                        [$icon, $bg, $color] = $iconMap[$a['type']];
                    ?>
                    <div class="activity-item">
                        <div class="activity-icon" style="background:<?= $bg ?>;color:<?= $color ?>;"><i class="bi <?= $icon ?>"></i></div>
                        <div class="activity-info">
                            <div class="t"><?= $a['text'] ?></div>
                            <div class="time"><?= timeAgo($a['time']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-3">

        <!-- RECENT ITEMS TABLE -->
        <div class="col-lg-8">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Recently Logged Items</div>
                        <div class="dash-card-sub">Latest entries across all locations</div>
                    </div>
                    <a href="adminLostFoundList.php" class="link-view-all">View All <i class="bi bi-arrow-right"></i></a>
                </div>

                <div class="table-scroll">
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentItems->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-muted">No items logged yet.</td></tr>
                            <?php else: ?>
                                <?php while ($row = $recentItems->fetch_assoc()):
                                    [$icon, $bg, $color] = $categoryIcon[$row['category']] ?? ['bi-box-seam', '#EDE9FE', '#6D28D9'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="mini-item">
                                            <div class="mini-icon" style="background:<?= $bg ?>;color:<?= $color ?>;"><i class="bi <?= $icon ?>"></i></div>
                                            <?= htmlspecialchars($row['category']) ?> item
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['reported_location'] ?? '—') ?></td>
                                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                    <td><?= statusBadge($row) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: QUICK ACTIONS + PENDING CLAIMS -->
        <div class="col-lg-4">
            <div class="dash-card-stack">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Quick Actions</div>
                    </div>
                </div>

                <div class="quick-action" onclick="location.href='adminLostFoundList.php'">
                    <div class="quick-action-icon" style="background:#E8F7EF;color:#198754;"><i class="bi bi-plus-lg"></i></div>
                    <div class="quick-action-text">Log New Item<span class="sub">Found or lost report</span></div>
                </div>

                <div class="quick-action" onclick="location.href='adminLostFoundMatches.php'">
                    <div class="quick-action-icon" style="background:#F1EAFC;color:#7C4DFF;"><i class="bi bi-layers"></i></div>
                    <div class="quick-action-text">Review Matches<span class="sub"><?= $totalMatched ?> total matches</span></div>
                </div>

                <div class="quick-action" onclick="location.href='adminLostFoundAuditTrail.php'">
                    <div class="quick-action-icon" style="background:#FFF4E5;color:#B8860B;"><i class="bi bi-clipboard-data"></i></div>
                    <div class="quick-action-text">View Audit Trail<span class="sub">Track every change</span></div>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Pending Claims</div>
                        <div class="dash-card-sub">Oldest unclaimed found items</div>
                    </div>
                </div>

                <?php if ($pendingClaims->num_rows === 0): ?>
                    <p class="text-muted" style="font-size:12.5px;">No pending claims. 🎉</p>
                <?php else: ?>
                    <?php while ($row = $pendingClaims->fetch_assoc()): ?>
                    <div class="upcoming-item">
                        <div class="upcoming-date">
                            <span class="d"><?= date('d', strtotime($row['created_at'])) ?></span>
                            <span class="m"><?= date('M', strtotime($row['created_at'])) ?></span>
                        </div>
                        <div class="upcoming-info">
                            <div class="t"><?= htmlspecialchars($row['category']) ?> item #<?= $row['id'] ?></div>
                            <div class="s"><?= htmlspecialchars($row['reported_location'] ?? 'Location unknown') ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mt-1">

        <!-- MOST FREQUENTLY LOST CATEGORIES -->
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Most Frequently Lost Items</div>
                        <div class="dash-card-sub">Ranked by category, all Lost reports</div>
                    </div>
                </div>

                <?php if (empty($lostByCategory)): ?>
                    <p class="text-muted" style="font-size:12.5px;">No lost reports yet.</p>
                <?php else: ?>
                    <?php foreach ($lostByCategory as $c):
                        [$bg, $color] = $categoryColors[$c['category']] ?? ['#EDE9FE', '#6D28D9'];
                        $pct = $maxCategoryCount > 0 ? round(($c['total'] / $maxCategoryCount) * 100) : 0;
                    ?>
                    <div class="rank-item">
                        <div class="rank-item-head">
                            <div class="rank-item-label">
                                <span class="rank-item-dot" style="background:<?= $color ?>;"></span>
                                <?= htmlspecialchars($c['category']) ?>
                            </div>
                            <div class="rank-item-count"><?= $c['total'] ?> <span>reports</span></div>
                        </div>
                        <div class="rank-bar-track">
                            <div class="rank-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- MOST COMMON LOST GADGET TYPES -->
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Most Common Lost Gadgets</div>
                        <div class="dash-card-sub">Breakdown of Lost reports under Gadget</div>
                    </div>
                </div>

                <?php if (empty($lostByGadgetType)): ?>
                    <p class="text-muted" style="font-size:12.5px;">No lost gadget reports yet.</p>
                <?php else: ?>
                    <?php foreach ($lostByGadgetType as $g):
                        $pct = $maxGadgetCount > 0 ? round(($g['total'] / $maxGadgetCount) * 100) : 0;
                    ?>
                    <div class="rank-item">
                        <div class="rank-item-head">
                            <div class="rank-item-label">
                                <span class="rank-item-dot" style="background:#1D4ED8;"></span>
                                <?= htmlspecialchars($g['gadget_type']) ?>
                            </div>
                            <div class="rank-item-count"><?= $g['total'] ?> <span>reports</span></div>
                        </div>
                        <div class="rank-bar-track">
                            <div class="rank-bar-fill" style="width:<?= $pct ?>%;background:#1D4ED8;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="row g-3 mt-1">

        <!-- MOST FREQUENTLY FOUND CATEGORIES -->
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Most Frequently Found Items</div>
                        <div class="dash-card-sub">Ranked by category, all Found reports</div>
                    </div>
                </div>

                <?php if (empty($foundByCategory)): ?>
                    <p class="text-muted" style="font-size:12.5px;">No found reports yet.</p>
                <?php else: ?>
                    <?php foreach ($foundByCategory as $c):
                        [$bg, $color] = $categoryColors[$c['category']] ?? ['#EDE9FE', '#6D28D9'];
                        $pct = $maxFoundCategoryCount > 0 ? round(($c['total'] / $maxFoundCategoryCount) * 100) : 0;
                    ?>
                    <div class="rank-item">
                        <div class="rank-item-head">
                            <div class="rank-item-label">
                                <span class="rank-item-dot" style="background:<?= $color ?>;"></span>
                                <?= htmlspecialchars($c['category']) ?>
                            </div>
                            <div class="rank-item-count"><?= $c['total'] ?> <span>reports</span></div>
                        </div>
                        <div class="rank-bar-track">
                            <div class="rank-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- MOST COMMON FOUND GADGET TYPES -->
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Most Common Found Gadgets</div>
                        <div class="dash-card-sub">Breakdown of Found reports under Gadget</div>
                    </div>
                </div>

                <?php if (empty($foundByGadgetType)): ?>
                    <p class="text-muted" style="font-size:12.5px;">No found gadget reports yet.</p>
                <?php else: ?>
                    <?php foreach ($foundByGadgetType as $g):
                        $pct = $maxFoundGadgetCount > 0 ? round(($g['total'] / $maxFoundGadgetCount) * 100) : 0;
                    ?>
                    <div class="rank-item">
                        <div class="rank-item-head">
                            <div class="rank-item-label">
                                <span class="rank-item-dot" style="background:#198754;"></span>
                                <?= htmlspecialchars($g['gadget_type']) ?>
                            </div>
                            <div class="rank-item-count"><?= $g['total'] ?> <span>reports</span></div>
                        </div>
                        <div class="rank-bar-track">
                            <div class="rank-bar-fill" style="width:<?= $pct ?>%;background:#198754;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="row g-3 mt-1">

        <!-- MOST COMMON LOST DOCUMENTS -->
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Most Common Lost Documents</div>
                        <div class="dash-card-sub">Breakdown of Lost reports under Document</div>
                    </div>
                </div>

                <?php if (empty($lostByDocumentType)): ?>
                    <p class="text-muted" style="font-size:12.5px;">No lost document reports yet.</p>
                <?php else: ?>
                    <?php foreach ($lostByDocumentType as $d):
                        $pct = $maxLostDocCount > 0 ? round(($d['total'] / $maxLostDocCount) * 100) : 0;
                    ?>
                    <div class="rank-item">
                        <div class="rank-item-head">
                            <div class="rank-item-label">
                                <span class="rank-item-dot" style="background:#B45309;"></span>
                                <?= htmlspecialchars($d['document_type']) ?>
                            </div>
                            <div class="rank-item-count"><?= $d['total'] ?> <span>reports</span></div>
                        </div>
                        <div class="rank-bar-track">
                            <div class="rank-bar-fill" style="width:<?= $pct ?>%;background:#B45309;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- MOST COMMON FOUND DOCUMENTS -->
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Most Common Found Documents</div>
                        <div class="dash-card-sub">Breakdown of Found reports under Document</div>
                    </div>
                </div>

                <?php if (empty($foundByDocumentType)): ?>
                    <p class="text-muted" style="font-size:12.5px;">No found document reports yet.</p>
                <?php else: ?>
                    <?php foreach ($foundByDocumentType as $d):
                        $pct = $maxFoundDocCount > 0 ? round(($d['total'] / $maxFoundDocCount) * 100) : 0;
                    ?>
                    <div class="rank-item">
                        <div class="rank-item-head">
                            <div class="rank-item-label">
                                <span class="rank-item-dot" style="background:#B45309;"></span>
                                <?= htmlspecialchars($d['document_type']) ?>
                            </div>
                            <div class="rank-item-count"><?= $d['total'] ?> <span>reports</span></div>
                        </div>
                        <div class="rank-bar-track">
                            <div class="rank-bar-fill" style="width:<?= $pct ?>%;background:#B45309;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="row g-3 mt-1">

        <!-- MOST COMMON LOST OTHER ITEMS -->
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Most Common Lost "Other" Items</div>
                        <div class="dash-card-sub">Breakdown of Lost reports under Other</div>
                    </div>
                </div>

                <?php if (empty($lostByOtherDesc)): ?>
                    <p class="text-muted" style="font-size:12.5px;">No lost "Other" reports yet.</p>
                <?php else: ?>
                    <?php foreach ($lostByOtherDesc as $o):
                        $pct = $maxLostOtherCount > 0 ? round(($o['total'] / $maxLostOtherCount) * 100) : 0;
                    ?>
                    <div class="rank-item">
                        <div class="rank-item-head">
                            <div class="rank-item-label">
                                <span class="rank-item-dot" style="background:#6D28D9;"></span>
                                <?= htmlspecialchars(shortLabel($o['other_description'])) ?>
                            </div>
                            <div class="rank-item-count"><?= $o['total'] ?> <span>reports</span></div>
                        </div>
                        <div class="rank-bar-track">
                            <div class="rank-bar-fill" style="width:<?= $pct ?>%;background:#6D28D9;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- MOST COMMON FOUND OTHER ITEMS -->
        <div class="col-lg-6">
            <div class="dash-card">
                <div class="dash-card-head">
                    <div>
                        <div class="dash-card-title">Most Common Found "Other" Items</div>
                        <div class="dash-card-sub">Breakdown of Found reports under Other</div>
                    </div>
                </div>

                <?php if (empty($foundByOtherDesc)): ?>
                    <p class="text-muted" style="font-size:12.5px;">No found "Other" reports yet.</p>
                <?php else: ?>
                    <?php foreach ($foundByOtherDesc as $o):
                        $pct = $maxFoundOtherCount > 0 ? round(($o['total'] / $maxFoundOtherCount) * 100) : 0;
                    ?>
                    <div class="rank-item">
                        <div class="rank-item-head">
                            <div class="rank-item-label">
                                <span class="rank-item-dot" style="background:#6D28D9;"></span>
                                <?= htmlspecialchars(shortLabel($o['other_description'])) ?>
                            </div>
                            <div class="rank-item-count"><?= $o['total'] ?> <span>reports</span></div>
                        </div>
                        <div class="rank-bar-track">
                            <div class="rank-bar-fill" style="width:<?= $pct ?>%;background:#6D28D9;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="row g-3 mt-1">

        <!-- HIGHEST CASH LOST -->
        <div class="col-lg-6">
            <div class="cash-card">
                <div class="cash-card-icon" style="background:#FCEAED;color:#E1596B;"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="cash-card-label">Highest Cash Lost</div>
                    <?php if ($highestCashLost && $highestCashLost['cash_amount'] !== null): ?>
                        <div class="cash-card-value">₱<?= number_format($highestCashLost['cash_amount'], 2) ?></div>
                        <div class="cash-card-meta">
                            <?= htmlspecialchars($highestCashLost['reported_location'] ?? 'Location unknown') ?>
                            &middot; <?= date('M j, Y', strtotime($highestCashLost['created_at'])) ?>
                        </div>
                    <?php else: ?>
                        <div class="cash-card-value">₱0.00</div>
                        <div class="cash-card-meta">No cash reported lost yet</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- HIGHEST CASH RETURNED -->
        <div class="col-lg-6">
            <div class="cash-card">
                <div class="cash-card-icon" style="background:#E8F7EF;color:#198754;"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="cash-card-label">Highest Cash Returned</div>
                    <?php if ($highestCashReturned && $highestCashReturned['cash_amount'] !== null): ?>
                        <div class="cash-card-value">₱<?= number_format($highestCashReturned['cash_amount'], 2) ?></div>
                        <div class="cash-card-meta">
                            <?= htmlspecialchars($highestCashReturned['reported_location'] ?? 'Location unknown') ?>
                            <?php if (!empty($highestCashReturned['claimed_date'])): ?>
                                &middot; <?= date('M j, Y', strtotime($highestCashReturned['claimed_date'])) ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="cash-card-value">₱0.00</div>
                        <div class="cash-card-meta">No cash returned/claimed yet</div>
                    <?php endif; ?>
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

Chart.defaults.font.family = "'Nunito', Arial, sans-serif";

new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($weeklyLabels) ?>,
        datasets: [
            {
                label: 'Found',
                data: <?= json_encode($foundSeries) ?>,
                backgroundColor: '#198754',
                borderRadius: 6,
                maxBarThickness: 18
            },
            {
                label: 'Lost',
                data: <?= json_encode($lostSeries) ?>,
                backgroundColor: '#E1596B',
                borderRadius: 6,
                maxBarThickness: 18
            },
            {
                label: 'Matched',
                data: <?= json_encode($matchedSeries) ?>,
                backgroundColor: '#c9e9d7',
                borderRadius: 6,
                maxBarThickness: 18
            }
        ]
    },
    options: {
        responsive:true,
        maintainAspectRatio:false,
        plugins:{
            legend:{
                position:'top',
                align:'end',
                labels:{ boxWidth:8, boxHeight:8, usePointStyle:true, font:{size:11, weight:'700'} }
            }
        },
        scales:{
            y:{ beginAtZero:true, grid:{ color:'#F0F2F1' }, ticks:{ font:{size:11} } },
            x:{ grid:{ display:false }, ticks:{ font:{size:11} } }
        }
    }
});

// check session every 15 mins
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