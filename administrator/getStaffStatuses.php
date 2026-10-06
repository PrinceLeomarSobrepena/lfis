<?php
include '../config/db.php';

$result = $conn->query("
    SELECT a.id, l.status
    FROM staff a
    LEFT JOIN (
        SELECT staffId, status 
        FROM staffLogs 
        WHERE id IN (
            SELECT MAX(id) 
            FROM staffLogs 
            GROUP BY staffId
        )
    ) l ON a.id = l.staffId
");

$statuses = [];
while ($row = $result->fetch_assoc()) {
    $statuses[$row['id']] = strtolower($row['status'] ?? 'offline');
}

header('Content-Type: application/json');
echo json_encode($statuses);
