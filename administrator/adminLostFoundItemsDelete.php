<?php

include '../middleware/adminMiddleware.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adminLostFoundItems.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("CSRF validation failed");
}

$id = intval($_POST['id']);

$deleted_by = $_SESSION['admin']['id'];
$deleted_role = 'admin';

// =====================================================
// STEP 1: GET RECORD
// =====================================================

$stmt = $conn->prepare("SELECT * FROM lost_found WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

$stmt->close();

if ($data) {

    $image = $data['image'];

    $wasResolved = (int)$data['is_resolved'];
    $wasClaimed = (int)$data['is_claimed'];

    // =================================================
    // STEP 2: SAVE DISPOSAL AUDIT
    // =================================================

    $wasDisposed = 1;

    $deleteAudit = $conn->prepare("INSERT INTO lost_found_deletions (lost_found_id, category, status, was_claimed, was_resolved, was_disposed, deleted_by, deleted_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $deleteAudit->bind_param("issiiiis", $data['id'], $data['category'], $data['status'], $wasClaimed, $wasResolved, $wasDisposed, $deleted_by, $deleted_role);
    $deleteAudit->execute();
    $deleteAudit->close();

    // =================================================
    // STEP 3: DELETE SELECTED RECORD ONLY
    // =================================================

    $stmt = $conn->prepare("DELETE FROM lost_found WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // =================================================
    // STEP 4: DELETE IMAGE IF UNUSED
    // =================================================

    if (!empty($image) && $image !== 'default1.png') {
        $check = $conn->prepare("SELECT COUNT(*) AS count FROM lost_found WHERE image = ?");
        $check->bind_param("s", $image);
        $check->execute();
        $count = $check ->get_result() ->fetch_assoc()['count'];
        $check->close();

        if ($count == 0 && file_exists("../uploads/" . $image)
        ) {
            unlink("../uploads/" . $image);
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(
    random_bytes(32)
);

header("Location: adminLostFoundItems.php");
exit();
?>
