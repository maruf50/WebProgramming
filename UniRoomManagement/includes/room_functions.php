<?php
require_once '../database/db.php';

class RoomManager {
    
    // Check room availability for specific date and slot
    public static function checkRoomAvailability($roomId, $date, $slotId) {
        $conn = getDBConnection();
        
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
            WHERE r.id = ?
        ");
        
        $stmt->bind_param("siis", $date, $slotId, $date, $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Room is available if not booked and not blocked
            $isAvailable = ($row['is_available'] == 1 && 
                          $row['bookings_count'] == 0 && 
                          $row['blocked_count'] == 0);
            
            $stmt->close();
            closeDBConnection($conn);
            
            return [
                'available' => $isAvailable,
                'room' => $row
            ];
        }
        
        $stmt->close();
        closeDBConnection($conn);
        return null;
    }
    
    // Get all available rooms for a date and slot
    public static function getAvailableRooms($date, $slotId) {
        $conn = getDBConnection();
        
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
            $rooms[] = $row;
        }
        
        $stmt->close();
        closeDBConnection($conn);
        return $rooms;
    }
    
    // Create a new booking
    public static function createBooking($userId, $roomId, $slotId, $date, $purpose, $priority = 1) {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO bookings (user_id, room_id, slot_id, booking_date, purpose, priority)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("iiissi", $userId, $roomId, $slotId, $date, $purpose, $priority);
        
        if ($stmt->execute()) {
            $bookingId = $stmt->insert_id;
            $stmt->close();
            closeDBConnection($conn);
            return $bookingId;
        } else {
            $stmt->close();
            closeDBConnection($conn);
            return false;
        }
    }
    
    // Get all time slots
    public static function getTimeSlots() {
        $conn = getDBConnection();
        
        $result = $conn->query("SELECT * FROM time_slots ORDER BY start_time");
        $slots = [];
        
        while ($row = $result->fetch_assoc()) {
            // Format time for display
            $row['start_time_formatted'] = date('H:i', strtotime($row['start_time']));
            $row['end_time_formatted'] = date('H:i', strtotime($row['end_time']));
            $row['label'] = $row['slot_name'] . ' (' . $row['start_time_formatted'] . ' - ' . $row['end_time_formatted'] . ')';
            $slots[] = $row;
        }
        
        closeDBConnection($conn);
        return $slots;
    }
    
    // Get user's bookings
    public static function getUserBookings($userId) {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("
            SELECT b.*, r.room_number, r.room_name, s.slot_name, 
                   s.start_time, s.end_time
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN time_slots s ON b.slot_id = s.id
            WHERE b.user_id = ?
            ORDER BY b.booking_date DESC, s.start_time DESC
        ");
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        $stmt->close();
        closeDBConnection($conn);
        return $bookings;
    }
}
?>