<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/auth_functions.php';
require_once '../database/db.php';

// Demo mode - removed authentication requirement
// AuthHelper::requireRole('admin', 'loginstudent.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn = getDBConnection();
    
    // Get all rooms
    $stmt = $conn->prepare(
        "SELECT id, room_number, room_name, capacity, building, floor, is_available, created_at
         FROM rooms
         ORDER BY room_number"
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    $stmt->close();
    closeDBConnection($conn);
    
    echo json_encode(['success' => true, 'rooms' => $rooms]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_room') {
        $room_number = trim($_POST['room_number'] ?? '');
        $room_name = trim($_POST['room_name'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        $building = trim($_POST['building'] ?? '');
        $floor = trim($_POST['floor'] ?? ''); // Keep as string since DB column is varchar
        
        if (!$room_number || !$room_name || $capacity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Missing or invalid fields']);
            exit;
        }
        
        $conn = getDBConnection();
        
        // Check if room_number already exists
        $checkStmt = $conn->prepare("SELECT id FROM rooms WHERE room_number = ?");
        $checkStmt->bind_param('s', $room_number);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            closeDBConnection($conn);
            echo json_encode(['success' => false, 'message' => 'Room number already exists']);
            exit;
        }
        $checkStmt->close();
        
        $stmt = $conn->prepare(
            "INSERT INTO rooms (room_number, room_name, capacity, building, floor, is_available)
             VALUES (?, ?, ?, ?, ?, 1)"
        );
        $stmt->bind_param('ssiss', $room_number, $room_name, $capacity, $building, $floor);
        $ok = $stmt->execute();
        
        if (!$ok) {
            $error = $stmt->error;
            $stmt->close();
            closeDBConnection($conn);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $error]);
            exit;
        }
        
        $stmt->close();
        closeDBConnection($conn);
        
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Room added successfully' : 'Failed to add room'
        ]);
        
    } elseif ($action === 'delete_room') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        
        if (!$room_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
            exit;
        }
        
        $conn = getDBConnection();
        
        // Delete room
        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->bind_param('i', $room_id);
        $ok = $stmt->execute();
        $stmt->close();
        
        closeDBConnection($conn);
        
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Room deleted successfully' : 'Failed to delete room'
        ]);
        
    } elseif ($action === 'toggle_available') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        
        if (!$room_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
            exit;
        }
        
        $conn = getDBConnection();
        
        // Get current status
        $stmt = $conn->prepare("SELECT is_available FROM rooms WHERE id = ?");
        $stmt->bind_param('i', $room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Room not found']);
            exit;
        }
        
        $newStatus = $row['is_available'] ? 0 : 1;
        
        // Update status
        $stmt = $conn->prepare("UPDATE rooms SET is_available = ? WHERE id = ?");
        $stmt->bind_param('ii', $newStatus, $room_id);
        $ok = $stmt->execute();
        $stmt->close();
        
        closeDBConnection($conn);
        
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Room status updated' : 'Failed to update room'
        ]);
    }
}
