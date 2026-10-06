<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// $result = $conn->query("SELECT * FROM lost_found ORDER BY created_at DESC");

// $result = $conn->query("
// SELECT lost_found.*, admin.firstName, admin.lastName
// FROM lost_found
// LEFT JOIN admin
// ON admin.id = lost_found.created_by
// ORDER BY lost_found.created_at DESC
// ");

$result = $conn->query("
SELECT
    lf.*,

    ac.firstName AS admin_created_first,
    ac.lastName AS admin_created_last,

    sc.firstName AS staff_created_first,
    sc.lastName AS staff_created_last,

    e.firstName AS edited_first,
    e.lastName AS edited_last,

    r.firstName AS released_first,
    r.lastName AS released_last,

    rv.firstName AS resolved_first,
    rv.lastName AS resolved_last

FROM lost_found lf


LEFT JOIN admin ac
    ON ac.id = lf.created_by 

LEFT JOIN staff sc
    ON sc.id = lf.created_by  

LEFT JOIN admin e
    ON e.id = lf.edited_by

LEFT JOIN admin r
    ON r.id = lf.released_by

LEFT JOIN admin rv
    ON rv.id = lf.resolved_by

ORDER BY lf.created_at DESC
");
?>
<!--    // c.firstName AS created_first,
        // c.lastName AS created_last,
    

        // LEFT JOIN admin c
        //     ON c.id = lf.created_by
    -->

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost & Found List</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial, sans-serif;
    overflow-x:hidden;
    /* background:#f5f6fa; */
}

        /* Prevent text from going down */
    table {
        white-space: nowrap;
    }

    /* Optional smoother scroll */
    .table-responsive {
        overflow-x: auto;
    }

    .bg-purple{
    background-color: #6f42c1;
    color: white;
}
.icon-link{
    font-size: 21px;
    color: #6c757d;
    cursor: pointer;
    text-decoration: none;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    background: transparent;
    border: none;
    padding: 0;
}

