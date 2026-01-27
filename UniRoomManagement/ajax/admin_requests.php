<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/auth_functions.php';
require_once '../database/db.php';

// Demo mode - removed authentication requirement
// AuthHelper::requireRole('admin', 'loginstudent.php');
header('Content-Type: application/json');

// Priority map: 1=Student, 2=Club, 3=Faculty
$priorityMap = ['student' => 1, 'club' => 2, 'faculty' => 3];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conn = getDBConnection();
    
    // Get pending bookings
    $stmt = $conn->prepare(
        "SELECT b.*, r.room_number, r.room_name, r.capacity,
                s.slot_name, s.start_time, s.end_time,
                u.username, u.email, u.first_name, u.role
         FROM bookings b
         JOIN rooms r ON b.room_id = r.id
         JOIN time_slots s ON b.slot_id = s.id
         JOIN users u ON b.user_id = u.id
         WHERE b.status = 'pending'
         ORDER BY b.created_at DESC"
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    $stmt->close();
    closeDBConnection($conn);
    
    echo json_encode(['success' => true, 'requests' => $requests]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if (!$bookingId || !in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    $conn = getDBConnection();
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    // Get booking details
    $bookingStmt = $conn->prepare(
        "SELECT b.*, u.role FROM bookings b 
         JOIN users u ON b.user_id = u.id 
         WHERE b.id = ?"
    );
    $bookingStmt->bind_param('i', $bookingId);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    $booking = $bookingResult->fetch_assoc();
    $bookingStmt->close();
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        closeDBConnection($conn);
        exit;
    }
    
    // If approving, check for lower priority conflicting bookings
    if ($action === 'approve') {
        $currentUserPriority = $priorityMap[$booking['role']] ?? 1;
        
        // Find ALL bookings (pending or approved) with same room, slot, date
        $conflictStmt = $conn->prepare(
            "SELECT b.id, b.user_id, b.status, u.role FROM bookings b
             JOIN users u ON b.user_id = u.id
             WHERE b.room_id = ? AND b.slot_id = ? AND b.booking_date = ?
             AND b.id != ? AND b.status IN ('pending', 'approved')"
        );
        $conflictStmt->bind_param('iisi', $booking['room_id'], $booking['slot_id'], $booking['booking_date'], $bookingId);
        $conflictStmt->execute();
        $conflictResult = $conflictStmt->get_result();
        
        // Check for conflicts and handle them
        while ($conflict = $conflictResult->fetch_assoc()) {
            $conflictPriority = $priorityMap[$conflict['role']] ?? 1;
            
            // If there's a same or higher priority booking, reject this approval
            if ($conflictPriority >= $currentUserPriority) {
                $conflictStmt->close();
                closeDBConnection($conn);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot approve: Room already has a booking with same or higher priority'
                ]);
                exit;
            }
            
            // If lower priority, cancel/reject it
            if ($currentUserPriority > $conflictPriority) {
                $newStatus = ($conflict['status'] === 'approved') ? 'cancelled' : 'rejected';
                
                $cancelStmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                $cancelStmt->bind_param('si', $newStatus, $conflict['id']);
                $cancelStmt->execute();
                $cancelStmt->close();
                
                // Create notification for affected user
                $notifMsg = "Your booking was " . $newStatus . " because a higher priority user (faculty/club) booked the same room and time slot.";
                $notifTitle = "Booking " . ucfirst($newStatus);
                $notifType = ($newStatus === 'cancelled') ? 'booking_overridden' : 'booking_rejected';
                $conflict_user_id = $conflict['user_id'];
                $conflict_booking_id = $conflict['id'];
                
                $notifStmt = $conn->prepare(
                    "INSERT INTO notifications (user_id, booking_id, type, title, message) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $notifStmt->bind_param('iisss', $conflict_user_id, $conflict_booking_id, $notifType, $notifTitle, $notifMsg);
                $ok_notif = $notifStmt->execute();
                $notifStmt->close();
                
                if (!$ok_notif) {
                    error_log("Failed to create notification for user {$conflict_user_id}");
                }
            }
        }
        $conflictStmt->close();
    }
    
    // Update booking status
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $bookingId);
    $ok = $stmt->execute();
    $stmt->close();
    
    closeDBConnection($conn);
    
    echo json_encode([
        'success' => $ok,
        'message' => $ok ? "Booking {$status}" : 'Failed to update booking'
    ]);
}
