<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../config/db.php';

$login = trim($_POST["email"] ?? "");
$password = trim($_POST["password"] ?? "");



$sql = "SELECT *
        FROM users
        WHERE username = ? OR email = ?
        LIMIT 1";
$stmt = $connection->prepare($sql);
$stmt->bind_param("ss", $login, $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION["error"] = "Invalid username or password.";
    header("Location: ../auth/login.php");
    exit();
}

$users = $result->fetch_assoc();


if (!password_verify($password, $users["password"])) {
    $_SESSION["error"] = "Invalid username or password.";
    header("Location: ../auth/login.php");
    exit();
}

session_regenerate_id(true);
$_SESSION["id"] = $users["id"];
$_SESSION["email"] = $users["email"];
$_SESSION["username"] = $users["username"];
$_SESSION["role"] = $users["role"];

$user_id = $users["id"];


if ($users["role"] != "admin") {

    // Auto logout other active attendance records for today
    $timeout_sql = "UPDATE attendance
                    SET logout_time = NOW()
                    WHERE DATE(login_time) = CURDATE()
                    AND logout_time IS NULL
                    AND user_id != ?";
    $timeout_stmt = $connection->prepare($timeout_sql);
    $timeout_stmt->bind_param("i", $user_id);
    $timeout_stmt->execute();

    // Check if user already has attendance today
    $check_sql = "SELECT id
                  FROM attendance
                  WHERE user_id = ?
                  AND DATE(login_time) = CURDATE()
                  LIMIT 1";
    $check_stmt = $connection->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $attendance_result = $check_stmt->get_result();

    // Delete existing record if found
    if ($attendance_result->num_rows > 0) {
        $delete_sql = "DELETE FROM attendance
                       WHERE user_id = ?
                       AND DATE(login_time) = CURDATE()";
        $delete_stmt = $connection->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
    }

    // set shift start 9:00AM
    $login_time = new DateTime(); // actual login timestamp
    $shift_start = new DateTime($login_time->format("Y-m-d") . " 09:00:00"); // shift start
    $grace_period = clone $shift_start;
    $grace_period->modify("+15 minutes"); // considered late at 9:15 AM

    // If login time > 9:15 is late, else present
    $status = ($login_time > $grace_period) ? "late" : "present";

    //
    $attendance_sql = "INSERT INTO attendance
                       (user_id, login_time, logout_time, status)
                       VALUES (?, ?, NULL, ?)";
    $attendance_stmt = $connection->prepare($attendance_sql);
    $attendance_stmt->bind_param("iss", $user_id, $login_time->format("Y-m-d H:i:s"), $status);
    $attendance_stmt->execute();
}
if ($_SESSION["role"] === 'employee')
    header("Location: ../pages/empDashboard.php");
else

header("Location: ../pages/dashboard.php");
exit();
?>
