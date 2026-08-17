<?php
session_start();

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/empDashboard.php");
    exit;
}

$user_id     = $_SESSION['user_id'];
$leave_type  = $_POST['leave_type'] ?? '';
$start_date  = $_POST['start_date'] ?? '';
$end_date    = $_POST['end_date'] ?? '';
$reason      = trim($_POST['reason'] ?? '');

// ----- Basic validation -----
$allowed_types = ['vacation', 'sick', 'emergency', 'others'];

if (!in_array($leave_type, $allowed_types, true) || !$start_date || !$end_date || $reason === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please fill out all required fields.'];
    header("Location: ../pages/empDashboard.php");
    exit;
}

if (strtotime($end_date) < strtotime($start_date)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'End date cannot be before the start date.'];
    header("Location: ../pages/empDashboard.php");
    exit;
}

if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Start date cannot be in the past.'];
    header("Location: ../pages/empDashboard.php");
    exit;
}

// ----- Insert the leave request -----
$stmt = $connection->prepare("
    INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
");
$stmt->bind_param("isssss", $user_id, $leave_type, $start_date, $end_date, $reason);

if ($stmt->execute()) {
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Leave request submitted successfully.'];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Something went wrong while submitting your request. Please try again.'];
}

$stmt->close();
header("Location: ../pages/empDashboard.php");
exit;