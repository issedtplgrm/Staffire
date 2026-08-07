<?php
    session_start();
    require_once __DIR__ . '/../config/db.php';

    if (!isset($_SESSION['id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '' !== '' ? (int)$_POST['department_id'] : null);

    if(empty($full_name) || empty($username) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: manageEmployees.php");
        exit();
    }   
    
    $stmt = $connection->prepare(
        "INSERT INTO users (full_name, username, email, password, role, department_id)
         VALUES (?, ?, ?, ?, ?, ?)"
         );
    $stmt->bind_param("sssssi", $full_name, $username, $email, password_hash($password, PASSWORD_DEFAULT), $role, $department_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Employee added successfully.";
    } else {
        $_SESSION['error'] = "Could not add employee: " . $stmt->error;
    }

    header("Location: manageEmployees.php");
    exit();
?>
