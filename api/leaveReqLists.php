<?php
    session_start();

    header("Content-Type: application/json");
    require_once __DIR__ . '/../config/db.php';

    //check if a user is logged in
    if (!isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

// get the filter from the page and sanitize it
// empty string if no filter is provided

//employee search filter
$search = trim($_GET['search'] ?? '');
$department = trim($GET['department'] ?? '');
$status = trim($_GET['status'] ?? '');
$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');

// containers
$conditions = []; //WHERE...
$params = []; //a varaible that will placed in '?'
$types = '';

//check if the search filter is not empty and add it to the conditions
//then convrts into a prepared statement for query 
if ($search !== '') {
    //condition = search a user with that name 
    $conditions[] = "u.full_name LIKE CONCAT('%', ?, '%')";
    $params[] = $search;
    $types .= 's';
}

if ($department !== '' && $department !== 'all'){
    //will search for that department
    $conditions = "d.name = ?";
    $params[] = $department;
    $types .= 's';
}

if ($status !== '' && $status !== 'all'){
    //will searchf for the status
    $conditons = "lr.status = ?";
    $params = $status;
    $types .= 's';
}

if($start_date !== ''){
    //will search for the start date of leave
    $conditons = "lr.start_date >= ?";
    $params[] = $start_date;
    $types .= 's';
}

if($end_date !== ''){
    //will search for the end date of leave
    $conditons = "lr.end_date <= ?";
    $params[] = $end_date;
    $types .= 's';
}

//get the conditions from above
//checks if there is an active filter or coundtion, then pieces the active filter a user used
//return null if nothing was used
$where = $conditions ? "WHERE " . implode(" AND", $conditions): "";


$sql = "
    SELECT
        lr.id AS leave_request_id,
        lr.user_id,
        lr.start_date,
        lr.end_date,
        DATEDIFF(lr.end_date, lr.start_date) + 1 AS duration,
        lr.type AS leave_type,
        lr.reason,
        lr.status,
        lr.created_at,
        u.full_name,
        d.name AS department_name
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    $where
    ORDER BY lr.created_at DESC
";

$stmt = $connection->prepare($sql);

if ($params){
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$leave_requests = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($leave_requests);
exit();