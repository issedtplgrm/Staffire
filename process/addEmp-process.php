<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/User.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = trim($_POST['role'] ?? '');
$departmentId = ($_POST['department_id'] ?? '') !== '' ? (int) $_POST['department_id'] : null;

if ($fullName === '' || $username === '' || $email === '' || $password === '' || $role === '') {
    $_SESSION['error'] = 'All fields are required.';
    header('Location: ../pages/manageEmployees.php');
    exit();
}

try {
    $db = new Database();
    $user = new User($db->getConnection());
    $user->setFullName($fullName)->setUsername($username)->setEmail($email)->setPassword($password)->setRole($role)->setDepartmentId($departmentId);
    $_SESSION['success'] = $user->create()
        ? 'Employee added successfully.'
        : 'Could not add employee.';
} catch (Throwable $e) {
    $_SESSION['error'] = 'Could not add employee: ' . $e->getMessage();
}

header('Location: ../pages/manageEmployees.php');
exit();
