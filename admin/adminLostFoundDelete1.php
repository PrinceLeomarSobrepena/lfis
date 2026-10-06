<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adminLostFoundList.php");
    exit();
}

// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF validation failed");
}

$id = intval($_POST['id']);

// Step 1: Get the record's image first
$stmt = $conn->prepare("SELECT * FROM lost_found WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if ($data) {

    $image = $data['image'];

    // IF ITEM IS RESOLVED
    // Delete the paired Lost/Found item and the match record
    if ($data['is_resolved'] == 1) {

        // Hanapin ang kaparehang record
        $match = $conn->prepare("SELECT * FROM lost_found_matches WHERE lost_id = ? OR found_id = ? LIMIT 1");
        $match->bind_param("ii", $id, $id);
        $match->execute();
        $matchResult = $match->get_result();

        if ($matchRow = $matchResult->fetch_assoc()) {
        
            // Determine the paired item
            if ($matchRow['lost_id'] == $id) {
                $otherId = $matchRow['found_id'];
            } else {
                $otherId = $matchRow['lost_id'];
            }

            // Get paired item image
            $pair = $conn->prepare("SELECT image FROM lost_found WHERE id = ?");
            $pair->bind_param("i", $otherId);
            $pair->execute();
            $pairData = $pair->get_result()->fetch_assoc();
            $pair->close();

            // Delete paired Lost/Found item
            $deletePair = $conn->prepare("DELETE FROM lost_found WHERE id = ?");
            $deletePair->bind_param("i", $otherId);
            $deletePair->execute();
            $deletePair->close();

            // Delete paired image if unused
            if ($pairData && $pairData['image'] != 'default.jpg') {
                $check = $conn->prepare("SELECT COUNT(*) total FROM lost_found WHERE image = ?");
                $check->bind_param("s", $pairData['image']);
                $check->execute();
                $count = $check->get_result()->fetch_assoc()['total'];
                $check->close();

                if ($count == 0 && file_exists("../uploads/" . $pairData['image'])) {
                    unlink("../uploads/" . $pairData['image']);
                }
            }

            //Delete Match Record
            $deleteMatch = $conn->prepare("DELETE FROM lost_found_matches WHERE lost_id = ? OR found_id = ?");
            $deleteMatch->bind_param("ii", $id, $id);
            $deleteMatch->execute();
            $deleteMatch->close();
        }

        $match->close();
    }

    // Step 2: Delete record from DB
    $stmt = $conn->prepare("DELETE FROM lost_found WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Step 3: Delete image file if it's not default and not used by any other record
    if ($image != 'default.jpg') {
        $check = $conn->prepare(" SELECT COUNT(*) count FROM lost_found WHERE image = ?");
        $check->bind_param("s", $image);
        $check->execute();
        $count = $check->get_result()->fetch_assoc()['count'];
        $check->close();

        if (
            $count == 0 && file_exists("../uploads/" . $image)) {
            unlink("../uploads/" . $image);
        }
    }
}

// Regenerate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header("Location: adminLostFoundList.php");
exit();
?>