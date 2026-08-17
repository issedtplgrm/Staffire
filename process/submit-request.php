<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role']; // 'employee', 'manager', or 'admin'
$request_type = $_POST['request_type'] ?? '';

//leave request
if ($request_type === 'leave') {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];
    $reason     = $_POST['reason'];

    $stmt = $connection->prepare("
        INSERT INTO leave_requests 
        (user_id, type, start_date, end_date, reason, status, submitted_by_role) 
        VALUES (?, ?, ?, ?, ?, 'pending', ?)
    ");
    $stmt->bind_param("isssss", $user_id, $leave_type, $start_date, $end_date, $reason, $user_role);

    if (!$stmt->execute()) {
        die("Leave insert failed: " . $stmt->error);
    }
    $stmt->close();
}

//overtime request
if ($request_type === 'overtime') {
    $ot_date   = $_POST['overtime_date'];
    $ot_start  = $_POST['overtime_start'];
    $ot_end    = $_POST['overtime_end'];
    $ot_hours  = $_POST['total_hours'];       //can calculate server-side if needed
    $ot_type   = $_POST['overtime_type'];
    $ot_reason = $_POST['overtime_reason'];
    $ot_work   = $_POST['overtime_work'];

    $stmt = $connection->prepare("
        INSERT INTO overtime_requests 
        (user_id, overtime_date, start_time, end_time, total_hours, overtime_type, reason, work, status, manager_id, submitted_by_role) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL, ?)
    ");
    $stmt->bind_param(
        "isssdssss",
        $user_id, $ot_date, $ot_start, $ot_end, $ot_hours, $ot_type, $ot_reason, $ot_work, $user_role
    );

    if (!$stmt->execute()) {
        die("Overtime insert failed: " . $stmt->error);
    }
    $stmt->close();
}


if ($user_role === 'employee') {
    header("Location: ../pages/empDashboard.php");
//if admin/manager
} elseif ($user_role === 'manager') {
    header("Location: ../pages/dashboard.php");
}
exit();
?>
