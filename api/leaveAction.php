<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/LeaveRequest.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access. Please log in first.']);
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit();
}

try {
    $db = new Database();
    $leave = new LeaveRequest($db->getConnection());
    $request = $leave->find($id);

    if (!$request) {
        http_response_code(404);
        echo json_encode(['error' => 'Leave request not found.']);
        exit();
    }

    if ($_SESSION['role'] === 'manager' && $request['role'] === 'manager') {
        http_response_code(403);
        echo json_encode(['error' => 'Managers cannot approve or reject manager leave requests.']);
        exit();
    }

    $status = $action === 'approve' ? 'approved' : 'rejected';
    if (!$leave->setId($id)->setStatus($status)->setManagerId((int) $_SESSION['id'])->update()) {
        throw new RuntimeException('Could not update leave request.');
    }

    echo json_encode(['success' => true, 'status' => $status]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
