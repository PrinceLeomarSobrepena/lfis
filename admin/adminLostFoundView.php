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

    $releasedBy =
        $item['admin_released_first'] . ' ' .
        $item['admin_released_last'];

}
elseif (!empty($item['staff_released_first'])) {

    $releasedBy =
        $item['staff_released_first'] . ' ' .
        $item['staff_released_last'];

}
else {

    $releasedBy = '-';

}


/*
|--------------------------------------------------------------------------
| PDF
|--------------------------------------------------------------------------
*/

if (isset($_GET['pdf'])) {

    $options = new Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);

    $html = '
    <h2 style="text-align:center;">
        Lost & Found Claim Details
    </h2>

    <hr>

    <h3>Item Information</h3>

    <p>
        <b>Category:</b>
        ' . htmlspecialchars($item['category']) . '
    </p>

    <p>
        <b>Status:</b>
        ' . htmlspecialchars($item['status']) . '
    </p>

    <p>
        <b>Location:</b>
        ' . htmlspecialchars($item['reported_location']) . '
    </p>
    ';


    if ($item['category'] === 'Cash') {

        $html .= '
        <p>
            <b>Amount:</b>
            ₱' . number_format($item['cash_amount'], 2) . '
        </p>
        ';

    }

    elseif ($item['category'] === 'Gadget') {

        $html .= '
        <p>
            <b>Type:</b>
            ' . htmlspecialchars($item['gadget_type'] ?? '-') . '
        </p>

        <p>
            <b>Brand:</b>
            ' . htmlspecialchars($item['gadget_brand'] ?? '-') . '
        </p>

        <p>
            <b>Color:</b>
            ' . htmlspecialchars($item['gadget_color'] ?? '-') . '
        </p>

        <p>
            <b>Features:</b>
            ' . htmlspecialchars($item['gadget_features'] ?? '-') . '
        </p>
        ';

    }

    elseif ($item['category'] === 'Document') {

        $html .= '
        <p>
            <b>Document Type:</b>
            ' . htmlspecialchars($item['document_type'] ?? '-') . '
        </p>

        <p>
            <b>Name:</b>
            ' . htmlspecialchars($item['document_name'] ?? '-') . '
        </p>
        ';

    }

    elseif ($item['category'] === 'Other') {

        $html .= '
        <p>
            <b>Description:</b>
            ' . htmlspecialchars($item['other_description'] ?? '-') . '
        </p>
        ';
    }


    $html .= '

    <hr>

    <h3>Claim Information</h3>

    <p>
        <b>Claimed By:</b>
        ' . htmlspecialchars($item['claimed_by'] ?? '-') . '
    </p>

    <p>
        <b>Student ID:</b>
        ' . htmlspecialchars($item['claimed_id'] ?? '-') . '
    </p>

    <p>
        <b>Email:</b>
        ' . htmlspecialchars($item['claimed_email'] ?? '-') . '
    </p>

    <p>
        <b>Contact:</b>
        ' . htmlspecialchars($item['claimed_contact'] ?? '-') . '
    </p>

    <p>
        <b>Department:</b>
        ' . htmlspecialchars($item['claimed_department'] ?? '-') . '
    </p>

    <p>
        <b>Address:</b>
        ' . htmlspecialchars($item['claimed_address'] ?? '-') . '
    </p>

    <p>
        <b>Claim Date:</b>
        ' . htmlspecialchars($item['claimed_date'] ?? '-') . '
    </p>

    <p>
        <b>Released By:</b>
        ' . htmlspecialchars($releasedBy) . '
    </p>
    ';


    $dompdf->loadHtml($html);

    $dompdf->setPaper('A4', 'portrait');

    $dompdf->render();

    $dompdf->stream(
        "Claimed_Item_" . $id . ".pdf",
        [
            "Attachment" => true
        ]
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| Helper: badge color for status
|--------------------------------------------------------------------------
*/

function statusBadgeClass($status)
{
    switch (strtolower($status)) {

        case 'claimed':
            return 'bg-success-subtle text-success border border-success-subtle';

        case 'pending':
            return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';

        case 'unclaimed':
            return 'bg-secondary-subtle text-secondary border border-secondary-subtle';

        default:
            return 'bg-light text-dark border';
    }
}


/*
|--------------------------------------------------------------------------
| Helper: icon per category
|--------------------------------------------------------------------------
*/

function categoryIcon($category)
{
    switch ($category) {

        case 'Cash':
            return 'bi-cash-coin';

        case 'Gadget':
            return 'bi-laptop';

        case 'Document':
            return 'bi-file-earmark-text';

        default:
            return 'bi-box-seam';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Claimed Item Details</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
      rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap"
      rel="stylesheet">


<style>

* {
    box-sizing: border-box;
}

body {
    font-family: 'Nunito', Arial, sans-serif;
    background: #f5f6fa;
    color: #0F1B2D;
}

.page-wrap {
    max-width: 1100px;
    margin: 0 auto;
    padding: 35px 15px 60px;
}


/* HEADER */

.view-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.eyebrow {
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: .8px;
    text-transform: uppercase;
    color: #198754;
    margin-bottom: 4px;
}

.view-title {
    font-weight: 800;
    font-size: 24px;
    margin: 0;
    color: #0F1B2D;
}

.view-subtitle {
    font-size: 13px;
    color: #7C8A85;
    font-weight: 600;
    margin-top: 2px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13.5px;
    font-weight: 700;
    color: #4B5A54;
    text-decoration: none;
    padding: 9px 16px;
    border-radius: 10px;
    border: 1px solid #dee2e6;
    background: #fff;
    transition: .2s ease;
}

.back-link:hover {
    background: #E8F7EF;
    color: #198754;
    border-color: #bfe3cd;
}


/* CARD */

.detail-card {
    background: #fff;
    border: 1px solid #E7ECE9;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(20, 60, 40, 0.05);
    overflow: hidden;
}

.detail-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 22px 26px;
    border-bottom: 1px solid #E7ECE9;
    background: linear-gradient(135deg, #f8fefb 0%, #ffffff 100%);
}

.category-icon-lg {
    width: 52px;
    height: 52px;
    min-width: 52px;
    border-radius: 14px;
    background: #E8F7EF;
    color: #198754;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 23px;
}

.status-badge {
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: .4px;
    text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 20px;
    display: inline-block;
}

.detail-card-body {
    padding: 26px;
}


/* IMAGE PANEL */

.item-image-frame {
    border: 1px solid #E7ECE9;
    border-radius: 14px;
    padding: 14px;
    background: #fafbfc;
    text-align: center;
    margin-bottom: 16px;
}

.item-image-frame img {
    max-width: 100%;
    max-height: 220px;
    border-radius: 10px;
    object-fit: cover;
}


/* SECTION LABEL */

.section-label {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .6px;
    text-transform: uppercase;
    color: #198754;
    margin-bottom: 14px;
    padding-bottom: 8px;
    border-bottom: 2px solid #E8F7EF;
}


/* INFO LIST */

.info-list {
    margin: 0;
}

.info-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px dashed #E7ECE9;
    font-size: 13.5px;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #7C8A85;
    font-weight: 700;
    white-space: nowrap;
}

.info-value {
    font-weight: 600;
    color: #0F1B2D;
    text-align: right;
}


/* ACTIONS */

.action-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    padding: 20px 26px;
    border-top: 1px solid #E7ECE9;
    background: #fafbfc;
}

.btn-app {
    border-radius: 10px;
    font-weight: 700;
    font-size: 13.5px;
    padding: 10px 18px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
}

.btn-app-outline {
    border: 1px solid #dee2e6;
    background: #fff;
    color: #4B5A54;
}

.btn-app-outline:hover {
    background: #f1f3f4;
    color: #0F1B2D;
}

.btn-app-primary {
    background: #198754;
    color: #fff;
}

.btn-app-primary:hover {
    background: #147a49;
    color: #fff;
}

.btn-app-danger {
    background: #fff;
    color: #dc3545;
    border: 1px solid #f5c2c7;
}

.btn-app-danger:hover {
    background: #FCEAED;
}


@media print {

    .back-link,
    .action-bar {
        display: none !important;
    }

    body {
        background: #fff;
    }
}

</style>

</head>


<body>

<div class="page-wrap">


    <!-- HEADER -->

    <div class="view-header">

        <div>

            <div class="eyebrow">
                Lost & Found Records
            </div>

            <h3 class="view-title">
                Claimed Item Details
            </h3>

            <div class="view-subtitle">
                Full record for item #<?= $id ?>
            </div>

        </div>


        <a href="adminLostFoundList.php"
           class="back-link">

            <i class="bi bi-arrow-left"></i>
            Back to List

        </a>

    </div>


    <!-- MAIN CARD -->

    <div class="detail-card">


        <div class="detail-card-header">

            <div class="category-icon-lg">

                <i class="bi <?= categoryIcon($item['category']) ?>"></i>

            </div>


            <div class="flex-grow-1">

                <div class="fw-bold"
                     style="font-size:17px;">

                    <?= htmlspecialchars($item['category']) ?> Item

                </div>


                <div class="text-muted"
                     style="font-size:12.5px;">

                    <?= htmlspecialchars($item['reported_location']) ?>

                </div>

            </div>


            <span class="status-badge <?= statusBadgeClass($item['status']) ?>">

                <?= htmlspecialchars($item['status']) ?>

            </span>

        </div>


        <div class="detail-card-body">

            <div class="row g-4">


                <!-- LEFT: IMAGE + BASIC INFO -->

                <div class="col-lg-4">


                    <div class="item-image-frame">

                        <img src="../uploads/<?= htmlspecialchars($item['image']) ?>"
                             alt="Item image">

                    </div>


                    <div class="section-label">
                        Item Information
                    </div>


                    <div class="info-list">


                        <div class="info-row">

                            <span class="info-label">
                                Category
                            </span>

                            <span class="info-value">
                                <?= htmlspecialchars($item['category']) ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Status
                            </span>

                            <span class="info-value">
                                <?= htmlspecialchars($item['status']) ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Location
                            </span>

                            <span class="info-value">
                                <?= htmlspecialchars($item['reported_location']) ?>
                            </span>

                        </div>


                    </div>

                </div>


                <!-- RIGHT: DETAILS -->

                <div class="col-lg-8">


                    <div class="section-label">
                        Item Details
                    </div>


                    <div class="info-list mb-4">


                        <?php if ($item['category'] === 'Cash'): ?>

                            <div class="info-row">

                                <span class="info-label">
                                    Amount
                                </span>

                                <span class="info-value">
                                    ₱<?= number_format($item['cash_amount'], 2) ?>
                                </span>

                            </div>


                        <?php elseif ($item['category'] === 'Gadget'): ?>

                            <div class="info-row">

                                <span class="info-label">
                                    Type
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($item['gadget_type'] ?? '-') ?>
                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Brand / Model
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($item['gadget_brand'] ?? '-') ?>
                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Color
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($item['gadget_color'] ?? '-') ?>
                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Features
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($item['gadget_features'] ?? '-') ?>
                                </span>

                            </div>


                        <?php elseif ($item['category'] === 'Document'): ?>

                            <div class="info-row">

                                <span class="info-label">
                                    Document Type
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($item['document_type'] ?? '-') ?>
                                </span>

                            </div>


                            <div class="info-row">

                                <span class="info-label">
                                    Name
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($item['document_name'] ?? '-') ?>
                                </span>

                            </div>


                        <?php elseif ($item['category'] === 'Other'): ?>

                            <div class="info-row">

                                <span class="info-label">
                                    Description
                                </span>

                                <span class="info-value">
                                    <?= htmlspecialchars($item['other_description'] ?? '-') ?>
                                </span>

                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="section-label">
                        Claim Information
                    </div>


                    <div class="info-list">


                        <div class="info-row">

                            <span class="info-label">
                                Claimed By
                            </span>

                            <span class="info-value">
                                <?= htmlspecialchars($item['claimed_by'] ?? '-') ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Student ID
                            </span>

                            <span class="info-value">
                                <?= htmlspecialchars($item['claimed_id'] ?? '-') ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Email
                            </span>

                            <span class="info-value">
                                <?= htmlspecialchars($item['claimed_email'] ?? '-') ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Contact Number
                            </span>

                            <span class="info-value">
                                <?= htmlspecialchars($item['claimed_contact'] ?? '-') ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Department
                            </span>

                            <span class="info-value">
                                <?= htmlspecialchars($item['claimed_department'] ?? '-') ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Address
                            </span>

                            <span class="info-value">
                                <?= htmlspecialchars($item['claimed_address'] ?? '-') ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span class="info-label">
                                Claim Date
                            </span>

                            <span class="info-value">

                                <?= !empty($item['claimed_date'])
                                    ? date(
                                        'M d, Y h:i A',
                                        strtotime($item['claimed_date'])
                                    )
                                    : '-' ?>

                            </span>

                        </div>


                        <!-- RELEASED BY -->

                        <div class="info-row">

                            <span class="info-label">
                                Released By
                            </span>

                            <span class="info-value">

                                <?= htmlspecialchars($releasedBy) ?>

                            </span>

                        </div>


                    </div>

                </div>

            </div>

        </div>


        <!-- ACTIONS -->

        <div class="action-bar">

            <a href="adminLostFoundList.php"
               class="btn-app btn-app-outline">

                <i class="bi bi-arrow-left"></i>
                Back to List

            </a>


            <button onclick="window.print()"
                    class="btn-app btn-app-outline">

                <i class="bi bi-printer"></i>
                Print

            </button>


            <a href="adminLostFoundView.php?id=<?= $id ?>&pdf=1"
               class="btn-app btn-app-primary">

                <i class="bi bi-file-earmark-pdf"></i>
                Download PDF

            </a>

        </div>


    </div>

</div>

</body>
</html>
