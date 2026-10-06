<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$result = $conn->query("SELECT * FROM lost_found ORDER BY created_at DESC "); /* LIMIT 3 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost & Found List</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<style>
body{
    background: #f5f5f5;
    font-family: Arial, sans-serif;
}

.lost-card{
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.10);
    padding: 12px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
    transition: 0.2s ease;
    height: 100%;
}

.lost-card:hover{
    transform: translateY(-3px);
}

.lost-image{
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 10px;
    flex-shrink: 0;
}

.lost-details{
    flex: 1;
    min-width: 0;
}

.lost-title{
    font-size: 17px;
    font-weight: bold;
    margin-bottom: 0px;
}

.lost-text{
    font-size: 14px;
    margin-bottom: 4px;
    word-wrap: break-word;
}

.top-row{
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0px;
    gap: 10px;
}
</style>

</head>

<body>

<div class="container mt-5">

<h2>Lost & Found List</h2>
<p class="fw-bold text-danger mb-1">Latest Post</p>

<div class="row g-3">

<?php while($row = $result->fetch_assoc()): ?>

<?php
$itemDetails = '-';

if ($row['category'] === 'Cash') {

    $itemDetails =
        '₱ ' . number_format($row['cash_amount'], 2);
}

elseif ($row['category'] === 'Gadget') {

    $itemDetails =
        'Type: ' . ($row['gadget_type'] ?? '-') . '<br>' .
        'Brand: ' . ($row['gadget_brand'] ?? '-') . '<br>' .
        'Color: ' . ($row['gadget_color'] ?? '-') . '<br>' .
        'Features: ' . ($row['gadget_features'] ?? '-');
}

elseif ($row['category'] === 'Document') {

    $itemDetails =
        'Type: ' . ($row['document_type'] ?? '-') . '<br>' .
        'Name: ' . ($row['document_name'] ?? '-');
}

elseif ($row['category'] === 'Other') {

    $itemDetails =
        'Item: ' . ($row['other_description'] ?? '-');
}
?>

<div class="col-12 col-md-6 col-lg-4">

    <div class="lost-card">

        <!-- IMAGE -->
        <img
            src="../uploads/<?= htmlspecialchars($row['image']) ?>"
            class="lost-image"
        >

        <!-- DETAILS -->
        <div class="lost-details">

            <!-- CATEGORY + STATUS BADGE (LEFT / RIGHT) -->
            <div class="d-flex justify-content-between align-items-center mb-0">

                <!-- LEFT: CATEGORY -->
                <!-- <div class="lost-title mb-0">
                    <?= htmlspecialchars($row['category']) ?>
                </div> -->
                <!-- LEFT: CATEGORY + DETAILS (1 LINE) -->
                <div class="lost-title mb-0">
                    <?= htmlspecialchars($row['category']) ?>

                    <?php if ($row['category'] === 'Other'): ?>
                        <span class="text-muted fw-normal">
                            : <?= htmlspecialchars($row['other_description'] ?? '-') ?>
                        </span>
                    <?php endif; ?>
                </div>


                <!-- RIGHT: CLAIM STATUS -->
                <div>
                    <?php if ($row['is_resolved']): ?>
                        <span class="badge bg-primary">Resolved</span>

                    <?php elseif ($row['is_claimed']): ?>
                        <span class="badge bg-success">Claimed</span>

                    <?php else: ?>
                        <span class="badge bg-light text-dark">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ITEM DETAILS (optional kung gusto mo i-activate ulit) -->
            <!--
            <div class="lost-text">
                <?= $itemDetails ?>
            </div>
            -->

            <!-- LOCATION -->
            <div class="lost-text">
                <strong>Location:</strong>
                <?= htmlspecialchars($row['reported_location']) ?>
            </div>

            <!-- DATE CREATED -->
            <div class="lost-text text-muted">
                <!-- <strong>Date Created:</strong> -->
                <?= date('F d, Y h:i A', strtotime($row['created_at'])) ?>
            </div>

        </div>

    </div>

</div>

<?php endwhile; ?>

</div>

</div>

</body>
</html>