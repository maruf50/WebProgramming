<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/auth_functions.php';
require_once '../database/db.php';

AuthHelper::requireLogin('loginstudent.php');
header('Content-Type: application/json');

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn = getDBConnection();
    
    // Get unread notifications
    $stmt = $conn->prepare(
        "SELECT n.id, n.user_id, n.booking_id, n.type, n.title, n.message, n.is_read, n.created_at,
                b.booking_date, r.room_name, r.room_number
         FROM notifications n
         LEFT JOIN bookings b ON n.booking_id = b.id
         LEFT JOIN rooms r ON b.room_id = r.id
         WHERE n.user_id = ? AND n.is_read = 0
         ORDER BY n.created_at DESC
         LIMIT 20"
    );
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'notifications' => [], 'count' => 0, 'error' => $conn->error]);
        exit;
    }
    
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    closeDBConnection($conn);
    
    echo json_encode(['success' => true, 'notifications' => $notifications, 'count' => count($notifications)]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $notificationId = (int)($_POST['notification_id'] ?? 0);
    
    if (!$notificationId && $action !== 'mark_all_read') {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
        exit;
    }
    
    $conn = getDBConnection();
    
    if ($action === 'mark_read') {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $notificationId, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Marked as read' : 'Failed to mark as read']);
    } else if ($action === 'mark_all_read') {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => $ok, 'message' => $ok ? 'All marked as read' : 'Failed']);
    }
    
    closeDBConnection($conn);
}
