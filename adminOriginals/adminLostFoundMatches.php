<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';

/*
|--------------------------------------------------------------------------
| RESOLVE MATCH
|--------------------------------------------------------------------------
*/

if(isset($_POST['resolve_match'])){

    if(
        isset($_POST['lost_id']) &&
        isset($_POST['found_id'])
    ){

        $lost_id = $_POST['lost_id'];
        $found_id = $_POST['found_id'];

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
            SET is_resolved = 1
            WHERE id IN (?, ?)
        ");

        $update->bind_param("ii", $lost_id, $found_id);
        $update->execute();

        header("Location: adminLostFoundMatches.php");
        exit;
    }
}

$query = "

SELECT 

    m.id AS match_id,

    l.id AS lost_id,
    l.category AS lost_category,
    l.reported_location AS lost_location,
    l.created_at AS lost_date,
    l.cash_amount,
    l.gadget_type,
    l.gadget_brand,
    l.gadget_color,
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
    f.document_type AS found_document_type,
    f.document_name AS found_document_name,
    f.other_description AS found_other

FROM lost_found_matches m

INNER JOIN lost_found l ON m.lost_id = l.id
INNER JOIN lost_found f ON m.found_id = f.id

WHERE m.is_resolved = 0

ORDER BY m.matched_at DESC

";

$result = $conn->query($query);

$grouped = [];

while($row = $result->fetch_assoc()){

    $groupKey = '';

    // ✅ AGE CHECK (IMPORTANT FIX)
    $isLostRecent = (strtotime($row['lost_date']) >= strtotime('-7 days'));
    $isFoundRecent = (strtotime($row['found_date']) >= strtotime('-7 days'));

    $timeBucket = ($isLostRecent && $isFoundRecent) ? 'recent' : 'old';

    if(
        $row['lost_category'] === 'Cash' &&
        $row['found_category'] === 'Cash' &&
        (float)$row['cash_amount'] === (float)$row['found_cash']
    ){
        $groupKey = 'cash_' . $row['cash_amount'] . '_' . $timeBucket;
    }

    elseif(
        $row['lost_category'] === 'Gadget' &&
        $row['found_category'] === 'Gadget' &&
        $row['gadget_type'] === $row['found_gadget_type'] &&
        $row['gadget_brand'] === $row['found_gadget_brand'] &&
        $row['gadget_color'] === $row['found_gadget_color']
    ){
        $groupKey =
            'gadget_' .
            $row['gadget_type'] . '_' .
            $row['gadget_brand'] . '_' .
            $row['gadget_color'] .
            '_' . $timeBucket;
    }

    elseif(
        $row['lost_category'] === 'Document' &&
        $row['found_category'] === 'Document' &&
        $row['document_type'] === $row['found_document_type'] &&
        $row['document_name'] === $row['found_document_name']
    ){
        $groupKey =
            'document_' .
            $row['document_type'] . '_' .
            $row['document_name'] .
            '_' . $timeBucket;
    }

    else{
        if($row['lost_other'] === $row['found_other']){
            $groupKey =
                'other_' .
                md5($row['lost_other']) .
                '_' . $timeBucket;
        }
    }

    if($groupKey != ''){

        $grouped[$groupKey]['lost'][$row['lost_id']] = [
            'id' => $row['lost_id'],
            'category' => $row['lost_category'],
            'location' => $row['lost_location'],
            'date' => $row['lost_date'],
            'cash_amount' => $row['cash_amount'],
            'gadget_type' => $row['gadget_type'],
            'gadget_brand' => $row['gadget_brand'],
            'gadget_color' => $row['gadget_color'],
            'document_type' => $row['document_type'],
            'document_name' => $row['document_name'],
            'other' => $row['lost_other']
        ];

        $grouped[$groupKey]['found'][$row['found_id']] = [
            'id' => $row['found_id'],
            'category' => $row['found_category'],
            'location' => $row['found_location'],
            'date' => $row['found_date'],
            'cash_amount' => $row['found_cash'],
            'gadget_type' => $row['found_gadget_type'],
            'gadget_brand' => $row['found_gadget_brand'],
            'gadget_color' => $row['found_gadget_color'],
            'document_type' => $row['found_document_type'],
            'document_name' => $row['found_document_name'],
            'other' => $row['found_other']
        ];
    }
}

