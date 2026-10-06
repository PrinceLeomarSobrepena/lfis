<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$result = $conn->query("

    /* ==========================================
       CREATED
       ========================================== */

    SELECT
        lf.id,
        lf.category,
        lf.status,

        'Created' AS activity_type,
        lf.created_at AS activity_time,

        ac.firstName AS admin_first,
        ac.lastName AS admin_last,

        sc.firstName AS staff_first,
        sc.lastName AS staff_last

    FROM lost_found lf

    LEFT JOIN admin ac
        ON ac.id = lf.created_by

    LEFT JOIN staff sc
        ON sc.id = lf.created_by

    WHERE lf.created_at IS NOT NULL

    
    UNION ALL

    /* ==========================================
       EDITED
       ========================================== */

    SELECT
        lf.id,
        lf.category,
        lf.status,

        'Edited' AS activity_type,
        lf.edited_at AS activity_time,

        ae.firstName AS admin_first,
        ae.lastName AS admin_last,

        se.firstName AS staff_first,
        se.lastName AS staff_last

    FROM lost_found lf

    LEFT JOIN admin ae
        ON ae.id = lf.edited_by

    LEFT JOIN staff se
        ON se.id = lf.edited_by

    WHERE lf.edited_at IS NOT NULL


    UNION ALL

    /* ==========================================
       RELEASED
       ========================================== */

    SELECT
        lf.id,
        lf.category,
        lf.status,

        'Released' AS activity_type,
        lf.claimed_date AS activity_time,

        ar.firstName AS admin_first,
        ar.lastName AS admin_last,

        sr.firstName AS staff_first,
        sr.lastName AS staff_last

    FROM lost_found lf

    LEFT JOIN admin ar
        ON ar.id = lf.released_by

    LEFT JOIN staff sr
        ON sr.id = lf.released_by

    WHERE lf.claimed_date IS NOT NULL


    UNION ALL

    /* ==========================================
       RESOLVED
       ========================================== */

    SELECT
        lf.id,
        lf.category,
        lf.status,

        'Resolved' AS activity_type,
        lf.resolved_at AS activity_time,

        av.firstName AS admin_first,
        av.lastName AS admin_last,

        sv.firstName AS staff_first,
        sv.lastName AS staff_last

    FROM lost_found lf

    LEFT JOIN admin av
        ON av.id = lf.resolved_by

    LEFT JOIN staff sv
        ON sv.id = lf.resolved_by

    WHERE lf.resolved_at IS NOT NULL


    UNION ALL

    /* ==========================================
    DELETED / DISPOSED
    ========================================== */

    SELECT
        ld.lost_found_id AS id,
        ld.category,
        ld.status,

        CASE
            WHEN ld.was_disposed = 1
                THEN 'Disposed'

            WHEN ld.was_resolved = 1
                THEN 'Resolved Deleted'

            WHEN ld.was_claimed = 1
                THEN 'Claimed Deleted'

            ELSE 'Deleted'
        END AS activity_type,

        ld.deleted_at AS activity_time,

        ad.firstName AS admin_first,
        ad.lastName AS admin_last,

        sd.firstName AS staff_first,
        sd.lastName AS staff_last

    FROM lost_found_deletions ld

    LEFT JOIN admin ad
        ON ld.deleted_role = 'admin'
        AND ad.id = ld.deleted_by

    LEFT JOIN staff sd
        ON ld.deleted_role = 'staff'
        AND sd.id = ld.deleted_by



    ORDER BY activity_time DESC

");

function getPerformedBy($row)
{
    if (!empty($row['admin_first'])) {
        return htmlspecialchars($row['admin_first'] . ' ' . $row['admin_last']) . ' (Admin)';
    }

    if (!empty($row['staff_first'])) {
        return htmlspecialchars($row['staff_first'] . ' ' . $row['staff_last']) . ' (Staff)';
    }
    return '--';
}

function formatDateTime($date)
{
    if (empty($date)) {
        return '--';
    }

    return date('M d, Y h:i A', strtotime($date));
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost & Found Audit Trail</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<link rel="stylesheet"
 href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        overflow-x: hidden;
    }

    table {
        white-space: nowrap;
    }

    .table-responsive {
        overflow-x: auto;
    }

    /* CREATED */
    .audit-created {
        color: #198754;
    }

    .bg-audit-created {
        background-color: #198754;
    }

    /* EDITED */
    .audit-edited {
        color: #b88600;
    }

    .bg-audit-edited {
        background-color: #ffc107;
    }

    /* RELEASED */
    .audit-released {
        color: #0d6efd;
    }

    .bg-audit-released {
        background-color: #0d6efd;
    }

    /* RESOLVED */
    .audit-resolved {
        color: #6f42c1;
    }

    .bg-audit-resolved {
        background-color: #6f42c1;
    }

    .timeline-icon {
        width: 38px;
        height: 38px;

        border-radius: 50%;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        color: white;
    }
    </style>
</head>
<body>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2>Lost & Found</h2>
                <p class="fs-5 text-muted mb-0">Audit Trail</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Item ID</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Activity</th>
                        <th>Performed By</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>

                        <!-- ITEM ID -->
                        <td class="text-center"> 
                            #<?= htmlspecialchars($row['id']) ?> 
                        </td>

                        <!-- CATEGORY -->
                        <td class="text-center"> 
                            <?= htmlspecialchars($row['category']) ?> 
                        </td>

                        <!-- STATUS -->
                        <td class="text-center"> 
                            <?= htmlspecialchars($row['status']) ?> 
                        </td>

                        <!-- ACTIVITY -->
                        <td>
                            <?php if ($row['activity_type'] === 'Created'): ?>
                                <span class="timeline-icon bg-audit-created me-2">
                                    <i class="fa-solid fa-plus"></i>
                                </span>

                                <strong class="audit-created"> Item Created </strong>

                            <?php elseif ($row['activity_type'] === 'Edited'): ?>
                                <span class="timeline-icon bg-audit-edited me-2">
                                    <i class="fa-solid fa-pen"></i>
                                </span>

                                <strong class="audit-edited"> Item Edited </strong>

                            <?php elseif ($row['activity_type'] === 'Released'): ?>
                                <span class="timeline-icon bg-audit-released me-2">
                                    <i class="fa-solid fa-hand-holding"></i>
                                </span>

                                <strong class="audit-released"> Item Released / Claimed </strong>

                            <?php elseif ($row['activity_type'] === 'Resolved'): ?>
                                <span class="timeline-icon me-2" style="background-color: #6f42c1;">
                                    <i class="fa-solid fa-circle-check"></i>
                                </span>

                                <strong style="color: #6f42c1;"> Item Resolved </strong>

                            <?php elseif ($row['activity_type'] === 'Disposed'): ?>
                                <span class="timeline-icon me-2" style="background-color: #dc3545;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </span>

                                <strong style="color: #dc3545;">
                                    Item Disposed
                                </strong>

                            <?php elseif ($row['activity_type'] === 'Claimed Deleted'): ?>
                                <span class="timeline-icon me-2"
                                    style="background-color: #dc3545;">
                                    <i class="fa-solid fa-trash"></i>
                                </span>

                                <strong style="color: #dc3545;"> Item Claimed Deleted </strong>

                            <?php elseif ($row['activity_type'] === 'Resolved Deleted'): ?>
                                <span class="timeline-icon me-2"
                                    style="background-color: #dc3545;">
                                    <i class="fa-solid fa-trash"></i>
                                </span>

                                <strong style="color: #dc3545;"> Item Resolved Deleted </strong>

                            <?php elseif ($row['activity_type'] === 'Deleted'): ?>
                                <span class="timeline-icon me-2" style="background-color: #dc3545;">
                                    <i class="fa-solid fa-trash"></i>
                                </span>

                                <strong style="color: #dc3545;"> Item Deleted </strong>
                            <?php endif; ?>
                        </td>

                        <!-- PERFORMED BY -->
                        <td>
                            <?= getPerformedBy($row) ?>
                        </td>

                        <!-- DATE & TIME -->
                        <td>
                            <?= formatDateTime($row['activity_time']) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>