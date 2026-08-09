<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access. Please log in first.']);
    exit();
}

// get the filter from the page and sanitize it
// empty string if no filter is provided

//employee search filter
$search      = trim($_GET['search'] ?? '');
$department  = trim($_GET['department'] ?? '');
$status      = trim($_GET['status'] ?? '');
$date_from   = trim($_GET['date_from'] ?? '');
$date_to     = trim($_GET['date_to'] ?? '');

// containers
$conditions = []; //where
$params = []; //value that will be put in '?'
$types = ''; //s

if ($search !== '') {
    //search the employee that matches the name
    $conditions[] = "u.full_name LIKE CONCAT('%', ?, '%')";
    $params[] = $search;
    $types .= 's';
}

if ($department !== '' && $department !== 'all') {
    //search the department
    $conditions[] = "d.name = ?";
    $params[] = $department;
    $types .= 's';
}

if ($status !== '' && $status !== 'all') {
    //search the department
    $conditions[] = "a.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($date_from !== '') {
    //search for the login time
    $conditions[] = "DATE(a.login_time) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to !== '') {
    //search for the logout time    
    $conditions[] = "DATE(a.login_time) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

//get the conditions from above
//checks if there is an active filter or condition, then pieces the active filter a user used
//return null if nothing was used
$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "
    SELECT
        a.id AS attendance_id,
        a.login_time,
        a.logout_time,
        a.status,
        u.full_name,
        d.name AS department_name
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    $where
    ORDER BY a.login_time DESC
";

$stmt = $connection->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$attendance_rows = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($attendance_rows);
exit();