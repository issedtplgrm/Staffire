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


if ($_SESSION['role'] === 'manager') {

    $check_stmt = $connection->prepare(
        "SELECT users.id, users.role
         FROM leave_requests
         INNER JOIN users ON leave_requests.user_id = users.id
         WHERE leave_requests.id = ?"
    );

    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();

    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Leave request not found.']);
        exit();
    }

    $request_user = $check_result->fetch_assoc();

    // Managers cannot approve/reject manager requests
    if ($request_user['role'] === 'manager') {
        http_response_code(403);
        echo json_encode([
            'error' => 'Managers cannot approve or reject manager leave requests.'
        ]);
        exit();
    }
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

