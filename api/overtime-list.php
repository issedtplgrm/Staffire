<?php

require_once __DIR__ . '/../config/db.php';

$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$overtime_sql = "
    SELECT
        ot.id AS overtime_request_id,
        ot.user_id,
        ot.overtime_date,
        ot.start_time,
        ot.end_time,
        ot.total_hours,
        ot.overtime_type,
        ot.reason,
        ot.work,
        ot.status,
        ot.created_at,
        ot.submitted_by_role,
        u.full_name,
        u.email,
        d.name AS department_name
    FROM overtime_requests ot
    JOIN users u ON ot.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE 1=1
";

if ($search !== '') {
    $overtime_sql .= " AND u.full_name LIKE '%" . $connection->real_escape_string($search) . "%'";
}
if ($department !== 'all') {
    $overtime_sql .= " AND d.name = '" . $connection->real_escape_string($department) . "'";
}
if ($status !== 'all') {
    $overtime_sql .= " AND ot.status = '" . $connection->real_escape_string($status) . "'";
}
if ($date_from !== '') {
    $overtime_sql .= " AND ot.overtime_date >= '" . $connection->real_escape_string($date_from) . "'";
}
if ($date_to !== '') {
    $overtime_sql .= " AND ot.overtime_date <= '" . $connection->real_escape_string($date_to) . "'";
}

$result = $connection->query($overtime_sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

header("Content-Type: application/json");
echo json_encode($rows);
