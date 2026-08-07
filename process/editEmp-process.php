<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id        = (int) ($_POST['id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? '');
$role      = $_POST['role'] ?? 'employee';
$department_id = ($_POST['department_id'] ?? '') !== '' ? (int) $_POST['department_id'] : null;

if (empty($full_name) || empty($username) || empty($email) || empty($role)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: manageEmployees.php");
    exit();
}

$stmt = $connection->prepare(
    "UPDATE users 
     SET full_name = ?, username = ?, email = ?, role = ?, department_id = ? 
     WHERE id = ?"
);

$stmt->bind_param("sssssi", $full_name, $username, $email, $role, $department_id, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Employee updated successfully.";
} else {
    $_SESSION['error'] = "Could not update employee: " . $stmt->error;
}

header("Location: manageEmployees.php");
exit();