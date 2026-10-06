<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';

$query = "
SELECT 
    m.status AS match_status,
    m.matched_at,

    l.id AS lost_id,
    l.category AS lost_category,
    l.reported_location AS lost_location,
    l.created_at AS lost_date,
    l.cash_amount,
    l.gadget_type,
    l.gadget_brand,
    l.gadget_color,
    l.gadget_features,
    l.document_type,
    l.document_name,
    l.other_description AS lost_other,

    f.id AS found_id,
    f.category AS found_category,
    f.reported_location AS found_location,
    f.created_at AS found_date,
    f.cash_amount AS found_cash,
    f.gadget_type AS found_gadget_type,
    f.gadget_brand AS found_gadget_brand,
    f.gadget_color AS found_gadget_color,
    f.gadget_features AS found_gadget_features,
    f.document_type AS found_document_type,
    f.document_name AS found_document_name,
    f.other_description AS found_other

FROM lost_found_matches m

INNER JOIN lost_found l ON m.lost_id = l.id
INNER JOIN lost_found f ON m.found_id = f.id

WHERE l.is_claimed = 0
AND f.is_claimed = 0

ORDER BY m.matched_at DESC
";

$result = $conn->query($query);

$grouped = [];

while ($row = $result->fetch_assoc()) {

    $groupKey = '';

    // CASH
    if (
        $row['lost_category'] === 'Cash' &&
        $row['found_category'] === 'Cash' &&
        (float)$row['cash_amount'] === (float)$row['found_cash']
    ) {

        $groupKey = 'cash_' . $row['cash_amount'];
    }

    // GADGET
    elseif (
        $row['lost_category'] === 'Gadget' &&
        $row['found_category'] === 'Gadget' &&
        $row['gadget_type'] === $row['found_gadget_type'] &&
        $row['gadget_brand'] === $row['found_gadget_brand'] &&
        $row['gadget_color'] === $row['found_gadget_color']
    ) {

        $groupKey =
            'gadget_' .
            $row['gadget_type'] . '_' .
            $row['gadget_brand'] . '_' .
            $row['gadget_color'];
    }

    // DOCUMENT
    elseif (
        $row['lost_category'] === 'Document' &&
        $row['found_category'] === 'Document' &&
        $row['document_type'] === $row['found_document_type'] &&
        $row['document_name'] === $row['found_document_name']
    ) {

        $groupKey =
            'document_' .
            $row['document_type'] . '_' .
            $row['document_name'];
    }

    // OTHER
    else {

        if ($row['lost_other'] === $row['found_other']) {

            $groupKey =
                'other_' .
                md5($row['lost_other']);
        }
    }

    if ($groupKey != '') {

        // LOST ITEMS
        $grouped[$groupKey]['lost'][$row['lost_id']] = [
            'id' => $row['lost_id'],
            'category' => $row['lost_category'],
            'location' => $row['lost_location'],
            'date' => $row['lost_date'],
            'cash_amount' => $row['cash_amount'],
            'gadget_type' => $row['gadget_type'],
            'gadget_brand' => $row['gadget_brand'],
            'gadget_color' => $row['gadget_color'],
            'gadget_features' => $row['gadget_features'],
            'document_type' => $row['document_type'],
            'document_name' => $row['document_name'],
            'other' => $row['lost_other']
        ];

        // FOUND ITEMS
        $grouped[$groupKey]['found'][$row['found_id']] = [
            'id' => $row['found_id'],
            'category' => $row['found_category'],
            'location' => $row['found_location'],
            'date' => $row['found_date'],
            'cash_amount' => $row['found_cash'],
            'gadget_type' => $row['found_gadget_type'],
            'gadget_brand' => $row['found_gadget_brand'],
            'gadget_color' => $row['found_gadget_color'],
            'gadget_features' => $row['found_gadget_features'],
            'document_type' => $row['found_document_type'],
            'document_name' => $row['found_document_name'],
            'other' => $row['found_other']
        ];

        $grouped[$groupKey]['status'] = $row['match_status'];
    }
}

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Possible Matches</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

</head>

<body>

<div class="container py-5">
    <h2 class="mb-4">Possible Matches</h2>
    <table class="table table-bordered align-top">

    <tr>
        <th>Lost Items</th> <!-- width="40%"-->
        <th>Found Items</th> <!-- width="40%"-->
        <th>Status</th><!-- width="20%"-->
    </tr>

    <?php foreach($grouped as $group): ?>

    <tr>
        <!-- LOST -->
        <td>
            <?php foreach($group['lost'] as $lost): ?>
            <div class="border shadow-sm bg-white rounded p-3 mb-3">
                <b>Lost #<?= $lost['id'] ?></b><br>

                <?php if ($lost['category'] === 'Cash'): ?>
                    <span style="font-weight: 700"> ₱: </span><?= number_format($lost['cash_amount'], 2) ?>

                <?php elseif ($lost['category'] === 'Gadget'): ?>
                    <span style="font-weight: 700">Gadget Type:</span> <?= $lost['gadget_type'] ?><br>
                    <span style="font-weight: 700">Brand: </span> <?= $lost['gadget_brand'] ?><br>
                    <span style="font-weight: 700">Color: </span> <?= $lost['gadget_color'] ?>

                <?php elseif ($lost['category'] === 'Document'): ?>
                    <span style="font-weight: 700">Document Type: </span> <?= $lost['document_type'] ?><br>
                    <span style="font-weight: 700">Document Name: </span> <?= $lost['document_name'] ?>

                <?php else: ?>
                    <span style="font-weight: 700">Other: </span> <?= $lost['other'] ?>

                <?php endif; ?>

                <hr>

                <small class="text-muted">
                    <?= date('M d, Y h:i A', strtotime($lost['date'])) ?>
                </small>

                <br>

                <b>Location:</b> <?= $lost['location'] ?>
            </div>
            <?php endforeach; ?>
        </td>



        <!-- FOUND -->
        <td>
            <?php foreach($group['found'] as $found): ?>

            <div class="border rounded p-2 mb-2">

                <b>Found #<?= $found['id'] ?></b><br><br>

                <?php if ($found['category'] === 'Cash'): ?>
                    💵 ₱<?= number_format($found['cash_amount'], 2) ?>

                <?php elseif ($found['category'] === 'Gadget'): ?>
                    📱 <?= $found['gadget_type'] ?><br>
                    🏷 <?= $found['gadget_brand'] ?><br>
                    🎨 <?= $found['gadget_color'] ?>

                <?php elseif ($found['category'] === 'Document'): ?>
                    📄 <?= $found['document_type'] ?><br>
                    👤 <?= $found['document_name'] ?>

                <?php else: ?>
                    📝 <?= $found['other'] ?>

                <?php endif; ?>

                <hr>

                <small class="text-muted">
                    <?= date('M d, Y h:i A', strtotime($found['date'])) ?>
                </small>

                <br>

                📍 <b>Location:</b> <?= $found['location'] ?>
            </div>

            <?php endforeach; ?>
        </td>

        

        <!-- STATUS -->
        <td>
            <?php if ($group['status'] === 'pending'): ?>

            <span class="badge bg-warning text-dark">
                Pending
            </span>

            <?php elseif ($group['status'] === 'approved'): ?>

            <span class="badge bg-success">
                Approved
            </span>

            <?php else: ?>

            <span class="badge bg-danger">
                Rejected
            </span>

            <?php endif; ?>
        </td>
    </tr>

    <?php endforeach; ?>

    </table>
</div>

</body>
</html>