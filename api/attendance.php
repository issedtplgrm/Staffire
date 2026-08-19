<?php
//ATTENDANCE IN THE ADMIN DASHBOARD
session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in first.']);
    exit();
}

$filter = $_GET['filter'] ?? 'all';
$where = "WHERE users.role != 'admin'";

if ($filter === 'present') {
    $where .= "AND attendance.status != 'absent' AND leave_requests.status IS NULL";
} elseif ($filter === 'absent') {
    $where .= " AND attendance.status  IS NULL";
}
 elseif ($filter === 'late'){
    $where .= "AND TIME(attendance.login_time) > '09:15:00' AND leave_requests.status IS NULL ";
}

// same in dashboard
$sql = "
    SELECT
        users.full_name,
        departments.name AS department,
        attendance.login_time,
        attendance.logout_time,
        leave_requests.status as leave_status,
        CASE
            WHEN leave_requests.user_id IS NOT NULL THEN 'on leave'
            WHEN attendance.id IS NULL THEN 'absent'
            WHEN TIME(attendance.login_time) > '09:15:00' THEN 'late'
            ELSE 'present'
        END AS status
    FROM users
    LEFT JOIN attendance
        ON attendance.user_id = users.id
        AND DATE(attendance.login_time) = CURDATE()
    LEFT JOIN departments
        ON users.department_id = departments.id
    LEFT JOIN leave_requests
        ON leave_requests.user_id = users.id   
        AND CURDATE() BETWEEN leave_requests.start_date AND leave_requests.end_date   
    $where
    ORDER BY attendance.login_time DESC, users.full_name ASC
";

$result = $connection->query($sql);
$attendance_rows = $result->fetch_all(MYSQLI_ASSOC);
 
echo json_encode($attendance_rows);
exit();
?>