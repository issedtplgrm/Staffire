<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/empDashboard.php");
    exit();
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role']; // 'employee', 'manager', or 'admin'
$request_type = $_POST['request_type'] ?? '';
$redirect     = ($user_role === 'manager') ? "../pages/request-leave.php" : "../pages/empDashboard.php";

//leave request
if ($request_type === 'leave') {
    $leave_type = $_POST['leave_type'] ?? '';
    $start_date = $_POST['start_date' ] ?? '';
    $end_date   = $_POST['end_date'] ?? '';
    $reason     = $_POST['reason'] ?? '';

    $allowed_leave_types = ['vacation', 'sick', 'emergency', 'others'];

    if (!in_array($leave_type, $allowed_leave_types, true) || !$start_date || !$end_date || $reason === '') {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please fill out all required fields.'];
        header("Location: $redirect"); exit();
    }
    if (strtotime($end_date) < strtotime($start_date)) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'End date cannot be before the start date.'];
        header("Location: $redirect"); exit();
    }
    if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Start date cannot be in the past.'];
        header("Location: $redirect"); exit();
    }

    $stmt = $connection->prepare("
        INSERT INTO leave_requests 
        (user_id, type, start_date, end_date, reason, status, submitted_by_role) 
        VALUES (?, ?, ?, ?, ?, 'pending', ?)
    ");
    $stmt->bind_param("isssss", $user_id, $leave_type, $start_date, $end_date, $reason, $user_role);
    
    $_SESSION['flash'] = $stmt->execute()
        ? ['type' => 'success', 'message' => 'Leave request submitted. Awaiting approval.']
        : ['type' => 'error',   'message' => 'Something went wrong. Please try again.'];
    
    $stmt->close();
}

//overtime request
if ($request_type === 'overtime') {
    $ot_date   = $_POST['overtime_date'] ?? '';
    $ot_start  = $_POST['overtime_start'] ?? '';
    $ot_end    = $_POST['overtime_end'] ?? '';
    $ot_hours  = $_POST['total_hours'] ?? '';       //can calculate server-side if needed
    $ot_type   = $_POST['overtime_type'] ?? '';
    $ot_reason = $_POST['overtime_reason'] ?? '';
    $ot_work   = $_POST['overtime_work'] ?? '';

    $allowed_ot_types = ['regular', 'emergency'];

    if (!$ot_date || !$ot_start || !$ot_end || !in_array($ot_type, $allowed_ot_types, true) || $ot_reason === '') {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please fill out all required overtime fields.'];
        header("Location: $redirect"); exit();
    }
    if (strtotime($ot_end) <= strtotime($ot_start)) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'End time must be after the start time.'];
        header("Location: $redirect"); exit();
    }
    if (strtotime($ot_date) < strtotime(date('Y-m-d'))) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Overtime date cannot be in the past.'];
        header("Location: $redirect"); exit();
    }

     $ot_hours = round((strtotime($ot_end) - strtotime($ot_start)) / 3600, 2);


    $stmt = $connection->prepare("
        INSERT INTO overtime_requests 
        (user_id, overtime_date, start_time, end_time, total_hours, overtime_type, reason, work, status, manager_id, submitted_by_role) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL, ?)
    ");

    $stmt->bind_param(
        "isssdssss",
        $user_id, $ot_date, $ot_start, $ot_end, $ot_hours, $ot_type, $ot_reason, $ot_work, $user_role
    );
     $_SESSION['flash'] = $stmt->execute()
        ? ['type' => 'success', 'message' => 'Overtime request submitted. Awaiting approval.']
        : ['type' => 'error',   'message' => 'Something went wrong. Please try again.'];
    
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
