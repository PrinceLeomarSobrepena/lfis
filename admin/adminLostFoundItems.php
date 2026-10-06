<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$sql = "
    SELECT
        id,
        reported_location,
        category,
        status,
        image,
        cash_amount,
        gadget_type,
        gadget_brand,
        gadget_color,
        gadget_features,
        document_type,
        document_name,
        other_description,
        created_at,
        is_claimed,
        is_resolved
    FROM lost_found
    WHERE status = 'Found'
      AND is_claimed = 0
      AND is_resolved = 0
    ORDER BY created_at DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Found Items | Lost & Found</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    <style>

        /* Prevent text from going down */
        table {
            white-space: nowrap;
        }

        /* Responsive horizontal scrolling */
        .table-responsive {
            overflow-x: auto;
        }

        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

    </style>
</head>

<body>

<div class="container my-5">
    <div class="mb-3">
        <h2>Found Items</h2>

        <p class="text-muted mb-2"> List of all unclaimed and unresolved items reported as found. </p>
    </div>


    <!-- TABLE -->
    <div class="table-responsive">
        <table class="table table-striped align-middle" style="border: 1px solid #ced4da;">
            <thead class="table-dark text-center">
                <tr>
                    <th style="text-align: center !important; vertical-align: middle;"> Image </th>
                    <th style="text-align: center !important; vertical-align: middle;"> Category </th>
                    <th style="text-align: center !important; vertical-align: middle;"> Details </th>
                    <th style="text-align: center !important; vertical-align: middle;"> Found Location </th>
                    <th style="text-align: center !important; vertical-align: middle;"> Date Reported </th>
                    <th style="text-align: center !important; vertical-align: middle;"> Status </th>
                    <th style="text-align: center !important; vertical-align: middle;"> Action </th>

                </tr>
            </thead>

            <tbody class="text-center">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($item = $result->fetch_assoc()): ?>
                        <?php
                        $image = !empty($item['image']) ? "../uploads/" . $item['image'] : "../uploads/default.jpg";
                        ?>

                        <tr>
                            <!-- IMAGE -->
                            <td>
                                <img src="<?= htmlspecialchars($image) ?>" class="item-image" alt="Found Item" onerror="this.src='../uploads/default.jpg';">
                            </td>


                            <!-- CATEGORY -->
                            <td>
                                <?php if ($item['category'] === 'Cash'): ?>
                                    <span class="badge bg-primary"> Cash </span>

                                <?php elseif ($item['category'] === 'Gadget'): ?>
                                    <span class="badge bg-primary"> Gadget </span>

                                <?php elseif ($item['category'] === 'Document'): ?>
                                    <span class="badge bg-primary"> Document </span>

                                <?php else: ?>
                                    <span class="badge bg-primary"> Other </span>

                                <?php endif; ?>
                            </td>


                            <!-- DETAILS -->
                            <td style="text-align: left;">
                                <?php if ($item['category'] === 'Cash'): ?>
                                    <strong>
                                        ₱<?= number_format((float)$item['cash_amount'], 2) ?>
                                    </strong>

                                    <br>

                                    <small>
                                        Amount:
                                        ₱<?= number_format((float)$item['cash_amount'], 2) ?>
                                    </small>


                                <?php elseif ($item['category'] === 'Gadget'): ?>

                                    <strong>
                                        <?= htmlspecialchars($item['gadget_type'] ?? 'Gadget') ?>
                                    </strong>
                                    <br>

                                    <small>
                                        Brand / Model: <?= htmlspecialchars( $item['gadget_brand'] ?? '-') ?>
                                    </small>
                                    <br>

                                    <small>
                                        Color: <?= htmlspecialchars($item['gadget_color'] ?? '-') ?>
                                    </small>

                                    <?php if (!empty($item['gadget_features'])): ?>
                                        <br>

                                        <small>
                                            Features: <?= htmlspecialchars($item['gadget_features']) ?>
                                        </small>

                                    <?php endif; ?>

                                <?php elseif ($item['category'] === 'Document'): ?>
                                    <strong> Document </strong>
                                    <br>

                                    <small>
                                        Type: <?= htmlspecialchars( $item['document_type'] ?? '-') ?>
                                    </small>
                                    <br>

                                    <small>
                                        Name: <?= htmlspecialchars($item['document_name'] ?? '-') ?>
                                    </small>


                                <?php elseif ($item['category'] === 'Other'): ?>
                                    <strong> Other Item </strong>
                                    <br>

                                    <small>
                                        Description: <?= htmlspecialchars($item['other_description'] ?? '-') ?>
                                    </small>

                                <?php endif; ?>
                            </td>

                            <!-- LOCATION -->
                            <td>
                                <?= htmlspecialchars($item['reported_location']) ?>
                            </td>

                            <!-- DATE -->
                            <td>
                                <?= date('F d, Y h:i A', strtotime($item['created_at'])) ?>
                            </td>

                            <!-- STATUS -->
                            <td>
                                <?php if (!empty($item['is_claimed'])): ?>
                                    <span class="badge bg-warning text-dark rounded-5"> Claimed </span>

                                <?php elseif (!empty($item['is_resolved'])): ?>
                                    <span class="badge bg-secondary rounded-5"> Resolved </span>

                                <?php else: ?>
                                    <span class="badge bg-success rounded-5"> Pending </span>

                                <?php endif; ?>
                            </td>

                            <!-- ACTION -->
                            <td>
                                <form action="adminLostFoundItemsDelete.php" method="POST" onsubmit="return confirm('Are you sure you want to dispose this found item?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="py-5">
                            <div style="font-size: 50px;"> 📦 </div>
                            <h4 class="mt-3"> No Found Items </h4>
                            <p class="text-muted"> There are currently no unclaimed or unresolved found items. </p>
                            <a href="adminLostFoundCreate.php" class="btn btn-primary"> Create Found Item </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap JS -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>