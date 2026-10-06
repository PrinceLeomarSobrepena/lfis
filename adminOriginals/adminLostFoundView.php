// adminLostFoundView.php
<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM lost_found WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("Item not found");
}

$details = json_decode($item['extra_details'], true);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Claimed Item Details</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<style>
    body{
        background:#f4f6f9;
    }

    .details-card{
        border:none;
        border-radius:18px;
        overflow:hidden;
        box-shadow:0 8px 25px rgba(0,0,0,0.08);
    }

    .card-header-custom{
        background:linear-gradient(135deg,#0d6efd,#0b5ed7);
        color:#fff;
        padding:25px;
    }

    .item-image{
        width:100%;
        height:320px;
        object-fit:cover;
        border-radius:14px;
        border:1px solid #dee2e6;
    }

    .section-title{
        font-size:18px;
        font-weight:700;
        color:#0d6efd;
        margin-bottom:18px;
        border-left:4px solid #0d6efd;
        padding-left:10px;
    }

    .info-box{
        background:#f8f9fa;
        border-radius:12px;
        padding:14px 18px;
        margin-bottom:12px;
        border:1px solid #e9ecef;
    }

    .info-label{
        font-size:13px;
        color:#6c757d;
        margin-bottom:3px;
    }

    .info-value{
        font-size:16px;
        font-weight:600;
        color:#212529;
    }

    .status-badge{
        font-size:14px;
        padding:8px 14px;
        border-radius:30px;
    }

    .divider{
        border-top:1px dashed #ced4da;
        margin:28px 0;
    }

    .btn-back{
        border-radius:10px;
        padding:10px 22px;
        font-weight:600;
    }
</style>
</head>

<body>

<div class="container py-5">

    <div class="card details-card">

        <div class="card-header-custom">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="mb-1">Claimed Item Details</h2>
                    <p class="mb-0 opacity-75">
                        View complete information about the claimed item
                    </p>
                </div>

                <span class="badge bg-light text-dark status-badge">
                    <?= htmlspecialchars($item['status']) ?>
                </span>
            </div>
        </div>

        <div class="card-body p-4">

            <div class="row g-4">

                <!-- LEFT SIDE -->
                <div class="col-lg-4">

                    <img src="../uploads/<?= htmlspecialchars($item['image']) ?>"
                         class="item-image">

                    <div class="mt-4">

                        <div class="section-title">
                            General Information
                        </div>

                        <div class="info-box">
                            <div class="info-label">Category</div>
                            <div class="info-value">
                                <?= htmlspecialchars($item['category']) ?>
                            </div>
                        </div>

                        <div class="info-box">
                            <div class="info-label">Reported Location</div>
                            <div class="info-value">
                                <?= htmlspecialchars($item['reported_location']) ?>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- RIGHT SIDE -->
                <div class="col-lg-8">

                    <div class="section-title">
                        Item Details
                    </div>

                    <?php if ($item['category'] === 'Cash'): ?>

                        <div class="info-box">
                            <div class="info-label">Amount</div>
                            <div class="info-value">
                                ₱<?= number_format($details['amount'], 2) ?>
                            </div>
                        </div>

                    <?php elseif ($item['category'] === 'Gadget'): ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-label">Brand / Model</div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($details['brand']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-label">Color</div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($details['color']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-box">
                            <div class="info-label">Features</div>
                            <div class="info-value">
                                <?= htmlspecialchars($details['features']) ?>
                            </div>
                        </div>

                    <?php elseif ($item['category'] === 'Document'): ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-label">Document Type</div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($details['document_type']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-box">
                                    <div class="info-label">Name</div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($details['name']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($item['category'] === 'Other'): ?>

                        <div class="info-box">
                            <div class="info-label">Description</div>
                            <div class="info-value">
                                <?= htmlspecialchars($details['description']) ?>
                            </div>
                        </div>

                    <?php endif; ?>

                    <div class="divider"></div>

                    <div class="section-title">
                        Claimed By
                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">Student Name</div>
                                <div class="info-value">
                                    <?= htmlspecialchars($item['claimed_by'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">Student ID</div>
                                <div class="info-value">
                                    <?= htmlspecialchars($item['claimed_id'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">Email</div>
                                <div class="info-value">
                                    <?= htmlspecialchars($item['claimed_email'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">Contact</div>
                                <div class="info-value">
                                    <?= htmlspecialchars($item['claimed_contact'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">Department</div>
                                <div class="info-value">
                                    <?= htmlspecialchars($item['claimed_department'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">Claimed Date</div>
                                <div class="info-value">
                                    <?= $item['claimed_date']
                                        ? date('M d, Y h:i A', strtotime($item['claimed_date']))
                                        : '-' ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="info-box">
                                <div class="info-label">Address</div>
                                <div class="info-value">
                                    <?= htmlspecialchars($item['claimed_address'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4">
                        <a href="adminLostFoundList.php"
                           class="btn btn-secondary btn-back">
                            ← Back to List
                        </a>
                    </div>

                </div>

            </div>

        </div>
    </div>

</div>

</body>
</html>