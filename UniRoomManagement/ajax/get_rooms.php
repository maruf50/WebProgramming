<?php
require_once '../includes/room_functions.php';
require_once '../includes/auth_functions.php';
require_once '../database/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $slotId = $_POST['slot_id'] ?? 0;
    $userRole = $_SESSION['role'] ?? 'student';
    
    if (empty($date) || $slotId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    $conn = getDBConnection();
    
    // For faculty, show all rooms. For others, show only available rooms.
    if ($userRole === 'faculty') {
        // Get all rooms with booking info for override capability
        $stmt = $conn->prepare("
            SELECT r.*,
                   (SELECT COUNT(*) FROM bookings b 
                    WHERE b.room_id = r.id 
                    AND b.booking_date = ? 
                    AND b.slot_id = ? 
                    AND b.status IN ('pending', 'approved')) as bookings_count,
                   (SELECT COUNT(*) FROM blocked_rooms br 
                    WHERE br.room_id = r.id 
                    AND br.blocked_date = ?) as blocked_count,
                   (SELECT CONCAT(u.username, ' (', u.role, ')') FROM bookings b
                    JOIN users u ON b.user_id = u.id
                    WHERE b.room_id = r.id 
                    AND b.booking_date = ? 
                    AND b.slot_id = ? 
                    AND b.status = 'approved'
                    LIMIT 1) as booked_by
            FROM rooms r
            WHERE r.is_available = 1
            ORDER BY r.room_number
        ");
        
        $stmt->bind_param("sisss", $date, $slotId, $date, $date, $slotId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rooms = [];
        while ($row = $result->fetch_assoc()) {
            // Add status info
            if ($row['bookings_count'] > 0) {
                $row['status'] = 'booked';
                $row['status_label'] = 'Booked by: ' . ($row['booked_by'] ?? 'Unknown');
                $row['can_override'] = true;
            } elseif ($row['blocked_count'] > 0) {
                $row['status'] = 'blocked';
                $row['status_label'] = 'Room is blocked';
                $row['can_override'] = false;
            } else {
                $row['status'] = 'available';
                $row['status_label'] = 'Available';
                $row['can_override'] = false;
            }
            $rooms[] = $row;
        }
        $stmt->close();
    } else {
        // For students and club members, only show available rooms
        $stmt = $conn->prepare("
            SELECT r.*,
                   (SELECT COUNT(*) FROM bookings b 
                    WHERE b.room_id = r.id 
                    AND b.booking_date = ? 
                    AND b.slot_id = ? 
                    AND b.status IN ('pending', 'approved')) as bookings_count,
                   (SELECT COUNT(*) FROM blocked_rooms br 
                    WHERE br.room_id = r.id 
                    AND br.blocked_date = ?) as blocked_count
            FROM rooms r
            HAVING r.is_available = 1 
                   AND bookings_count = 0 
                   AND blocked_count = 0
            ORDER BY r.room_number
        ");
        
        $stmt->bind_param("sis", $date, $slotId, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rooms = [];
        while ($row = $result->fetch_assoc()) {
            $row['status'] = 'available';
            $row['status_label'] = 'Available';
            $row['can_override'] = false;
            $rooms[] = $row;
        }
        $stmt->close();
    }
    
    closeDBConnection($conn);
    
    echo json_encode([
        'success' => true,
        'rooms' => $rooms
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>