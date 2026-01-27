<?php
session_start();
require_once '../includes/auth_functions.php';
require_once '../database/db.php';

// Demo mode - removed authentication requirement
// AuthHelper::requireRole('admin', 'loginstudent.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn = getDBConnection();
    
    // Get unavailable rooms (is_available = 0)
    $blockedStmt = $conn->prepare(
        "SELECT id, room_number, room_name, capacity, building, floor, created_at
         FROM rooms
         WHERE is_available = 0
         ORDER BY room_number"
    );
    $blockedStmt->execute();
    $blocked = $blockedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $blockedStmt->close();
    
    // Get approved bookings
    $approvedStmt = $conn->prepare(
        "SELECT b.*, r.room_number, r.room_name, u.username, s.slot_name
         FROM bookings b
         JOIN rooms r ON b.room_id = r.id
         JOIN users u ON b.user_id = u.id
         JOIN time_slots s ON b.slot_id = s.id
         WHERE b.status = 'approved'
         ORDER BY b.booking_date DESC
         LIMIT 50"
    );
    $approvedStmt->execute();
    $approved = $approvedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $approvedStmt->close();
    
    // Get denied/rejected bookings
    $rejectedStmt = $conn->prepare(
        "SELECT b.*, r.room_number, r.room_name, u.username, s.slot_name
         FROM bookings b
         JOIN rooms r ON b.room_id = r.id
         JOIN users u ON b.user_id = u.id
         JOIN time_slots s ON b.slot_id = s.id
         WHERE b.status IN ('rejected', 'cancelled')
         ORDER BY b.created_at DESC
         LIMIT 50"
    );
    $rejectedStmt->execute();
    $rejected = $rejectedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $rejectedStmt->close();
    
    closeDBConnection($conn);
    
    echo json_encode([
        'success' => true,
        'blocked' => $blocked,
        'approved' => $approved,
        'rejected' => $rejected
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'block_room') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $userId = $_SESSION['user_id'] ?? 2; // Default to admin (user 2) for demo
        
        if (!$roomId) {
            echo json_encode(['success' => false, 'message' => 'Invalid room']);
            exit;
        }
        
        $conn = getDBConnection();
        $blockedDate = date('Y-m-d');
        
        $stmt = $conn->prepare(
            "INSERT INTO blocked_rooms (room_id, blocked_date, reason, blocked_by)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('issi', $roomId, $blockedDate, $reason, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        closeDBConnection($conn);
        
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Room blocked successfully' : 'Failed to block room'
        ]);
    }
}