?>

<!-- HTML stays EXACT same below -->

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Possible Matches</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
    /* Prevent text from going down */
    table {
        white-space: nowrap;
    }

    /* Optional smoother scroll */
    .table-responsive {
        overflow-x: auto;
    }
</style>
</head>
<body>

<div class="container py-5">
    <h2 class="mb-4">Possible Matches</h2>

    <div class="table-responsive">
        <table class="table table-bordered align-top"> <!--  -->

            <thead class="table-dark text-center">
                <tr>
                    <th>Lost Items</th>
                    <th>Found Items</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach($grouped as $group): ?>
                <form method="POST">
                    <tr>

                        <td>
                            <?php foreach($group['lost'] as $lost): ?>
                                <!-- <div class="border rounded p-3 my-2"> -->
                                <label class="border rounded p-3 my-2 d-block" style="cursor:pointer;">

                                    <input type="radio" name="lost_id" class="form-check-input" value="<?= $lost['id'] ?>" required>
                                    <b>Lost #<?= $lost['id'] ?></b>

                                    <br>

                                    <?php if($lost['category'] === 'Cash'): ?>
                                        ₱<?= number_format($lost['cash_amount'], 2) ?>
                                    <?php elseif($lost['category'] === 'Gadget'): ?>
                                        <?= $lost['gadget_type'] ?><br>
                                        <?= $lost['gadget_brand'] ?><br>
                                        <?= $lost['gadget_color'] ?>
                                    <?php elseif($lost['category'] === 'Document'): ?>
                                        <?= $lost['document_type'] ?><br>
                                        <?= $lost['document_name'] ?>
                                    <?php else: ?>
                                        <?= $lost['other'] ?>
                                    <?php endif; ?>

                                    <hr>
                                    <?= $lost['location'] ?><br>
                                    <small><?= date('M d, Y h:i A', strtotime($lost['date'])) ?></small>
                                </label>
                            <?php endforeach; ?>
                        </td>

                        <td>
                            <?php foreach($group['found'] as $found): ?>
                                <label class="border rounded p-3 my-2 d-block" style="cursor:pointer;"> <!--    -->

                                    <input type="radio" name="found_id" class="form-check-input" value="<?= $found['id'] ?>" required>
                                    <b>Found #<?= $found['id'] ?></b>

                                    <br>

                                    <?php if($found['category'] === 'Cash'): ?>
                                        ₱<?= number_format($found['cash_amount'], 2) ?>
                                    <?php elseif($found['category'] === 'Gadget'): ?>
                                        <?= $found['gadget_type'] ?><br>
                                        <?= $found['gadget_brand'] ?><br>
                                        <?= $found['gadget_color'] ?>
                                    <?php elseif($found['category'] === 'Document'): ?>
                                        <?= $found['document_type'] ?><br>
                                        <?= $found['document_name'] ?>
                                    <?php else: ?>
                                        <?= $found['other'] ?>
                                    <?php endif; ?>

                                    <hr>
                                    <?= $found['location'] ?><br>
                                    <small><?= date('M d, Y h:i A', strtotime($found['date'])) ?></small>
                                </label>
                            <?php endforeach; ?>
                        </td>

                        <td class="text-center align-middle">
                            <button type="submit" name="resolve_match" class="btn btn-success">
                                Resolve
                            </button>
                        </td>

                    </tr>
                </form>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</div>




<script>
document.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {

        document.querySelectorAll(
            'input[name="' + this.name + '"]'
        ).forEach(r => {
            r.closest('.select-card').classList.remove('active');
        });

        this.closest('.select-card').classList.add('active');
    });
});
</script>

</body>
</html>