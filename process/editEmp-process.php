<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/User.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'employee';
$departmentId = ($_POST['department_id'] ?? '') !== '' ? (int) $_POST['department_id'] : null;

if ($id <= 0 || $fullName === '' || $username === '' || $email === '' || $role === '') {
    $_SESSION['error'] = 'All fields are required.';
    header('Location: ../pages/manageEmployees.php');
    exit();
}

try {
    $db = new Database();
    $user = new User($db->getConnection());
    $user->setId($id)->setFullName($fullName)->setUsername($username)->setEmail($email)->setRole($role)->setDepartmentId($departmentId);
    $_SESSION['success'] = $user->update()
        ? 'Employee updated successfully.'
        : 'Could not update employee.';
} catch (Throwable $e) {
    $_SESSION['error'] = 'Could not update employee: ' . $e->getMessage();
}

header('Location: ../pages/manageEmployees.php');
exit();
