<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Attendance.php';

$login = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

try {
    $db = new Database();
    $userRepo = new User($db->getConnection());
    $attendance = new Attendance($db->getConnection());
    $user = $userRepo->findByLogin($login);

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['error'] = 'Invalid username or password.';
        header('Location: ../auth/login.php');
        exit();
    }

    session_regenerate_id(true);
    $_SESSION['id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    if ($user['role'] !== 'admin') {
        $attendance->timeoutOthers((int) $user['id']);
        $attendance->deleteToday((int) $user['id']);

        $loginTime = new DateTime();
        $gracePeriod = new DateTime($loginTime->format('Y-m-d') . ' 09:15:00');
        $status = $loginTime > $gracePeriod ? 'late' : 'present';
        $attendance->setUserId((int) $user['id'])->setLoginTime($loginTime->format('Y-m-d H:i:s'))->setStatus($status);
        $attendance->create();
    }

    header($user['role'] === 'employee' ? 'Location: ../pages/empDashboard.php' : 'Location: ../pages/dashboard.php');
    exit();
} catch (Throwable $e) {
    $_SESSION['error'] = 'Unable to process login.';
    header('Location: ../auth/login.php');
    exit();
}
