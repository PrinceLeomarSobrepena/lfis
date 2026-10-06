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

<title>Claim Details</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

</head>
<body>

<div class="container mt-5">

    <h2 class="mb-4">Claimed Item Details</h2>

    <div class="card">
        <div class="card-body">

            <div class="text-center mb-4">
                <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" width="180">
            </div>

            <h4>Item Information</h4>

            <p><strong>Status:</strong> <?= htmlspecialchars($item['status']) ?></p>

            <p><strong>Category:</strong> <?= htmlspecialchars($item['category']) ?></p>

            <p><strong>Reported Location:</strong> <?= htmlspecialchars($item['reported_location']) ?></p>

            <?php if ($item['category'] === 'Cash'): ?>

                <p><strong>Amount:</strong> ₱<?= htmlspecialchars($details['amount']) ?></p>

            <?php elseif ($item['category'] === 'Gadget'): ?>

                <p><strong>Brand/Model:</strong> <?= htmlspecialchars($details['brand']) ?></p>

                <p><strong>Color:</strong> <?= htmlspecialchars($details['color']) ?></p>

                <p><strong>Features:</strong> <?= htmlspecialchars($details['features']) ?></p>

            <?php elseif ($item['category'] === 'Document'): ?>

                <p><strong>Document Type:</strong> <?= htmlspecialchars($details['document_type']) ?></p>

                <p><strong>Name:</strong> <?= htmlspecialchars($details['name']) ?></p>

            <?php elseif ($item['category'] === 'Other'): ?>

                <p><strong>Description:</strong> <?= htmlspecialchars($details['description']) ?></p>

            <?php endif; ?>

            <hr>

            <h4>Claimed By</h4>

            <p><strong>Student Name:</strong> <?= htmlspecialchars($item['claimed_by']) ?></p>

            <p><strong>Student ID:</strong> <?= htmlspecialchars($item['claimed_id']) ?></p>

            <p><strong>Email:</strong> <?= htmlspecialchars($item['claimed_email']) ?></p>

            <p><strong>Contact:</strong> <?= htmlspecialchars($item['claimed_contact']) ?></p>

            <p><strong>Department:</strong> <?= htmlspecialchars($item['claimed_department']) ?></p>

            <p><strong>Address:</strong> <?= htmlspecialchars($item['claimed_address']) ?></p>

            <p>
                <strong>Claimed Date:</strong>
                <?= date('M d, Y h:i A', strtotime($item['claimed_date'])) ?>
            </p>

            <a href="adminLostFoundList.php" class="btn btn-secondary mt-3">
                Back
            </a>

        </div>
    </div>

</div>

</body>
</html>