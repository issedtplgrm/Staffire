<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Attendance.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/login.php');
    exit();
}

try {
    $db = new Database();
    $attendance = new Attendance($db->getConnection());
    $record = $attendance->activeToday((int) $_SESSION['id']);

    if ($record) {
        $attendance->setId((int) $record['id']);
        $attendance->logout();
    }
} finally {
    session_unset();
    session_destroy();
}

header('Location: ../auth/login.php');
exit();
