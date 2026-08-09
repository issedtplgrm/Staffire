<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

//check if user is logged in
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access. Please log in first.']);
    exit();
}

$id     = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';


if ($id === 0 || !in_array($action, ['approve', 'reject'], true)) {
    http_response_code(400); // 400 = "Bad Request"
    echo json_encode(['error' => 'Invalid request.']);
    exit();
}

$status = ($action === 'approve') ? 'approved' : 'rejected';
$manager_id = $_SESSION['id']; // whoever's logged in and clicked the button

$stmt = $connection->prepare(
    "UPDATE leave_requests SET status = ?, manager_id = ? WHERE id = ?"
);
$stmt->bind_param("sii", $status, $manager_id, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'status' => $status]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Could not update leave request.']);
}
exit();

?>