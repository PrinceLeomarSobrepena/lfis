<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';
date_default_timezone_set('Asia/Manila');

$resolved_by = $_SESSION['admin']['id'];

// CSRF (same approach as the Create page)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| RESOLVE MATCH
|--------------------------------------------------------------------------
*/

if(isset($_POST['resolve_match'])){

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF validation failed");
    }

    if(
        isset($_POST['lost_id']) &&
        isset($_POST['found_id'])
    ){

        $lost_id  = (int)$_POST['lost_id'];
        $found_id = (int)$_POST['found_id'];

        // RESOLVE MATCH
        $stmt = $conn->prepare("
            UPDATE lost_found_matches
            SET is_resolved = 1,
                resolved_at = NOW()
            WHERE lost_id = ?
               OR found_id = ?
        ");

        $stmt->bind_param("ii", $lost_id, $found_id);
        $stmt->execute();

        // UPDATE LOST_FOUND
        $update = $conn->prepare("
            UPDATE lost_found
            SET is_resolved = 1, resolved_by = ?, resolved_at = NOW()
            WHERE id IN (?, ?)
        ");

        $update->bind_param("iii", $resolved_by, $lost_id, $found_id);
        $update->execute();

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $_SESSION['sweet_alert'] = [
            'icon'  => 'success',
            'title' => 'Match Confirmed!',
            'text'  => 'The lost and found items have been marked as resolved.'
        ];

        header("Location: adminLostFoundMatches.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

// lowercase + trim + collapse spaces (Create page compares with strtolower too)
function normalize($s){
    return strtolower(trim(preg_replace('/\s+/', ' ', (string)$s)));
}

// 0..1 text similarity (1 = identical, 0 = empty / nothing alike)
function textSim($a, $b){
    $a = normalize($a);
    $b = normalize($b);

    if($a === '' || $b === '') return 0;
    if($a === $b) return 1;

    similar_text($a, $b, $pct);
    return $pct / 100;
}

// 1 when reported on the same day, 0 when 7+ days apart
function dateSim($a, $b){
    $days = abs(strtotime($a) - strtotime($b)) / 86400;
    return max(0, 1 - ($days / 7));
}

// build one item array from the query row using a prefix (lost_ / found_)
function buildItem($row, $p){
    return [
        'id'                 => $row[$p.'id'],
        'category'           => $row[$p.'category'],
        'location'           => $row[$p.'location'],
        'date'               => $row[$p.'date'],
        'image'              => $row[$p.'image'],
        'reporter'           => $row[$p.'reporter'],
        'cash_amount'        => $row[$p.'cash'],
        'gadget_type'        => $row[$p.'gadget_type'],
        'gadget_brand'       => $row[$p.'gadget_brand'],
        'gadget_color'       => $row[$p.'gadget_color'],
        'gadget_description' => $row[$p.'gadget_desc'],
        'document_type'      => $row[$p.'doc_type'],
        'document_name'      => $row[$p.'doc_name'],
        'other_title'        => $row[$p.'other_title'],
        'other_description'  => $row[$p.'other_desc']
    ];
}

// Same rules the Create page uses to decide if two items are a match
function groupKeyFor($l, $f, $bucket){

    if($l['category'] !== $f['category']) return '';

    switch($l['category']){

        case 'Cash':
            if((float)$l['cash_amount'] === (float)$f['cash_amount']){
                return 'cash_' . (float)$l['cash_amount'] . '_' . $bucket;
            }
            break;

        case 'Gadget':
            if(
                normalize($l['gadget_type'])  === normalize($f['gadget_type']) &&
                normalize($l['gadget_brand']) === normalize($f['gadget_brand']) &&
                normalize($l['gadget_color']) === normalize($f['gadget_color'])
            ){
                return 'gadget_' . md5(
                    normalize($l['gadget_type']) . '|' .
                    normalize($l['gadget_brand']) . '|' .
                    normalize($l['gadget_color'])
                ) . '_' . $bucket;
            }
            break;

        case 'Document':
            if(normalize($l['document_name']) === normalize($f['document_name'])){
                return 'document_' . md5(normalize($l['document_name'])) . '_' . $bucket;
            }
            break;

        case 'Other':
            if(
                normalize($l['other_title'])       === normalize($f['other_title']) &&
                normalize($l['other_description']) === normalize($f['other_description'])
            ){
                return 'other_' . md5(
                    normalize($l['other_title']) . '|' .
                    normalize($l['other_description'])
                ) . '_' . $bucket;
            }
            break;
    }

    return '';
}

/*
|--------------------------------------------------------------------------
| MATCH SCORE (0 - 100)
|--------------------------------------------------------------------------
| Cash     : Amount 60 | Location 25 | Date 15
| Gadget   : Type 20 | Brand 20 | Color 15 | Description 20 | Location 15 | Date 10
| Document : Type 25 | Name on document 40 | Location 20 | Date 15
| Other    : Item name 30 | Description 30 | Location 25 | Date 15
*/
function scorePair($l, $f){

    $parts = [];

    $add = function($label, $max, $sim) use (&$parts){
        $parts[] = [
            'label' => $label,
            'max'   => $max,
            'got'   => round($max * $sim, 1)
        ];
    };

    $locL = normalize($l['location']);
    $locF = normalize($f['location']);
    $locSim  = ($locL !== '' && $locL === $locF) ? 1 : 0;
    $dateSim = dateSim($l['date'], $f['date']);

    switch($l['category']){

        case 'Cash':
            $a = (float)$l['cash_amount'];
            $b = (float)$f['cash_amount'];
            $top = max($a, $b);
            $amountSim = ($top > 0) ? min($a, $b) / $top : 0;

            $add('Amount', 60, $amountSim);
            $add('Location', 25, $locSim);
            $add('Date proximity', 15, $dateSim);
            break;

        case 'Gadget':
            $add('Type', 20, textSim($l['gadget_type'], $f['gadget_type']));
            $add('Brand / Model', 20, textSim($l['gadget_brand'], $f['gadget_brand']));
            $add('Color', 15, textSim($l['gadget_color'], $f['gadget_color']));
            $add('Description', 20, textSim($l['gadget_description'], $f['gadget_description']));
            $add('Location', 15, $locSim);
            $add('Date proximity', 10, $dateSim);
            break;

        case 'Document':
            $add('Document type', 25, textSim($l['document_type'], $f['document_type']));
            $add('Name on document', 40, textSim($l['document_name'], $f['document_name']));
            $add('Location', 20, $locSim);
            $add('Date proximity', 15, $dateSim);
            break;

        default: // Other
            $add('Item name', 30, textSim($l['other_title'], $f['other_title']));
            $add('Description', 30, textSim($l['other_description'], $f['other_description']));
            $add('Location', 25, $locSim);
            $add('Date proximity', 15, $dateSim);
            break;
    }

    $total = 0;
    foreach($parts as $p) $total += $p['got'];

    return [
        'total' => (int)max(0, min(100, round($total))),
        'parts' => $parts
    ];
}

function renderItemName($item){
    if($item['category'] === 'Cash'){
        return '₱' . number_format((float)$item['cash_amount'], 2) . ' cash';
    } elseif($item['category'] === 'Gadget'){
        return trim($item['gadget_color'] . ' ' . $item['gadget_brand'] . ' ' . $item['gadget_type']);
    } elseif($item['category'] === 'Document'){
        return trim($item['document_type'] . ' - ' . $item['document_name']);
    } else {
        return $item['other_title'];
    }
}

function renderItemDesc($item){
    if($item['category'] === 'Gadget')  return trim((string)$item['gadget_description']);
    if($item['category'] === 'Other')   return trim((string)$item['other_description']);
    return '';
}

function hasPhoto($item){
    return !empty($item['image']) && $item['image'] !== 'default.jpg';
}

function categoryIcon($category){
    switch($category){
        case 'Cash': return 'bi-cash-coin';
        case 'Gadget': return 'bi-phone';
        case 'Document': return 'bi-file-earmark-text';
        default: return 'bi-box-seam';
    }
}

/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$query = "

SELECT

    m.id AS match_id,

    l.id AS lost_id,
    l.category AS lost_category,
    l.reported_location AS lost_location,
    l.created_at AS lost_date,
    l.image AS lost_image,
    l.reporter_name AS lost_reporter,
    l.cash_amount AS lost_cash,
    l.gadget_type AS lost_gadget_type,
    l.gadget_brand AS lost_gadget_brand,
    l.gadget_color AS lost_gadget_color,
    l.gadget_description AS lost_gadget_desc,
    l.document_type AS lost_doc_type,
    l.document_name AS lost_doc_name,
    l.other_title AS lost_other_title,
    l.other_description AS lost_other_desc,

    f.id AS found_id,
    f.category AS found_category,
    f.reported_location AS found_location,
    f.created_at AS found_date,
    f.image AS found_image,
    f.reporter_name AS found_reporter,
    f.cash_amount AS found_cash,
    f.gadget_type AS found_gadget_type,
    f.gadget_brand AS found_gadget_brand,
    f.gadget_color AS found_gadget_color,
    f.gadget_description AS found_gadget_desc,
    f.document_type AS found_doc_type,
    f.document_name AS found_doc_name,
    f.other_title AS found_other_title,
    f.other_description AS found_other_desc

FROM lost_found_matches m

INNER JOIN lost_found l ON m.lost_id = l.id
INNER JOIN lost_found f ON m.found_id = f.id

WHERE m.is_resolved = 0

ORDER BY m.matched_at DESC

";

$result = $conn->query($query);

$grouped = [];
$cutoff  = strtotime('-7 days');

while($row = $result->fetch_assoc()){

    $lost  = buildItem($row, 'lost_');
    $found = buildItem($row, 'found_');

    // AGE CHECK
    $isLostRecent  = (strtotime($lost['date'])  >= $cutoff);
    $isFoundRecent = (strtotime($found['date']) >= $cutoff);
    $timeBucket    = ($isLostRecent && $isFoundRecent) ? 'recent' : 'old';

    $groupKey = groupKeyFor($lost, $found, $timeBucket);

    if($groupKey !== ''){
        $grouped[$groupKey]['lost'][$lost['id']]    = $lost;
        $grouped[$groupKey]['found'][$found['id']]  = $found;
    }
}

// SCORE every lost x found combination inside each group
foreach($grouped as $key => $group){

    $scores  = [];
    $bestKey = '';
    $bestVal = -1;

    foreach($group['lost'] as $lid => $lost){
        foreach($group['found'] as $fid => $found){
            $pairKey = $lid . '-' . $fid;
            $scores[$pairKey] = scorePair($lost, $found);

            if($scores[$pairKey]['total'] > $bestVal){
                $bestVal = $scores[$pairKey]['total'];
                $bestKey = $pairKey;
            }
        }
    }

    $grouped[$key]['scores'] = $scores;
    $grouped[$key]['best']   = $bestKey;
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- font style -->
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Spinner styles */
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

.match-suggest-card{
    background:#fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:18px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
    margin-bottom:14px;
    transition:border-color .15s ease;
}

/* card whose score breakdown is currently open in the right panel */
.match-form.bd-active .match-suggest-card{
    border-color:#BFE3CF;
}

.match-suggest-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:14px;
    flex-wrap:wrap;
    gap:8px;
}

.head-badges{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:8px;
}

.confidence-badge{
    display:flex;
    align-items:center;
    font-weight:800;
    font-size:11px;
    padding:5px 12px;
    border-radius:20px;
    gap:6px;
}

/* badge that acts as a button (opens Score breakdown in the right panel) */
button.confidence-badge{
    border:none;
    cursor:pointer;
    font-family:inherit;
    transition:box-shadow .15s ease, filter .15s ease;
}

button.confidence-badge:hover{
    filter:brightness(.96);
}

button.confidence-badge.active{
    box-shadow:0 0 0 2px currentColor;
}

.conf-high{background: #E8F7EF;color: #198754;}
.conf-med{background: #FFF4E5;color: #B8860B;}
.conf-low{background: #FCEAED;color: #D9534F;}

.match-pair{
    display:grid;
    grid-template-columns:1fr 42px 1fr;
    gap:12px;
    align-items:start;
}

.match-col{
    display:flex;
    flex-direction:column;
    gap:10px;
}

/* the clickable card itself - wraps a hidden radio input */
.match-side{
    display:flex;
    align-items:center;
    border: 1px solid #EEF1F0;
    border-radius:11px;
    padding:12px;
    gap:11px;
    cursor:pointer;
    background: #fff;
    transition:border-color .15s ease, background .15s ease, box-shadow .15s ease;
}

.match-side:hover{
    border-color:#BFE3CF;
}

/* hide the actual radio, the whole card acts as the control */
.match-side input[type="radio"]{
    position:absolute;
    opacity:0;
    width:0;
    height:0;
    pointer-events:none;
}

/* SELECTED STATE - this is the part that shows the pick */
.match-side.selected{
    border-color: #198754;
    border: 2px solid #198754;
}

.match-side-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:42px;
    height:42px;
    font-size:22px;
    border-radius:10px;
    flex-shrink:0;
    overflow:hidden;
}

.match-side-icon img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.match-side-info .tag{
    font-size:9.5px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.3px;
    margin-bottom:2px;
}

.match-side-info .nm{
    font-weight:800;
    font-size:13px;
    color: #0F1B2D;
    margin-bottom:2px;
}

.match-side-info .desc{
    font-weight:600;
    font-size:11px;
    font-style:italic;
    color: #7C8A85;
    margin-bottom:2px;
}

.match-side-info .meta{
    font-weight:600;
    font-size:11px;
    color: #7C8A85;
}

.match-link-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:42px;
    height:42px;
    font-size:25px;
    color: #198754;
    background: #F3FBF7;
    border-radius:50%;
    margin:0 auto;
    align-self:center;
}

/* per-item percentage inside each lost / found card */
.item-score{
    display:flex;
    flex-direction:column;
    align-items:center;
    margin-left:auto;
    flex-shrink:0;
    min-width:58px;
    padding:6px 8px;
    border-radius:10px;
    line-height:1.15;
}

.item-score b{
    font-weight:800;
    font-size:14px;
}

.item-score small{
    font-weight:700;
    font-size:9px;
    opacity:.85;
    white-space:nowrap;
}

.match-suggest-actions{
    display:flex;
    justify-content:flex-end;
    gap:8px;
    margin-top:14px;
}

.btn-confirm-match{
    background:linear-gradient(135deg, #198754, #147a49);
    color: #fff;
    border:none;
    padding:9px 18px;
    border-radius:9px;
    font-weight:700;
    font-size:12.5px;
    display:flex;
    align-items:center;
    gap:6px;
}

.btn-confirm-match:disabled{
    opacity:.5;
    cursor:not-allowed;
}

.match-empty{
    text-align:center;
    padding:40px 20px;
    color:#7C8A85;
    font-weight:600;
}

/* ====================== LEGEND / SCORE BREAKDOWN PANEL ===================== */
.legend-sticky{
    position:sticky;
    top:15px;
    margin-top: 29px;
}

.legend-card{
    background:#fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:20px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
}

.legend-card h6{
    font-weight:800;
    font-size:13.5px;
    color:#0F1B2D;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.legend-section{
    font-weight:800;
    font-size:10.5px;
    color:#9AA6A1;
    text-transform:uppercase;
    letter-spacing:.4px;
    margin-top:12px;
}

.legend-item{
    display:flex;
    align-items:center;
    gap:10px;
    padding:8px 0;
}

.legend-dot{
    width:8px;
    height:8px;
    border-radius:50%;
    flex-shrink:0;
}

.legend-ico{
    width:8px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:13px;
    flex-shrink:0;
    overflow:visible;
}

.legend-label{
    font-weight:700;
    font-size:12.5px;
    color: #4B5A54;
}

.legend-desc{
    font-weight:600;
    font-size:11px;
    color: #9AA6A1;
}

.legend-summary-tip{
    display:flex;
    align-items:flex-start;
    font-weight:600;
    font-size:11.5px;
    color:#147a49;
    padding:12px 14px;
    background:#E8F7EF;
    border-radius:10px;
    gap:8px;
    line-height:1.5;
    margin-top:12px;
}

.legend-summary-tip i{
    margin-top:1px;
}

.panel-back{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-weight:700;
    font-size:12px;
    color:#198754;
    cursor:pointer;
    margin-bottom:12px;
}

.panel-back:hover{
    text-decoration:underline;
}

/* breakdown header (pair + overall %) */
.bd-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin:4px 0 6px;
}

.bd-pair{
    font-weight:800;
    font-size:12.5px;
    color:#0F1B2D;
}

.bd-which{
    font-weight:700;
    font-size:11px;
    color:#7C8A85;
}

.bd-total{
    display:flex;
    flex-direction:column;
    align-items:center;
    min-width:64px;
    padding:6px 10px;
    border-radius:10px;
    line-height:1.15;
    flex-shrink:0;
}

.bd-total b{
    font-weight:800;
    font-size:16px;
}

.bd-total small{
    font-weight:700;
    font-size:9px;
    opacity:.85;
    white-space:nowrap;
}

/* one block per lost x found pair */
.bd-block{
    border:1px solid #EEF1F0;
    border-radius:11px;
    padding:12px;
    margin-top:10px;
}

.bd-block.primary{
    border:2px solid #198754;
    background:#FAFEFC;
}

.bd-block .bd-head{
    margin:0 0 2px;
}

@media(min-width: 1200px){
    #breakdownPanel{
        max-height:calc(100vh - 40px);
        overflow-y:auto;
    }
}

/* score rows */
.score-row{
    display:grid;
    grid-template-columns:105px 1fr 48px;
    gap:10px;
    align-items:center;
    margin-top:10px;
    font-weight:700;
    font-size:11.5px;
    color: #4B5A54;
}

.score-row .bar{
    height:6px;
    background:#EEF1F0;
    border-radius:6px;
    overflow:hidden;
}

.score-row .fill{
    height:100%;
    border-radius:6px;
}

.fill-high{background:#198754;}
.fill-med{background:#F0B429;}
.fill-low{background:#E1596B;}

.score-row .pts{
    text-align:right;
    color:#7C8A85;
}

@media(max-width: 1199px){
    .legend-sticky{
        position:static;
    }
}

@media(max-width: 992px){
    .match-pair{
        grid-template-columns:1fr;
    }

    .match-link-icon{
        transform:rotate(90deg);
    }
}
</style>
</head>
<body>
    <?php if (!empty($_SESSION['sweet_alert'])): ?>
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        Swal.fire({
            icon: <?= json_encode($_SESSION['sweet_alert']['icon']) ?>,
            title: <?= json_encode($_SESSION['sweet_alert']['title']) ?>,
            text: <?= json_encode($_SESSION['sweet_alert']['text']) ?>,
            confirmButtonColor: "#198754"
        });
    });
    </script>
    <?php unset($_SESSION['sweet_alert']); endif; ?>

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
        <a href="adminStaffList.php"> <i class="bi bi-people"></i> Staff </a>
        <a href="adminLostFoundList.php"> <i class="bi bi-journal-text"></i> List </a>
        <a href="adminLostFoundCreate.php" style="margin-left: 20px;">
            <i class="bi bi-arrow-return-right"></i>
            <span style="padding-left:7px">Create</span>
        </a>
        <a href="adminLostFoundMatches.php" class="active"> <i class="bi bi-layers"></i> Match Items </a>
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
            <div class="page-heading">Match Items</div>
            <div class="page-subheading">Pair lost reports with found items to close the loop.</div>
        </div>

        <div class="row g-3">

            <!-- SUGGESTED MATCHES (LEFT) -->
            <div class="col-xl-9 col-lg-12">

                <div class="mb-2" style="font-weight:800;font-size:13.5px;color:#0F1B2D;display:flex;align-items:center;gap:8px;">
                    <i class="bi bi-stars" style="color:#198754;"></i> Suggested Matches
                </div>

                <?php if(empty($grouped)): ?>

                    <div class="match-suggest-card match-empty">
                        <i class="bi bi-inboxes" style="font-size:28px;color:#BFCBC5;"></i>
                        <div class="mt-2">No unresolved matches right now.</div>
                    </div>

                <?php else: ?>

                    <?php foreach($grouped as $groupKey => $group):

                        $isRecent = (substr($groupKey, -7) === '_recent');
                    ?>

                    <form method="POST" class="match-form"
                          data-scores="<?= e(json_encode($group['scores'])) ?>"
                          data-best="<?= e($group['best']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                        <div class="match-suggest-card">

                            <div class="match-suggest-head">
                                <span style="font-size:11.5px;color:#7C8A85;font-weight:700;">Scored by item details, location &amp; date proximity</span>

                                <div class="head-badges">
                                    <?php if($isRecent): ?>
                                        <button type="button" class="confidence-badge conf-high btn-breakdown" title="View score breakdown">
                                            <i class="bi bi-lightning-charge-fill"></i> Recent Match <i class="bi bi-chevron-right"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="confidence-badge conf-med btn-breakdown" title="View score breakdown">
                                            <i class="bi bi-clock-history"></i> Older Match <i class="bi bi-chevron-right"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="match-pair">

                                <!-- LOST COLUMN -->
                                <div class="match-col">
                                    <?php foreach($group['lost'] as $lost): ?>
                                        <label class="match-side">
                                            <input type="radio" name="lost_id" value="<?= (int)$lost['id'] ?>" required>

                                            <div class="match-side-icon" style="background:#FCEAED;color:#E1596B;">
                                                <?php if(hasPhoto($lost)): ?>
                                                    <img src="../uploads/<?= e($lost['image']) ?>" alt="">
                                                <?php else: ?>
                                                    <i class="bi <?= categoryIcon($lost['category']) ?>"></i>
                                                <?php endif; ?>
                                            </div>

                                            <div class="match-side-info">
                                                <div class="tag" style="color:#E1596B;">Lost #<?= (int)$lost['id'] ?></div>
                                                <div class="nm"><?= e(renderItemName($lost)) ?></div>
                                                <?php if(renderItemDesc($lost) !== ''): ?>
                                                    <div class="desc"><?= e(renderItemDesc($lost)) ?></div>
                                                <?php endif; ?>
                                                <div class="meta"><?= e($lost['location']) ?> • Reported <?= date('M d, Y', strtotime($lost['date'])) ?></div>
                                                <?php if(!empty($lost['reporter'])): ?>
                                                    <div class="meta">By <?= e($lost['reporter']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <!-- LINK ICON -->
                                <div class="match-link-icon"><i class="bi bi-link-45deg"></i></div>

                                <!-- FOUND COLUMN -->
                                <div class="match-col">
                                    <?php foreach($group['found'] as $found): ?>
                                        <label class="match-side">
                                            <input type="radio" name="found_id" value="<?= (int)$found['id'] ?>" required>

                                            <div class="match-side-icon" style="background:#E8F7EF;color:#198754;">
                                                <?php if(hasPhoto($found)): ?>
                                                    <img src="../uploads/<?= e($found['image']) ?>" alt="">
                                                <?php else: ?>
                                                    <i class="bi <?= categoryIcon($found['category']) ?>"></i>
                                                <?php endif; ?>
                                            </div>

                                            <div class="match-side-info">
                                                <div class="tag" style="color:#198754;">Found #<?= (int)$found['id'] ?></div>
                                                <div class="nm"><?= e(renderItemName($found)) ?></div>
                                                <?php if(renderItemDesc($found) !== ''): ?>
                                                    <div class="desc"><?= e(renderItemDesc($found)) ?></div>
                                                <?php endif; ?>
                                                <div class="meta"><?= e($found['location']) ?> • Found <?= date('M d, Y', strtotime($found['date'])) ?></div>
                                                <?php if(!empty($found['reporter'])): ?>
                                                    <div class="meta">By <?= e($found['reporter']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                            </div>

                            <div class="match-suggest-actions">
                                <button type="submit" name="resolve_match" class="btn-confirm-match">
                                    <i class="bi bi-check-lg"></i> Confirm Match
                                </button>
                            </div>

                        </div>
                    </form>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <!-- RIGHT PANEL: LEGEND (default) / SCORE BREAKDOWN (pag pinindot yung Recent / Older Match) -->
            <div class="col-xl-3 col-lg-12">
                <div class="legend-sticky">

                    <!-- LEGEND (default view) -->
                    <div class="legend-card" id="legendPanel">
                        <h6>Legend</h6>

                        <div class="legend-section">Items</div>

                        <div class="legend-item">
                            <span class="legend-dot" style="background:#E1596B;"></span>
                            <div>
                                <div class="legend-label">Lost Report</div>
                                <div class="legend-desc">Item reported as lost</div>
                            </div>
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot" style="background:#198754;"></span>
                            <div>
                                <div class="legend-label">Found Report</div>
                                <div class="legend-desc">Item turned in / reported found</div>
                            </div>
                        </div>

                        <div class="legend-section">Match type</div>

                        <div class="legend-item">
                            <span class="legend-ico" style="color:#198754;"><i class="bi bi-lightning-charge-fill"></i></span>
                            <div>
                                <div class="legend-label">Recent Match</div>
                                <div class="legend-desc">Both reports are within the last 7 days</div>
                            </div>
                        </div>

                        <div class="legend-item">
                            <span class="legend-ico" style="color:#B8860B;"><i class="bi bi-clock-history"></i></span>
                            <div>
                                <div class="legend-label">Older Match</div>
                                <div class="legend-desc">One or both reports are older than 7 days</div>
                            </div>
                        </div>

                        <div class="legend-section">Match score</div>

                        <div class="legend-item">
                            <span class="legend-dot" style="background:#198754;"></span>
                            <div>
                                <div class="legend-label">80% and above</div>
                                <div class="legend-desc">Strong match</div>
                            </div>
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot" style="background:#F0B429;"></span>
                            <div>
                                <div class="legend-label">50% - 79%</div>
                                <div class="legend-desc">Possible match</div>
                            </div>
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot" style="background:#E1596B;"></span>
                            <div>
                                <div class="legend-label">Below 50%</div>
                                <div class="legend-desc">Weak match</div>
                            </div>
                        </div>

                        <div class="legend-summary-tip">
                            <i class="bi bi-info-circle"></i>
                            <span>Click the Recent / Older Match button on a card to see its score breakdown here.</span>
                        </div>
                    </div>

                    <!-- SCORE BREAKDOWN (hidden by default) -->
                    <div class="legend-card" id="breakdownPanel" style="display:none;">
                        <div class="panel-back" onclick="showLegend()">
                            <i class="bi bi-arrow-left"></i> Back to Legend
                        </div>

                        <h6><span><i class="bi bi-bar-chart-line"></i>&nbsp; Score Breakdown</span></h6>

                        <div class="bd-which" id="breakdownCount"></div>

                        <div id="breakdownList"></div>

                        <div class="legend-summary-tip">
                            <i class="bi bi-info-circle"></i>
                            <span>All possible pairs for this card are listed. Select the lost and found items on the card to move that pair to the top.</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>


<!-- spinner on confirm -->
<script>
document.querySelectorAll('.match-form').forEach(form => {
    form.addEventListener('submit', () => {
        document.getElementById('spinner').style.display = 'flex';
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

<!-- MATCH SCORE + CARD SELECTION + RIGHT PANEL -->
<script>
function scoreLevel(pct){
    if(pct >= 80) return 'high';
    if(pct >= 50) return 'med';
    return 'low';
}

const legendPanel    = document.getElementById('legendPanel');
const breakdownPanel = document.getElementById('breakdownPanel');
const bdList         = document.getElementById('breakdownList');
const bdCount        = document.getElementById('breakdownCount');

// the card whose breakdown is currently shown in the right panel
let activeForm = null;

function clearActive(){
    document.querySelectorAll('.match-form').forEach(f => {
        f.classList.remove('bd-active');
        const b = f.querySelector('.btn-breakdown');
        if(b) b.classList.remove('active');
    });
}

function showBreakdown(form){
    activeForm = form;
    clearActive();
    form.classList.add('bd-active');
    form.querySelector('.btn-breakdown').classList.add('active');

    legendPanel.style.display = 'none';
    breakdownPanel.style.display = 'block';

    form.renderPanel();

    // on smaller screens the panel sits below the cards, so bring it into view
    if(window.innerWidth < 1200){
        breakdownPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function showLegend(){
    activeForm = null;
    clearActive();
    breakdownPanel.style.display = 'none';
    legendPanel.style.display = 'block';
}

document.querySelectorAll('.match-form').forEach(form => {

    const scores = JSON.parse(form.dataset.scores || '{}');
    const best   = form.dataset.best;

    // every item shows its own %:
    // - if an item on the OTHER side is selected -> score against that one
    // - otherwise -> its best score against any item on the other side
    function renderItemScores(l, f){
        form.querySelectorAll('.match-side').forEach(label => {
            const radio  = label.querySelector('input[type="radio"]');
            const isLost = (radio.name === 'lost_id');
            const id     = radio.value;
            const other  = isLost ? f : l;

            let pct = 0;
            let sub = 'Best match';

            if(other){
                const key = isLost ? (id + '-' + other.value) : (other.value + '-' + id);
                pct = scores[key] ? scores[key].total : 0;
                sub = 'vs ' + (isLost ? 'Found' : 'Lost') + ' #' + other.value;
            } else {
                Object.keys(scores).forEach(k => {
                    const parts = k.split('-');
                    if(parts[isLost ? 0 : 1] === id){
                        pct = Math.max(pct, scores[k].total);
                    }
                });
            }

            let badge = label.querySelector('.item-score');
            if(!badge){
                badge = document.createElement('span');
                label.appendChild(badge);
            }

            badge.className = 'item-score conf-' + scoreLevel(pct);
            badge.innerHTML = '<b>' + pct + '%</b><small>' + sub + '</small>';
        });
    }

    // fills the right panel with the breakdown of EVERY lost x found pair in this card.
    // top = selected pair (or best pair if none picked yet), the rest follow from highest to lowest score
    form.renderPanel = function(){
        const l = form.querySelector('input[name="lost_id"]:checked');
        const f = form.querySelector('input[name="found_id"]:checked');
        const selKey  = (l && f) ? (l.value + '-' + f.value) : null;
        const primary = selKey || best;

        const others = Object.keys(scores)
            .filter(k => k !== primary)
            .sort((a, b) => scores[b].total - scores[a].total);

        const ordered = [primary].concat(others).filter(k => scores[k]);

        bdCount.textContent = ordered.length + (ordered.length === 1 ? ' possible pair' : ' possible pairs') + ' in this match';

        bdList.innerHTML = ordered.map(key => {
            const s   = scores[key];
            const ids = key.split('-');
            const isPrimary = (key === primary);
            const tag = isPrimary ? (selKey ? 'Selected pair' : 'Best pair') : 'Other pair';

            const rows = s.parts.map(p => {
                const pct = p.max ? Math.round((p.got / p.max) * 100) : 0;
                return `
                    <div class="score-row">
                        <span>${p.label}</span>
                        <div class="bar"><div class="fill fill-${scoreLevel(pct)}" style="width:${pct}%"></div></div>
                        <span class="pts">${p.got}/${p.max}</span>
                    </div>`;
            }).join('');

            return `
                <div class="bd-block ${isPrimary ? 'primary' : ''}">
                    <div class="bd-head">
                        <div>
                            <div class="bd-pair">Lost #${ids[0]} \u2194 Found #${ids[1]}</div>
                            <div class="bd-which">${tag}</div>
                        </div>
                        <div class="bd-total conf-${scoreLevel(s.total)}">
                            <b>${s.total}%</b><small>overall match</small>
                        </div>
                    </div>
                    ${rows}
                </div>`;
        }).join('');
    };

    function renderScore(){
        const l = form.querySelector('input[name="lost_id"]:checked');
        const f = form.querySelector('input[name="found_id"]:checked');

        renderItemScores(l, f);

        // keep the right panel in sync if this card's breakdown is open
        if(activeForm === form) form.renderPanel();
    }

    // Recent / Older Match button -> open (or close) the breakdown in the right panel
    const btn = form.querySelector('.btn-breakdown');
    if(btn){
        btn.addEventListener('click', function(){
            if(activeForm === form){
                showLegend();
            } else {
                showBreakdown(form);
            }
        });
    }

    form.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function(){
            // only clear siblings within the SAME name group, inside THIS form
            form.querySelectorAll('input[name="' + this.name + '"]').forEach(r => {
                r.closest('.match-side').classList.remove('selected');
            });
            this.closest('.match-side').classList.add('selected');

            renderScore();
        });
    });

    renderScore();
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