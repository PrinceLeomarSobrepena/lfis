<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// $result = $conn->query("SELECT * FROM staff ORDER BY created_at DESC");
$result = $conn->query("
    SELECT a.*, l.status AS staff_status
    FROM staff a
    LEFT JOIN (
        SELECT staffId, status 
        FROM staffLogs 
        WHERE id IN ( SELECT MAX(id)  FROM staffLogs  GROUP BY staffId)
    ) l ON a.id = l.staffId
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Responsive Bootstrap Table</title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- Data Table -->
<link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">

<style>
    /* Optional smoother scroll */
    .table-responsive {
        overflow-x: auto;
    }

    /* Prevent text from going down */
    table {
        white-space: nowrap;
    }


    .dataTables_filter {
    margin-bottom: 15px;
}

.dataTables_filter input {
    border: 1px solid #ced4da !important;
    border-radius: 8px;
    padding: 6px 12px;
    margin-left: 8px;
      outline: none;
}

    .dataTables_paginate {
        margin-top: 10px;
    }

    
</style>
</head>
<body>

    <div class="container my-5">
        <h2>Staff Information</h2>
        <div class="mb-2">
           <a href="adminDashboard.php" class="btn btn-secondary" style="padding: 6px 30px"><i class="fa-solid fa-arrow-left"></i> Back </a>
            <a href="adminStaffCreate.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Staff </a>
        </div>
        <div class="table-responsive">
            <table id="staffTable" class="table table-striped  align-middle" style="border: 1px solid #ced4da;"><!---table-hover  table-bordered -->
                <thead class="table-dark text-center">
                    <tr>
                        <th style=" text-align: center !important;  vertical-align: middle;">Image</th>
                        <th  style=" text-align: center !important;  vertical-align: middle;">Name</th>
                        <th  style=" text-align: center !important;  vertical-align: middle;">Email</th>
                        <th  style=" text-align: center !important;  vertical-align: middle;">Contact</th>
                        <th  style=" text-align: center !important;  vertical-align: middle;">Ban</th>
                        <th  style=" text-align: center !important;  vertical-align: middle;">Status</th>
                        <th  style=" text-align: center !important;  vertical-align: middle;">Reset Password</th>
                        <th>Logged</th>
                        <th  style=" text-align: center !important;  vertical-align: middle;">Action</th>
                    </tr>
                </thead>

                <tbody class="text-center">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($row['image']): ?>
                                    <img src="../uploads/<?= $row['image'] ?>" width="50" height="50">
                                <?php endif; ?>
                            </td>

                            <td><?= htmlspecialchars($row['firstName']. ' ' . $row['lastName'])?></td>
                            <td><?= htmlspecialchars($row['email'])?></td>
                            <td><?= htmlspecialchars($row['contact'])?></td>

                            <td>
                                <form method="post" action="adminStaffBan.php" style="display:inline;"
                                    onsubmit="return confirm('<?= $row['banned'] ? 'Unban' : 'Ban' ?> this customer?');">

                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                    <button type="submit"
                                        class="btn btn-sm rounded-5 fw-bold text-white">

                                        <i class="fa-solid fa-ban"
                                        style="color: <?= $row['banned'] ? '#f89d54ff' : '#8c969fff' ?>;
                                            font-size: 25px">
                                        </i>
                                    </button>
                                </form>
                            </td>

                            <td>
                                <?= $row['banned']
                                    ? '<span class="badge bg-danger rounded-5">Banned</span>'
                                    : '<span class="badge bg-success rounded-5">Active</span>' ?>
                            </td>

                            <td>
                                <form action="adminStaffResetLoginPassword.php" method="POST" style="display:inline;" onsubmit="return confirm('Reset this staff password to the default password?');">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <button type="submit" class="btn btn-warning btn-sm" title="Reset Password">
                                        <i class="fa-solid fa-key" style="font-size: 18px;" ></i>
                                    </button>
                                </form>
                            </td>

                            <td>
                                <a href="adminStaffUpdate.php?id=<?= $row['id']?>" class="btn btn-warning btn-sm" title="Edt Staff"><i class="fa-solid fa-pen-to-square text-black" style="font-size: 18px"></i></a>
                                
                                <form action="adminStaffDelete.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $row['id']?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']?>">

                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this staff?')" title="Delete Staff">
                                        <i class="fa-solid fa-trash-can" style="font-size: 18px"></i>
                                    </button>
                                </form>
                            </td>

                            <!-- Online/Offline Status -->
                            <td id="status-<?= $row['id'] ?>">
                                    <?php
                                        $status = strtolower($row['admin_status'] ?? 'offline');
                                        if ($status === 'online') {
                                            echo "<span class='text-success fw-bold'>🟢 Online</span>";
                                        } else {
                                            echo "<span class='text-secondary fw-bold'>🔴 Offline</span>";
                                        }
                                    ?>
                                </td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JS Library -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {
    $('#staffTable').DataTable({
        pageLength: 10,
        responsive: true
    });
});
</script>

<script>
function updateAdminStatuses() {
    fetch('getAdminStatuses.php')
        .then(res => res.json())
        .then(data => {
            Object.entries(data).forEach(([id, status]) => {
                const cell = document.getElementById('status-' + id);
                if (cell) {
                    if (status.toLowerCase() === 'online') {
                        cell.innerHTML = "<span class='text-success fw-bold'>🟢 Online</span>";
                    } else {
                        cell.innerHTML = "<span class='text-secondary fw-bold'>🔴 Offline</span>";
                    }
                }
            });
        })
        .catch(err => console.error('Failed to fetch statuses:', err));
}

// Run every 15 seconds
setInterval(updateAdminStatuses, 15000);
</script>

</body>
</html>