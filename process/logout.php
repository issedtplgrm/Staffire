<?php

session_start();

include '../config/db.php';

// Check if someone is logged in
if (!isset($_SESSION["id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION["id"];

// Delete employee's record for today
$delete_sql = "DELETE FROM attendance
               WHERE user_id = ?
               AND DATE(login_time) = CURDATE()";

$delete_stmt = $connection->prepare($delete_sql);
$delete_stmt->bind_param("i", $user_id);
$delete_stmt->execute();

// Check whether the attendance table is empty
$count_sql = "SELECT COUNT(*) AS total FROM attendance";

$result = $connection->query($count_sql);
$count_result = $result->fetch_assoc();

if ($count_result["total"] == 0) {
    $connection->query("ALTER TABLE attendance AUTO_INCREMENT = 1");
}

// Save current timestamp
$logout = "UPDATE attendance
           SET logout_time = NOW()
           WHERE user_id = ?
           AND DATE(login_time) = CURDATE()
           AND logout_time IS NULL";

$logout_stmt = $connection->prepare($logout);
$logout_stmt->bind_param("i", $user_id);
$logout_stmt->execute();

session_unset();
session_destroy();

header("Location: ../auth/login.php");
exit();

?>