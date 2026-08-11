<?php
//RECENT TIME INS IN LOGIN PAGE

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';


$filter =  $_GET['filter'] ?? 'present';
$where = '';

//get only the present piopl
if ($filter === 'present') {
    $where = "WHERE attendance.status IS NOT NULL AND attendance.status != 'absent'";
} 

// get the recent 10 employees
$sql = "
    SELECT
        users.full_name,
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
    $where
    ORDER BY attendance.login_time DESC, users.full_name ASC
    LIMIT 10
";

$result = $connection->query($sql);
$attendance_rows = $result->fetch_all(MYSQLI_ASSOC);
 
echo json_encode($attendance_rows);
exit();
?>