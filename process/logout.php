
<?php
session_start();
require_once __DIR__ . '/../config/db.php';


if (!isset($_SESSION["id"])) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $_SESSION["id"];


$attendance_sql = "
    SELECT id, login_time, status
    FROM attendance
    WHERE user_id = ?
      AND DATE(login_time) = CURDATE()
      AND logout_time IS NULL
    LIMIT 1
";
$attendance_stmt = $connection->prepare($attendance_sql);
$attendance_stmt->bind_param("i", $user_id);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

//check if exists
if ($attendance_result->num_rows > 0) {
    $attendance = $attendance_result->fetch_assoc();
    $attendance_id = $attendance["id"];
    $time_in       = $attendance["login_time"];
    $status        = $attendance["status"];

    //logout_time
    $logout_sql = "UPDATE attendance SET logout_time = NOW() WHERE id = ?";
    $logout_stmt = $connection->prepare($logout_sql);
    $logout_stmt->bind_param("i", $attendance_id);
    $logout_stmt->execute();

    //get new logout
    $time_out_sql = "SELECT logout_time FROM attendance WHERE id = ?";
    $time_out_stmt = $connection->prepare($time_out_sql);
    $time_out_stmt->bind_param("i", $attendance_id);
    $time_out_stmt->execute();
    $time_out_result = $time_out_stmt->get_result();
    $time_out_data   = $time_out_result->fetch_assoc();
    $time_out        = $time_out_data["logout_time"];

    //prototype save to perm attendance table
    // $record_sql = "
    //     INSERT INTO attendance (user_id, login_time, logout_time, status)
    //     VALUES (?, ?, ?, ?)
    // ";
    // $record_stmt = $connection->prepare($record_sql);
    // $record_stmt->bind_param("isss", $user_id, $login_time, $logout_time, $status);
    // $record_stmt->execute();
}



session_unset();
session_destroy();


header("Location: ../auth/login.php");
exit();
?>
