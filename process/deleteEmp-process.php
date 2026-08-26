<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/User.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = 'Invalid employee id.';
    header('Location: ../pages/manageEmployees.php');
    exit();
}

try {
    $db = new Database();
    $user = new User($db->getConnection());
    $user->setId($id);
    $_SESSION['success'] = $user->delete() ? 'Employee deleted.' : 'Could not delete employee.';
} catch (Throwable $e) {
    $_SESSION['error'] = 'Could not delete employee: ' . $e->getMessage();
}

header('Location: ../pages/manageEmployees.php');
exit();
