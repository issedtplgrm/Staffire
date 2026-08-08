<?php

session_start();

require_once __DIR__ . '/../config/db.php';

$login = trim($_POST["email"] ?? "");
$password = trim($_POST["password"] ?? "");

// Check if empty
if (empty($login) || empty($password)) {
    $_SESSION["error"] = "Please enter your email and password.";
    header("Location: ../auth/login.php");
    exit();
}

// Find user
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

// Check password
if ($password === $users["password"]) {

    // Login successful
    session_regenerate_id(true);

    $_SESSION["id"] = $users["id"];
    $_SESSION["email"] = $users["email"];
    $_SESSION["username"] = $users["username"];

    $user_id = $users["id"];

    // Check if this user already timed in TODAY
    $check = "SELECT id
              FROM attendance
              WHERE user_id = ?
              AND DATE(login_time) = CURDATE()
              LIMIT 1";


    

    $check_stmt = $connection->prepare($check);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();

    $attendance_result = $check_stmt->get_result();

    //reset attendance if signed in for the second time
    if($attendance_result->num_rows > 0){
    
    // $delete_attendance = "DELETE from attendance where user_id = ?";
    // $delete = $connection->

    $update_attendance = "UPDATE attendance SET login_time = NOW(), logout_time = NULL, status ='present'
                        WHERE user_id = ? AND DATE(login_time) = CURDATE()";
    
    $update = $connection->prepare($update_attendance);
    $update->bind_param("i", $user_id);
    $update->execute();


    }

    // If no time-in exists today, insert one
    else {
        $attendance = "INSERT INTO attendance
                       (user_id, login_time, status)
                       VALUES (?, NOW(), 'present')";

        $get_attendance = $connection->prepare($attendance);
        $get_attendance->bind_param("i", $user_id);
        $get_attendance->execute();
    }

    
    // Redirect to dashboard
    header("Location: ../pages/dashboard.php");
    exit();

} else {
    $_SESSION["error"] = "Invalid username or password.";
    header("Location: ../auth/login.php");
    exit();
}

?>