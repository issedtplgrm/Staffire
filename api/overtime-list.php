<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/OvertimeRequest.php';

$conditions = [];
$params = [];
$types = '';
$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? 'all');
$status = trim($_GET['status'] ?? 'all');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

if ($search !== '') { $conditions[] = 'u.full_name LIKE CONCAT("%", ?, "%")'; $params[] = $search; $types .= 's'; }
if ($department !== 'all' && $department !== '') { $conditions[] = 'd.name = ?'; $params[] = $department; $types .= 's'; }
if ($status !== 'all' && $status !== '') { $conditions[] = 'ot.status = ?'; $params[] = $status; $types .= 's'; }
if ($dateFrom !== '') { $conditions[] = 'ot.overtime_date >= ?'; $params[] = $dateFrom; $types .= 's'; }
if ($dateTo !== '') { $conditions[] = 'ot.overtime_date <= ?'; $params[] = $dateTo; $types .= 's'; }

try {
    $db = new Database();
    $overtime = new OvertimeRequest($db->getConnection());
    echo json_encode($overtime->all($conditions, $params, $types));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
