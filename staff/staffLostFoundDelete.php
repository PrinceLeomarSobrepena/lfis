<?php
include '../middleware/staffMiddleware.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: staffLostFoundList.php");
    exit();
}

if ( !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF validation failed");
}

$id = intval($_POST['id']);

$deleted_by = $_SESSION['staff']['id'];
$deleted_role = 'staff';

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

    $deleteAudit = $conn->prepare("INSERT INTO lost_found_deletions (lost_found_id, category, status, was_resolved, was_claimed, deleted_by, deleted_role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $deleteAudit->bind_param("issiiis", $data['id'], $data['category'], $data['status'], $wasResolved, $wasClaimed, $deleted_by, $deleted_role);
    $deleteAudit->execute();
    $deleteAudit->close();

    // =================================================
    // IF CURRENT ITEM IS RESOLVED
    // DELETE PAIRED ITEM TOO
    // =================================================

    if ($data['is_resolved'] == 1) {
        $match = $conn->prepare("SELECT * FROM lost_found_matches WHERE lost_id = ? OR found_id = ? LIMIT 1");
        $match->bind_param("ii", $id, $id);
        $match->execute();
        $matchResult = $match->get_result();

        if ($matchRow = $matchResult->fetch_assoc()) {

            // =========================================
            // FIND PAIRED ITEM
            // =========================================

            if ($matchRow['lost_id'] == $id) {
                $otherId = $matchRow['found_id'];
            } else {
                $otherId = $matchRow['lost_id'];
            }

            // =========================================
            // GET PAIRED ITEM
            // =========================================

            $pair = $conn->prepare(" SELECT * FROM lost_found WHERE id = ?");
            $pair->bind_param("i", $otherId);
            $pair->execute();

            $pairData = $pair
                ->get_result()
                ->fetch_assoc();
            $pair->close();

            if ($pairData) {

                // =====================================
                // SAVE PAIRED ITEM TO DELETE AUDIT
                // =====================================

                $pairWasResolved = (int)$pairData['is_resolved'];

                $pairAudit = $conn->prepare("INSERT INTO lost_found_deletions ( lost_found_id, category, status, was_resolved, deleted_by, deleted_role) VALUES (?, ?, ?, ?, ?, ?)");
                $pairAudit->bind_param("ississ", $pairData['id'], $pairData['category'], $pairData['status'], $pairWasResolved, $deleted_by, $deleted_role);
                $pairAudit->execute();
                $pairAudit->close();

                // =====================================
                // DELETE PAIRED ITEM
                // =====================================

                $deletePair = $conn->prepare("DELETE FROM lost_found WHERE id = ?");
                $deletePair->bind_param("i", $otherId);
                $deletePair->execute();
                $deletePair->close();

                // =====================================
                // DELETE PAIRED IMAGE IF UNUSED
                // =====================================

                if (!empty($pairData['image']) && $pairData['image'] !== 'default.jpg') {
                    $check = $conn->prepare(" SELECT COUNT(*) AS total FROM lost_found WHERE image = ?");
                    $check->bind_param("s", $pairData['image']);
                    $check->execute();

                    $count = $check
                        ->get_result()
                        ->fetch_assoc()['total'];
                    $check->close();

                    if ($count == 0 && file_exists( "../uploads/" . $pairData['image'])) {
                        unlink("../uploads/" . $pairData['image']);
                    }
                }
            }

            // =========================================
            // DELETE MATCH RECORD
            // =========================================

            $deleteMatch = $conn->prepare("DELETE FROM lost_found_matches WHERE lost_id = ? OR found_id = ?");
            $deleteMatch->bind_param("ii", $id, $id);
            $deleteMatch->execute();
            $deleteMatch->close();
        }

        $match->close();
    }

    // =================================================
    // STEP 2: DELETE CURRENT ITEM
    // =================================================

    $stmt = $conn->prepare("DELETE FROM lost_found WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // =================================================
    // STEP 3: DELETE CURRENT IMAGE IF UNUSED
    // =================================================

    if (!empty($image) && $image !== 'default.jpg') {
        $check = $conn->prepare("SELECT COUNT(*) AS total FROM lost_found WHERE image = ?");
        $check->bind_param("s", $image);
        $check->execute();

        $count = $check
            ->get_result()
            ->fetch_assoc()['total'];
        $check->close();

        if ( $count == 0 && file_exists("../uploads/" . $image)) {
            unlink("../uploads/" . $image);
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(
    random_bytes(32)
);

header("Location: staffLostFoundList.php");
exit();
?>