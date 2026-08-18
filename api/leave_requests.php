<?php
//LEAVE REQUESTS IN THE ADMIN DASHBOARD
session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

//check if a user is logged in
if (!isset($_SESSION['id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in first.']);
    exit();
}

$leave_sql= "SELECT
    lr.id as leave_request_id,
    lr.user_id,
    lr.start_date,
    lr.end_date,
    lr.type as leave_type,
    lr.status,
    lr.created_at,
    u.full_name,
    u.email,
    d.name as department_name
FROM leave_requests lr
JOIN users u ON lr.user_id = u.id
LEFT JOIN departments d ON u.department_id = d.id
WHERE lr.status = 'pending'
";

$result = $connection->query($leave_sql);

// saves the result in an array
$leave_requests = $result->fetch_all(MYSQLI_ASSOC);

//then converts the array into json and sends it to the client
echo json_encode($leave_requests);


    ?>