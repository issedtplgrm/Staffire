<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/OvertimeRequest.php';

$id = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$userRole = $_SESSION['role'] ?? null;
$userId = (int) ($_SESSION['id'] ?? 0);

if ($id <= 0 || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit();
}

try {
    $db = new Database();
    $overtime = new OvertimeRequest($db->getConnection());
    $request = $overtime->find($id);

    if (!$request) {
        echo json_encode(['success' => false, 'error' => 'Request not found']);
        exit();
    }

    if ($userRole === 'manager') {
        if ($request['submitted_by_role'] === 'manager') {
            echo json_encode(['success' => false, 'error' => 'Managers cannot approve/reject manager-submitted requests.']);
            exit();
        }
        if ((int) $request['user_id'] === $userId) {
            echo json_encode(['success' => false, 'error' => 'Managers cannot approve/reject their own requests.']);
            exit();
        }
    } elseif ($userRole === 'employee') {
        echo json_encode(['success' => false, 'error' => 'Employees cannot approve/reject requests.']);
        exit();
    }

    $status = $action === 'approve' ? 'approved' : 'rejected';
    echo json_encode(['success' => $overtime->setId($id)->setStatus($status)->update()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
