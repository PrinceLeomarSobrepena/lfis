<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT
        lost_found.*,

        ar.firstName AS admin_released_first,
        ar.lastName AS admin_released_last,

        sr.firstName AS staff_released_first,
        sr.lastName AS staff_released_last

    FROM lost_found

    LEFT JOIN admin ar
        ON lost_found.released_by = ar.id

    LEFT JOIN staff sr
        ON lost_found.released_by = sr.id

    WHERE lost_found.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("Item not found");
}


/*
|--------------------------------------------------------------------------
| RELEASED BY
|--------------------------------------------------------------------------
*/

if (!empty($item['admin_released_first'])) {
    $releasedBy = $item['admin_released_first'] . ' ' . $item['admin_released_last'] . ' (Admin)';
}
elseif (!empty($item['staff_released_first'])) {
    $releasedBy = $item['staff_released_first'] . ' ' . $item['staff_released_last'] . ' (Staff)';
}
else {
    $releasedBy = '-';
}


if ((int)$item['is_claimed'] !== 1) {
    die("Only claimed items can be viewed.");
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e($value, $fallback = '-') {
    $value = trim((string)($value ?? ''));
    return htmlspecialchars($value !== '' ? $value : $fallback);
}

function fmtDate($value) {
    return !empty($value) ? date('M d, Y · h:i A', strtotime($value)) : '-';
}

$catIcons = [
    'Cash'     => 'bi-cash-coin',
    'Gadget'   => 'bi-phone',
    'Document' => 'bi-file-earmark-text',
    'Other'    => 'bi-box-seam'
];
$catIcon  = $catIcons[$item['category']] ?? 'bi-box-seam';
$hasImage = !empty($item['image']) && $item['image'] !== 'default.jpg';
$initial  = strtoupper(mb_substr(trim((string)$item['claimed_by']), 0, 1)) ?: '?';


/*
|--------------------------------------------------------------------------
| PDF
|--------------------------------------------------------------------------
*/

if (isset($_GET['pdf'])) {

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans'); // supports the peso sign

    $dompdf = new Dompdf($options);

    // embed the item photo as base64 so Dompdf can always load it
    $imgTag = '';
    if ($hasImage) {
        $imgPath = realpath(__DIR__ . '/../uploads/' . basename($item['image']));
        if ($imgPath && is_file($imgPath)) {
            $mime = mime_content_type($imgPath);
            $data = base64_encode(file_get_contents($imgPath));
            $imgTag = '<img src="data:' . $mime . ';base64,' . $data . '" style="width:110px;height:110px;border-radius:10px;">';
        }
    }

    // item specific rows
    $itemRows = '';
    $pdfRow = function ($label, $value) {
        return '<tr><td class="lbl">' . $label . '</td><td class="val">' . e($value) . '</td></tr>';
    };

    if ($item['category'] === 'Cash') {
        $itemRows .= $pdfRow('Amount', '₱' . number_format($item['cash_amount'], 2));
    }
    elseif ($item['category'] === 'Gadget') {
        $itemRows .= $pdfRow('Type', $item['gadget_type']);
        $itemRows .= $pdfRow('Brand', $item['gadget_brand']);
        $itemRows .= $pdfRow('Color', $item['gadget_color']);
        $itemRows .= $pdfRow('Features', $item['gadget_description']);
    }
    elseif ($item['category'] === 'Document') {
        $itemRows .= $pdfRow('Document Type', $item['document_type']);
        $itemRows .= $pdfRow('Name', $item['document_name']);
    }
    elseif ($item['category'] === 'Other') {
        $itemRows .= $pdfRow('Title', $item['other_title']);
        $itemRows .= $pdfRow('Description', $item['other_description']);
    }

    $html = '
    <html>
    <head>
    <style>
        body{ font-family:"DejaVu Sans", sans-serif; font-size:12px; color:#0F1B2D; }
        .head{ text-align:center; border-bottom:3px solid #198754; padding-bottom:10px; margin-bottom:18px; }
        .head .school{ font-size:11px; color:#4B5A54; letter-spacing:.5px; }
        .head h2{ margin:4px 0 0; font-size:20px; color:#198754; }
        h3{ font-size:13px; color:#147a49; background:#E8F7EF; padding:7px 10px; margin:20px 0 6px; }
        table{ width:100%; border-collapse:collapse; }
        td{ padding:7px 10px; border-bottom:1px solid #E7ECE9; vertical-align:top; }
        td.lbl{ width:32%; color:#7A8A83; font-weight:bold; }
        td.val{ font-weight:bold; }
        .photo{ text-align:center; margin:10px 0 4px; }
        .foot{ margin-top:30px; text-align:center; font-size:10px; color:#7A8A83; }
        .sign{ margin-top:50px; }
        .sign td{ border:none; text-align:center; width:50%; font-size:11px; color:#4B5A54; }
        .sign .line{ border-top:1px solid #4B5A54; padding-top:5px; margin:0 25px; }
    </style>
    </head>
    <body>

        <div class="head">
            <div class="school">DR. GLORIA D. LACSON FOUNDATION COLLEGES, INC.</div>
            <h2>Lost &amp; Found Claim Details</h2>
        </div>

        <div class="photo">' . $imgTag . '</div>

        <h3>Item Information</h3>
        <table>
            <tr><td class="lbl">Category</td><td class="val">' . e($item['category']) . '</td></tr>
            <tr><td class="lbl">Status</td><td class="val">' . e($item['status']) . '</td></tr>
            <tr><td class="lbl">Reported Location</td><td class="val">' . e($item['reported_location']) . '</td></tr>
            <tr><td class="lbl">Date Reported</td><td class="val">' . e(fmtDate($item['created_at'])) . '</td></tr>
            ' . $itemRows . '
        </table>

        <h3>Claim Information</h3>
        <table>
            <tr><td class="lbl">Claimed By</td><td class="val">' . e($item['claimed_by']) . '</td></tr>
            <tr><td class="lbl">ID</td><td class="val">' . e($item['claimed_id']) . '</td></tr>
            <tr><td class="lbl">Email</td><td class="val">' . e($item['claimed_email']) . '</td></tr>
            <tr><td class="lbl">Contact</td><td class="val">' . e($item['claimed_contact']) . '</td></tr>
            <tr><td class="lbl">Department</td><td class="val">' . e($item['claimed_department']) . '</td></tr>
            <tr><td class="lbl">Address</td><td class="val">' . e($item['claimed_address']) . '</td></tr>
        </table>

        <h3>Release Information</h3>
        <table>
            <tr><td class="lbl">Claim Date</td><td class="val">' . e(fmtDate($item['claimed_date'])) . '</td></tr>
            <tr><td class="lbl">Released By</td><td class="val">' . e($releasedBy) . '</td></tr>
        </table>

        <table class="sign">
            <tr>
                <td><div class="line">Claimant Signature</div></td>
                <td><div class="line">Released By</div></td>
            </tr>
        </table>

        <div class="foot">Generated on ' . date('M d, Y h:i A') . '</div>

    </body>
    </html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dompdf->stream(
        "Claimed_Item_" . $id . ".pdf",
        ["Attachment" => true]
    );

    exit();
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
/* ====================== PHOTO + ITEM DETAILS ===================== */
.photo-upload-row{
    display:flex;
    align-items:flex-start;
    gap:18px;
}

.photo-preview{
    display:flex;
    align-items:center;
    justify-content:center;
    width:84px;
    height:84px;
    padding:0;
    font-size:28px;
    color:#198754;
    border-radius:16px;
    background:#F3FBF7;
    border:none;
    flex-shrink:0;
    overflow:hidden;
    position:relative;
}

.photo-preview img{
    display:block;
    width:100%;
    height:100%;
    object-fit:cover;
    max-width:none;
}

.item-details-right{
    flex:1;
    min-width:0;
}

.category-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-size:11.5px;
    font-weight:800;
    letter-spacing:.4px;
    text-transform:uppercase;
    color:#147a49;
    background:#E8F7EF;
    border-radius:999px;
    padding:4px 12px;
    margin-bottom:10px;
}

.detail-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:10px 18px;
}

.detail-item{
    display:flex;
    flex-direction:column;
    min-width:0;
    line-height:1.3;
}

.detail-item.full{
    grid-column:1 / -1;
}

.detail-item small{
    font-size:10.5px;
    font-weight:700;
    letter-spacing:.4px;
    text-transform:uppercase;
    color:#7A8A83;
}

.detail-item span{
    font-size:13px;
    font-weight:700;
    color: #0F1B2D;
    overflow-wrap:anywhere;
}

.detail-item .detail-amount{
    font-size:13px;
    font-weight:700;
    color: #0F1B2D;
}

/* ====================== Reported ===================== */
.reported-details{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    width:100%;
    margin-top:-5px;
}

.report-chip{
    display:flex;
    align-items:center;
    gap:10px;
    flex:1 1 220px;
    background:#F3FBF7;
    border:1px solid #D5EEDF;
    border-radius:12px;
    padding:10px 14px;
}

.report-chip-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:34px;
    height:34px;
    flex-shrink:0;
    border-radius:10px;
    background:#198754;
    color:#fff;
    font-size:15px;
}

.report-chip-text{
    display:flex;
    flex-direction:column;
    min-width:0;
    line-height:1.3;
}

.report-chip-text small{
    font-size:10.5px;
    font-weight:700;
    letter-spacing:.4px;
    text-transform:uppercase;
    color:#7A8A83;
}

.report-chip-text span{
    font-size:13px;
    font-weight:700;
    color:#0F1B2D;
    overflow-wrap:anywhere;
}

/* ====================== FORM CARD ===================== */
.form-card{
    background:#fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:26px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
}

.form-section-title{
    display:flex;
    align-items:center;
    font-weight:800;
    font-size:14px;
    color: #0F1B2D;
    margin-bottom:16px;
    gap:8px;
}

.form-section-title i{
    color: #198754;
}

/* ====================== READ-ONLY FIELDS ===================== */
.info-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:12px;
    width:100%;
}

.info-field{
    /* background:#F9FBFA; */
    border:1px solid #E7ECE9;
    border-radius:10px;
    padding:10px 14px;
    min-width:0;
}

.info-field small{
    display:block;
    font-size:10.5px;
    font-weight:700;
    letter-spacing:.4px;
    text-transform:uppercase;
    color:#7A8A83;
    margin-bottom:2px;
}

.info-field span{
    display:block;
    font-size:13.5px;
    font-weight:700;
    color:#0F1B2D;
    overflow-wrap:anywhere;
}

/* ====================== DIVIDER ===================== */
.divider-line{
    border:none;
    border-top:1px solid #EEF1F0;
    margin:6px 0;
    width:100%;
    opacity:1;
}

/* ====================== SIDE SUMMARY ===================== */
.summary-card{
    text-align:center;
    background:#fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:22px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
}



.summary-card h6{
    font-weight:800;
    font-size:15px;
    color:#0F1B2D;
    margin-bottom:6px;
    overflow-wrap:anywhere;
}


/* ====================== ACTION BUTTONS ===================== */
.summary-head{
    margin-bottom:18px;
}

.summary-head-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:46px;
    height:46px;
    margin:0 auto 10px;
    border-radius:14px;
    background:#E8F7EF;
    color:#198754;
    font-size:22px;
}

.summary-head-title{
    font-weight:800;
    font-size:15px;
    color:#0F1B2D;
    margin-bottom:4px;
}

.summary-head-text{
    font-size:12px;
    font-weight:600;
    line-height:1.5;
    color:#7A8A83;
    margin:0;
}

.action-stack{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.btn-action{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    width:100%;
    font-weight:700;
    font-size:13.5px;
    border-radius:10px;
    padding:11px 22px;
    text-decoration:none;
    cursor:pointer;
    border:1px solid transparent;
}

.btn-action.primary{
    color:#fff;
    background:linear-gradient(135deg, #198754, #147a49);
    box-shadow:0 6px 14px rgba(25,135,84,0.25);
}

.btn-action.primary:hover{ color:#fff; filter:brightness(1.05); }

.btn-action.outline{
    color:#147a49;
    background:#fff;
    border-color:#198754;
}

.btn-action.outline:hover{ background:#E8F7EF; color:#147a49; }

.btn-action.ghost{
    color:#4B5A54;
    background:#fff;
    border-color:#E7ECE9;
}

.btn-action.ghost:hover{ background:#f5f6fa; color:#4B5A54; }

/* ====================== PRINT HEADER (print only) ===================== */
.print-only{ display:none; }

.print-header{
    text-align:center;
    border-bottom:3px solid #198754;
    padding-bottom:10px;
    margin-bottom:18px;
}

.print-header small{
    display:block;
    font-size:11px;
    font-weight:700;
    letter-spacing:.5px;
    color:#4B5A54;
}

.print-header h3{
    margin:4px 0 0;
    font-size:20px;
    font-weight:800;
    color:#198754;
}

@media(max-width:992px){
    .summary-card{
        margin-top:16px;
    }
}

@media(max-width:576px){
    .detail-grid,
    .info-grid{
        grid-template-columns:repeat(1, minmax(0, 1fr));
    }

    .detail-grid{
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }
}

/* ====================== PRINT ===================== */
@media print{
    @page{ margin:14mm; }

    body{
        background:#fff !important;
        -webkit-print-color-adjust:exact;
        print-color-adjust:exact;
    }

    .sidebar,
    .navbar-custom,
    #overlay,
    .no-print{
        display:none !important;
    }

    .print-only{ display:block; }

    .main-content{
        margin:0 !important;
        padding:0 !important;
        width:100% !important;
        max-width:100% !important;
    }

    .col-lg-8{ width:100% !important; flex:0 0 100% !important; max-width:100% !important; }

    .form-card{
        border:none;
        box-shadow:none;
        padding:0;
    }

    .info-field,
    .report-chip{
        break-inside:avoid;
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
        <a href="adminLostFoundView.php?id=<?= $item['id'] ?>" class="active" style="background: #30b175;margin-left: 20px;"> 
           <i class="bi bi-arrow-return-right"></i>
            <span><i class="bi bi-eye"></i>&nbsp;&nbsp;&nbsp;View</span> 
        </a>

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
            <img src="../uploads/SCHOOL.jpg" class="profile-img">  <!--  d-lg-none -->

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
        <div class="mt-4 mb-3 no-print">
            <div class="page-heading">View Released Item</div>
            <div class="page-subheading">Full record of the item and who claimed it.</div>
        </div>

        <div class="row g-3">
            <!-- DETAILS -->
            <div class="col-lg-8">
                <div class="form-card">

                    <!-- shown only when printing -->
                    <div class="print-only print-header">
                        <small>DR. GLORIA D. LACSON FOUNDATION COLLEGES, INC.</small>
                        <h3>Lost &amp; Found Claim Details</h3>
                    </div>

                    <div class="row g-3">

                        <div class="form-section-title"><i class="bi bi-box-seam"></i> Item Details</div>

                        <!-- REPORTED LOCATION + DATE -->
                        <div class="reported-details">
                            <div class="report-chip">
                                <span class="report-chip-icon"><i class="bi bi-geo-alt-fill"></i></span>
                                <div class="report-chip-text">
                                    <small>Reported Location</small>
                                    <span><?= e($item['reported_location']) ?></span>
                                </div>
                            </div>

                            <div class="report-chip">
                                <span class="report-chip-icon"><i class="bi bi-calendar-event-fill"></i></span>
                                <div class="report-chip-text">
                                    <small>Date Reported</small>
                                    <span><?= fmtDate($item['created_at']) ?></span>
                                </div>
                            </div>
                        </div>

                        <!--  IMAGE AND DETAILS -->
                        <div class="photo-upload-row">

                            <div class="photo-preview">
                                <?php if ($hasImage): ?>
                                    <img alt="Item photo" src="../uploads/<?= htmlspecialchars($item['image']) ?>">
                                <?php else: ?>
                                    <i class="bi bi-image"></i>
                                <?php endif; ?>
                            </div>

                            <div class="item-details-right">

                                <span class="category-badge">
                                    <!-- <i class="bi <?= $catIcon ?>"></i> -->
                                    <?= e($item['category']) ?>
                                </span>

                                <div class="detail-grid">

                                    <?php if ($item['category'] === 'Cash'): ?>
                                        <div class="detail-item full">
                                            <small>Amount</small>
                                            <span class="detail-amount">₱<?= number_format($item['cash_amount'], 2) ?></span>
                                        </div>

                                    <?php elseif ($item['category'] === 'Gadget'): ?>
                                        <div class="detail-item">
                                            <small>Type</small>
                                            <span><?= e($item['gadget_type']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <small>Brand</small>
                                            <span><?= e($item['gadget_brand']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <small>Color</small>
                                            <span><?= e($item['gadget_color']) ?></span>
                                        </div>
                                        <div class="detail-item full">
                                            <small>Features</small>
                                            <span><?= e($item['gadget_description']) ?></span>
                                        </div>

                                    <?php elseif ($item['category'] === 'Document'): ?>
                                        <div class="detail-item">
                                            <small>Document Type</small>
                                            <span><?= e($item['document_type']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <small>Name</small>
                                            <span><?= e($item['document_name']) ?></span>
                                        </div>

                                    <?php elseif ($item['category'] === 'Other'): ?>
                                        <div class="detail-item">
                                            <small>Title</small>
                                            <span><?= e($item['other_title']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <small>Description</small>
                                            <span><?= e($item['other_description']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>

                        </div>

                        <hr class="divider-line">

                        <!-- CLAIM INFORMATION -->
                        <div class="form-section-title"><i class="bi bi-person-check"></i> Claim Information</div>

                        <div class="info-grid">
                            <div class="info-field">
                                <small>Claimed By</small>
                                <span><?= e($item['claimed_by']) ?></span>
                            </div>
                            <div class="info-field">
                                <small>ID</small>
                                <span><?= e($item['claimed_id']) ?></span>
                            </div>
                            <div class="info-field">
                                <small>Email</small>
                                <span><?= e($item['claimed_email']) ?></span>
                            </div>
                            <div class="info-field">
                                <small>Contact</small>
                                <span><?= e($item['claimed_contact']) ?></span>
                            </div>
                            <div class="info-field">
                                <small>Department</small>
                                <span><?= e($item['claimed_department']) ?></span>
                            </div>
                            <div class="info-field">
                                <small>Address</small>
                                <span><?= e($item['claimed_address']) ?></span>
                            </div>
                        </div>

                        <hr class="divider-line">

                        <!-- RELEASE INFORMATION -->
                        <div class="form-section-title"><i class="bi bi-hand-index-thumb"></i> Release Information</div>

                        <div class="info-grid">
                            <div class="info-field">
                                <small>Claim Date</small>
                                <span><?= fmtDate($item['claimed_date']) ?></span>
                            </div>
                            <div class="info-field">
                                <small>Released By</small>
                                <span><?= e($releasedBy) ?></span>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <!-- SUMMARY -->
            <div class="col-lg-4 no-print">
                <div class="summary-card">

                    <div class="summary-head">
                        <div class="summary-head-icon"><i class="bi bi-file-earmark-check"></i></div>
                        <div class="summary-head-title">Claim Record</div>
                        <p class="summary-head-text">
                            This item has already been claimed and released. Print or download a copy for your records.
                        </p>
                    </div>

                    <div class="action-stack">
                        <button type="button" class="btn-action primary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>

                        <a href="adminLostFoundView.php?id=<?= $item['id'] ?>&pdf=1" class="btn-action outline">
                            <i class="bi bi-file-earmark-pdf"></i> Download PDF
                        </a>

                        <a href="adminLostFoundList.php" class="btn-action ghost">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>



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