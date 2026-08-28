<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/LeaveRequest.php';
require_once __DIR__ . '/../classes/OvertimeRequest.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/empDashboard.php');
    exit();
}

$userId = (int) $_SESSION['id'];
$userRole = $_SESSION['role'];
$requestType = $_POST['request_type'] ?? '';
$redirect = $userRole === 'manager' ? '../pages/request-leave.php' : '../pages/empDashboard.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    $leave = new LeaveRequest($connection);
    $overtime = new OvertimeRequest($connection);

    if ($requestType === 'leave') {
        $leaveType = $_POST['leave_type'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        $allowed = ['vacation', 'sick', 'emergency', 'other', 'others'];

        if (!in_array($leaveType, $allowed, true) || !$startDate || !$endDate || $reason === '') {
            throw new InvalidArgumentException('Please fill out all required fields.');
        }
        if (strtotime($endDate) < strtotime($startDate)) {
            throw new InvalidArgumentException('End date cannot be before the start date.');
        }
        if (strtotime($startDate) < strtotime(date('Y-m-d'))) {
            throw new InvalidArgumentException('Start date cannot be in the past.');
        }

        $leave->setUserId($userId)->setType($leaveType)->setStartDate($startDate)->setEndDate($endDate)->setReason($reason)->setSubmittedByRole($userRole);
        $_SESSION['flash'] = $leave->create()
            ? ['type' => 'success', 'message' => 'Leave request submitted. Awaiting approval.']
            : ['type' => 'error', 'message' => 'Something went wrong. Please try again.'];
    } elseif ($requestType === 'overtime') {
        $date = $_POST['overtime_date'] ?? '';
        $start = $_POST['overtime_start'] ?? '';
        $end = $_POST['overtime_end'] ?? '';
        $type = $_POST['overtime_type'] ?? '';
        $reason = trim($_POST['overtime_reason'] ?? '');
        $work = trim($_POST['overtime_work'] ?? '');
        $allowed = ['regular', 'emergency'];

        if (!$date || !$start || !$end || !in_array($type, $allowed, true) || $reason === '') {
            throw new InvalidArgumentException('Please fill out all required overtime fields.');
        }
        if (strtotime($end) <= strtotime($start)) {
            throw new InvalidArgumentException('End time must be after the start time.');
        }
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            throw new InvalidArgumentException('Overtime date cannot be in the past.');
        }

        $hours = round((strtotime($end) - strtotime($start)) / 3600, 2);
        $overtime->setUserId($userId)->setDate($date)->setStart($start)->setEnd($end)->setHours($hours)->setType($type)->setReason($reason)->setWork($work ?: null)->setSubmittedByRole($userRole);
        $_SESSION['flash'] = $overtime->create()
            ? ['type' => 'success', 'message' => 'Overtime request submitted. Awaiting approval.']
            : ['type' => 'error', 'message' => 'Something went wrong. Please try again.'];
    }
} catch (InvalidArgumentException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
} catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Something went wrong. Please try again.'];
}

header("Location: $redirect");
exit();
