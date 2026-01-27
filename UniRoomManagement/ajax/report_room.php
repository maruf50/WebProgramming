<?php
session_start();
require_once '../includes/auth_functions.php';
require_once '../database/db.php';

// Demo mode - removed authentication requirement
// AuthHelper::requireLogin('loginstudent.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$userId = $_SESSION['user_id'] ?? 1; // Default to user 1 for demo
$roomId = (int)($_POST['room_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$severity = $_POST['severity'] ?? 'low';

if (!$roomId || !$title || !$description) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$severity = in_array($severity, ['low','medium','high'], true) ? $severity : 'low';

$conn = getDBConnection();
$stmt = $conn->prepare("INSERT INTO room_reports (user_id, room_id, title, description, severity) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('iisss', $userId, $roomId, $title, $description, $severity);
$ok = $stmt->execute();
$stmt->close();
closeDBConnection($conn);

echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Report submitted' : 'Failed to submit report'
]);
