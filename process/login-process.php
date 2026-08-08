<?php

session_start();

require_once __DIR__ . '/../config/db.php';


// ==============================
// GET LOGIN DATA
// ==============================

$login = trim($_POST["email"] ?? "");
$password = trim($_POST["password"] ?? "");


// ==============================
// CHECK EMPTY FIELDS
// ==============================

if (empty($login) || empty($password)) {
    $_SESSION["error"] = "Please fill in both fields.";
    header("Location: ../auth/login.php");
    exit();
}


// ==============================
// FIND USER
// ==============================

$sql = "SELECT *
        FROM users
        WHERE username = ? OR email = ?
        LIMIT 1";

$stmt = $connection->prepare($sql);

$stmt->bind_param("ss", $login, $login);

$stmt->execute();

$result = $stmt->get_result();


// ==============================
// USER NOT FOUND
// ==============================

if ($result->num_rows === 0) {

    $_SESSION["error"] = "Invalid username or password.";

    header("Location: ../auth/login.php");
    exit();
}


$users = $result->fetch_assoc();


// ==============================
// CHECK PASSWORD
// ==============================

if (!password_verify($password, $users["password"])) {

    $_SESSION["error"] = "Invalid username or password.";

    header("Location: ../auth/login.php");
    exit();
}


// ==============================
// LOGIN SUCCESSFUL
// ==============================

session_regenerate_id(true);

$_SESSION["id"] = $users["id"];
$_SESSION["email"] = $users["email"];
$_SESSION["username"] = $users["username"];
$_SESSION["role"] = $users["role"];

$user_id = $users["id"];


// ==================================================
// STEP 1
// TIME OUT ANY OTHER ACCOUNT CURRENTLY ACTIVE
// ==================================================

$timeout_sql = "UPDATE attendance
                SET logout_time = NOW()
                WHERE DATE(login_time) = CURDATE()
                AND logout_time IS NULL
                AND user_id != ?";

$timeout_stmt = $connection->prepare($timeout_sql);

$timeout_stmt->bind_param(
    "i",
    $user_id
);

$timeout_stmt->execute();


// ==================================================
// STEP 2
// CHECK IF THIS USER ALREADY HAS ATTENDANCE TODAY
// ==================================================

$check_sql = "SELECT id
              FROM attendance
              WHERE user_id = ?
              AND DATE(login_time) = CURDATE()
              LIMIT 1";

$check_stmt = $connection->prepare($check_sql);

$check_stmt->bind_param(
    "i",
    $user_id
);

$check_stmt->execute();

$attendance_result = $check_stmt->get_result();


// ==================================================
// STEP 3
// DELETE THIS USER'S OLD ATTENDANCE
// ==================================================

if ($attendance_result->num_rows > 0) {

    $delete_sql = "DELETE FROM attendance
                   WHERE user_id = ?
                   AND DATE(login_time) = CURDATE()";

    $delete_stmt = $connection->prepare($delete_sql);

    $delete_stmt->bind_param(
        "i",
        $user_id
    );

    $delete_stmt->execute();
}


// ==================================================
// STEP 4
// CREATE NEW ATTENDANCE RECORD
// ==================================================

$attendance_sql = "INSERT INTO attendance
                   (user_id, login_time, logout_time, status)
                   VALUES (?, NOW(), NULL, 'present')";

$attendance_stmt = $connection->prepare($attendance_sql);

$attendance_stmt->bind_param(
    "i",
    $user_id
);

$attendance_stmt->execute();


// ==================================================
// STEP 5
// GO TO DASHBOARD
// ==================================================

header("Location: ../pages/dashboard.php");
exit();

?>