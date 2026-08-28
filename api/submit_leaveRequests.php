<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/LeaveRequest.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/empDashboard.php');
    exit();
}

$userId = (int) $_SESSION['id'];
$role = $_SESSION['role'] ?? 'employee';
$type = $_POST['leave_type'] ?? '';
$start = $_POST['start_date'] ?? '';
$end = $_POST['end_date'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$allowed = ['vacation', 'sick', 'emergency', 'other', 'others'];

try {
    if (!in_array($type, $allowed, true) || !$start || !$end || $reason === '') {
        throw new InvalidArgumentException('Please fill out all required fields.');
    }
    if (strtotime($end) < strtotime($start)) {
        throw new InvalidArgumentException('End date cannot be before the start date.');
    }
    if (strtotime($start) < strtotime(date('Y-m-d'))) {
        throw new InvalidArgumentException('Start date cannot be in the past.');
    }

    $db = new Database();
    $leave = new LeaveRequest($db->getConnection());
    $leave->setUserId($userId)->setType($type)->setStartDate($start)->setEndDate($end)->setReason($reason)->setSubmittedByRole($role);
    $_SESSION['flash'] = $leave->create()
        ? ['type' => 'success', 'message' => 'Leave request submitted successfully.']
        : ['type' => 'error', 'message' => 'Something went wrong while submitting your request.'];
} catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
}

header('Location: ../pages/empDashboard.php');
exit();