.icon-link:hover{
    color: #4c5257;
}
</style>
<body>

    <div class="container py-5">
        <h2>Lost & Found</h2>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="fs-5 text-muted mb-0">All List</p>

            <div class="d-flex align-items-center gap-3">
                <a href="adminLostFoundMatches.php" class="icon-link ms-2">
                <i class="fa-solid fa-swatchbook"></i>
                </a>

                <a href="adminLostFoundMatches.php" class="icon-link">
                    <i class="fa-solid fa-list-ul"></i>
                </a>

                <a href="adminLostFoundCreate.php" class="btn  btn-primary">
                    Add Item
                </a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle" ><!---table-hover table-bordered        style="border: 1px solid #ced4da;"-->
                <thead class="table-dark text-center">
                    <tr>
                        <th>Image</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Item Details</th>
                        <th>Reported Location</th>
                        <th>Claim Status</th>
                        <th>Created At</th>
                        <th>Action</th>
                        <th>Created By</th>
                        <th>Edited By</th>
                        <th>Edited At</th>
                        <th>Released By</th>
                        <th>Released At</th>
                        <th>Resolved By</th>
                        <th>Resolved At</th>
                    </tr>
                </thead>

                <tbody class="text-center">
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

                <tr>

                    <td>
                        <img src="../uploads/<?= $row['image'] ?>" width="60">
                    </td>

                    <td><?= $row['status'] ?></td>

                    <td><?= $row['category'] ?></td>

                    <td><?= $itemDetails ?></td>

                    <td><?= $row['reported_location'] ?></td>

                    <!-- <td>
                        <?= $row['is_claimed']
                            ? '<span class="badge bg-success">Claimed</span>'
                            : '<span class="badge bg-secondary">Unclaimed</span>'
                        ?>
                    </td> -->

                    <!--<td>
                            <?= $row['is_claimed'] ? '<span class="badge bg-success">Claimed</span>' : 'N/A' ?>           <span class="badge bg-danger">Unclaimed</span> 
                        </td>-->
                    <td>
                        <?php

                        if($row['is_claimed']){
                            echo '<span class="badge bg-success rounded-5"> Claimed </span>';
                        }

                        elseif($row['is_resolved']){
                            echo '<span class="badge bg-purple rounded-5"> Resolved </span>';
                        }

                        else{
                            echo '<span class="badge bg-secondary rounded-5"  style="font-size: 14px; "> Pending </span>';
                        }

                        ?>
                    </td>


                    <td>
                        <?= date('M d, Y', strtotime($row['created_at'])) ?>
                        <!-- <br> -->
                        <?= date('h:i A', strtotime($row['created_at'])) ?>
                    </td>

                    <td>
                        <!-- <a href="adminLostFoundUpdate.php?id=<?= $row['id'] ?>"
                            class="btn btn-warning btn-sm">
                            <i class="fa-solid fa-pen-to-square text-white"></i>
                        </a> -->
                        <?php if (!$row['is_claimed'] && !$row['is_resolved']): ?>
                            <a href="adminLostFoundUpdate.php?id=<?= $row['id'] ?>"
                                class="btn btn-warning btn-sm">
                                <i class="fa-solid fa-pen-to-square text-black" style="font-size: 18px"></i>
                            </a>
                        <?php endif; ?>

                        <!-- <?php if ($row['status'] === 'Found' && !$row['is_claimed']): ?>
                            <a href="adminLostFoundClaim.php?id=<?= $row['id'] ?>"
                                class="btn btn-success btn-sm">
                                <i class="fa-solid fa-hand-point-down"></i>
                            </a>
                        <?php endif; ?> -->
                        <?php if ($row['status'] === 'Found' && !$row['is_claimed'] && !$row['is_resolved']): ?>
                            <a href="adminLostFoundClaim.php?id=<?= $row['id'] ?>"
                                class="btn btn-success btn-sm">
                                <!-- <i class="fa-solid fa-hand-point-down"></i> -->
                                <i class="fa-solid fa-hand-pointer" style="font-size: 18px"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($row['is_claimed']): ?>
                            <a href="adminLostFoundView.php?id=<?= $row['id'] ?>"
                                class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-eye" style="color: white"></i>
                            </a>
                        <?php endif; ?>

                        <form method="POST" action="adminLostFoundDelete.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"> <i class="fa-solid fa-trash-can" style="font-size: 18px"></i></button><!-- delete -->
                        </form>
                    </td>

                    <!-- <td>
                        <?= $row['created_first'] ? $row['created_first'].' '.$row['created_last'] : '-' ?>
                    </td> -->
<td>
    <?php
    if (!empty($row['admin_created_first'])) {
        echo $row['admin_created_first'] . ' ' . $row['admin_created_last'];
    }
    elseif (!empty($row['staff_created_first'])) {
        echo $row['staff_created_first'] . ' ' . $row['staff_created_last'];
    }
    else {
        echo '-';
    }
    ?>
</td>



                    <td>
                        <?= $row['edited_first'] ? $row['edited_first'].' '.$row['edited_last'] : '--' ?>
                    </td>

                    <td>
                        <?php if (!empty($row['edited_at'])): ?>
                            <?= date('M d, Y', strtotime($row['edited_at'])) ?>
                            <!-- <br> -->
                            <?= date('h:i A', strtotime($row['edited_at'])) ?>
                        <?php else: ?>
                            --
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= $row['released_first'] ? $row['released_first'].' '.$row['released_last'] : '--' ?>
                    </td>
                    <!-- Released At -->
                    <td>
                        <?php if (!empty($row['claimed_date'])): ?>
                            <?= date('M d, Y', strtotime($row['claimed_date'])) ?>
                            <!-- <br> -->
                            <?= date('h:i A', strtotime($row['claimed_date'])) ?>
                        <?php else: ?>
                            --
                        <?php endif; ?>
                    </td>


                    <td>
                        <?= $row['resolved_first']
                            ? $row['resolved_first'].' '.$row['resolved_last']
                            : '--' ?>
                    </td>

                    <td>
                        <?php if (!empty($row['resolved_at'])): ?>
                            <?= date('M d, Y', strtotime($row['resolved_at'])) ?>
                            <?= date('h:i A', strtotime($row['resolved_at'])) ?>
                        <?php else: ?>
                            --
                        <?php endif; ?>
                    </td>
                
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>