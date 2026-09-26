<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Attendance.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access. Please log in first.']);
    exit();
}

$conditions = [];
$params = [];
$types = '';

$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$status = trim($_GET['status'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

if ($search !== '') { $conditions[] = 'u.full_name LIKE CONCAT("%", ?, "%")'; $params[] = $search; $types .= 's'; }
if ($department !== '' && $department !== 'all') { $conditions[] = 'd.name = ?'; $params[] = $department; $types .= 's'; }
if ($status !== '' && $status !== 'all') { $conditions[] = 'a.status = ?'; $params[] = $status; $types .= 's'; }
if ($dateFrom !== '') { $conditions[] = 'DATE(a.login_time) >= ?'; $params[] = $dateFrom; $types .= 's'; }
if ($dateTo !== '') { $conditions[] = 'DATE(a.login_time) <= ?'; $params[] = $dateTo; $types .= 's'; }

try {
    $db = new Database();
    $attendance = new Attendance($db->getConnection());
    echo json_encode($attendance->all($conditions, $params, $types));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
