<?php
require_once "../config/db.php";

session_start();
$user_role = $_SESSION['role'] ?? null;   // e.g. 'manager', 'admin', 'employee'
$user_id   = $_SESSION['user_id'] ?? null;

$id     = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$id || !$action) {
    echo json_encode(["success" => false, "error" => "Missing parameters"]);
    exit;
}

// Fetch the request to check who submitted it
$stmt = $connection->prepare("SELECT submitted_by_role, user_id FROM overtime_requests WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    echo json_encode(["success" => false, "error" => "Request not found"]);
    exit;
}

// Role validation
if ($user_role === 'manager') {
    if ($request['submitted_by_role'] === 'manager') {
        echo json_encode(["success" => false, "error" => "Managers cannot approve/reject manager-submitted requests."]);
        exit;
    }
    if ($request['user_id'] == $user_id) {
        echo json_encode(["success" => false, "error" => "Managers cannot approve/reject their own requests."]);
        exit;
    }
} elseif ($user_role === 'employee') {
    echo json_encode(["success" => false, "error" => "Employees cannot approve/reject requests."]);
    exit;
}

// Map action to status
$status = ($action === "approve") ? "approved" : "rejected";

// Update the request
$update = $connection->prepare("UPDATE overtime_requests SET status = ? WHERE id = ?");
$update->bind_param("si", $status, $id);

if ($update->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $connection->error]);
}
