<?php
session_start();

header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

// check if a user is logged in
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// dashboard panel only needs pending requests, most recent first
$sql = "
    SELECT
        ot.id AS overtime_request_id,
        ot.user_id,
        ot.overtime_date,
        ot.start_time,
        ot.end_time,
        ot.total_hours,
        ot.overtime_type,
        ot.status,
        ot.created_at,
        u.full_name,
        d.name AS department_name
    FROM overtime_requests ot
    JOIN users u ON ot.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE ot.status = 'pending'
    ORDER BY ot.created_at DESC
    LIMIT 5
";

$result = $connection->query($sql);
$overtime_requests = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($overtime_requests);
exit();