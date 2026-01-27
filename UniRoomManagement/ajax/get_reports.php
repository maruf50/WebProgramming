<?php
session_start();
require_once '../includes/auth_functions.php';
require_once '../database/db.php';

// Demo mode - removed authentication requirement
// AuthHelper::requireLogin('loginstudent.php');
$userRole = $_SESSION['role'] ?? 'admin'; // Default to admin for demo
$userId = $_SESSION['user_id'] ?? 1;

header('Content-Type: application/json');

$conn = getDBConnection();

if ($userRole === 'admin') {
    $stmt = $conn->prepare(
        "SELECT rr.*, u.username, r.room_number
         FROM room_reports rr
         JOIN users u ON rr.user_id = u.id
         JOIN rooms r ON rr.room_id = r.id
         ORDER BY rr.created_at DESC"
    );
} else {
    $stmt = $conn->prepare(
        "SELECT rr.*, r.room_number
         FROM room_reports rr
         JOIN rooms r ON rr.room_id = r.id
         WHERE rr.user_id = ?
         ORDER BY rr.created_at DESC"
    );
    $stmt->bind_param('i', $userId);
}

$stmt->execute();
$result = $stmt->get_result();
$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();
closeDBConnection($conn);

echo json_encode(['success' => true, 'reports' => $reports]);
