<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in first.']);
    exit();
}

$filter = $_GET['filter'] ?? 'all';
$where = '';

if ($filter === 'present') {
    $where = "WHERE attendance.status  IS NOT NULL AND attendance.status != 'absent'";
} elseif ($filter === 'absent') {
    $where = "WHERE attendance.status  IS NULL OR attendance.status != 'absent'";
}

// same in dashboard
$sql = "
    SELECT
        users.full_name,
        departments.name AS department,
        attendance.login_time,
        attendance.logout_time,
        CASE
            WHEN attendance.id IS NULL THEN 'absent'
            ELSE attendance.status
        END AS status
    FROM users
    LEFT JOIN attendance
        ON attendance.user_id = users.id
        AND DATE(attendance.login_time) = CURDATE()
    LEFT JOIN departments
        ON users.department_id = departments.id
    $where
    ORDER BY attendance.login_time DESC, users.full_name ASC
";

$result = $connection->query($sql);
$attendance_rows = $result->fetch_all(MYSQLI_ASSOC);
 
echo json_encode($attendance_rows);
exit();
?>