<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int) ($_GET['id'] ?? 0);

if ($id === 0) {
    $_SESSION['error'] = "Invalid employee id.";
    header("Location: ../pages/manageEmployees.php");
    exit();
}

$stmt = $connection->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Employee deleted.";
} else {
    $_SESSION['error'] = "Could not delete employee: " . $connection->error;
}

header("Location: ../pages/manageEmployees.php");
exit();