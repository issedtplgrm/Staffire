<?php

session_start();

include '../Staffire/config/db.php';

$login = trim($_POST["email"] ?? "");
$password = trim($_POST["password"] ?? "");

//check if empty
if (empty($login) || empty($password)) {
    $_SESSION["error"] = "Please enter your email and password.";
    header("Location: ../auth/login.php");
    exit();
}

//find user
$sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
$stmt = $connection->prepare($sql);
$stmt->bind_param("ss", $login, $login);
$stmt->execute();
$result = $stmt->get_result();

// If no account found
if ($result->num_rows === 0) {
    $_SESSION["error"] = "Invalid username or password.";
    header("Location: ../auth/login.php");
    exit();
}

$users = $result->fetch_assoc();


if ($password === $users["password"]) {
    // Login successful
    session_regenerate_id(true);

    $_SESSION["id"] = $users["id"];
    $_SESSION["email"] = $users["email"];
    $_SESSION["username"] = $users["username"];

    header("Location: ../pages/dashboard.php");
    exit();
} else {
   
    $_SESSION["error"] = "Invalid username or password.";
    header("Location: ../auth/login.php");
    exit();
}
