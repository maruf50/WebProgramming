<?php
session_start();
require_once '../includes/room_functions.php';
require_once '../database/db.php';

// Demo mode - removed authentication requirement
// if (!isset($_SESSION['user_id'])) {
//     header('Location: loginstudent.php');
//     exit();
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? 1; // Default to user 1 for demo
    $roomId = $_POST['room_id'] ?? 0;
    $slotId = $_POST['slot_id'] ?? 0;
    $date = $_POST['booking_date'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $userRole = $_SESSION['role'] ?? 'student';
    
    // Map user role to priority
    $priorityMap = [
        'student' => 1,
        'club' => 2,
        'faculty' => 3
    ];
    $priority = $priorityMap[$userRole] ?? 1;
    
    // Validate inputs
    if ($roomId <= 0 || $slotId <= 0 || empty($date) || empty($purpose)) {
        header('Location: search.php?error=invalid_data');
        exit();
    }
    
    $conn = getDBConnection();
    
    // Check if room is blocked
    $blockedStmt = $conn->prepare(
        "SELECT COUNT(*) as blocked FROM blocked_rooms 
         WHERE room_id = ? AND blocked_date = ?"
    );
    $blockedStmt->bind_param('is', $roomId, $date);
    $blockedStmt->execute();
    $blockedResult = $blockedStmt->get_result()->fetch_assoc();
    $blockedStmt->close();
    
    if ($blockedResult['blocked'] > 0) {
        closeDBConnection($conn);
        header('Location: search.php?error=room_blocked');
        exit();
    }
    
    // Check for conflicting bookings (both pending and approved)
    $conflictStmt = $conn->prepare(
        "SELECT b.id, b.status, u.role FROM bookings b
         JOIN users u ON b.user_id = u.id
         WHERE b.room_id = ? AND b.slot_id = ? AND b.booking_date = ?
         AND b.status IN ('approved', 'pending')"
    );
    $conflictStmt->bind_param('iis', $roomId, $slotId, $date);
    $conflictStmt->execute();
    $conflictResult = $conflictStmt->get_result();
    $conflictStmt->close();
    
    // Check if there's a conflict with same or higher priority
    $canBook = true;
    $lowerPriorityBookings = []; // Bookings to cancel due to lower priority
    
    while ($conflict = $conflictResult->fetch_assoc()) {
        $conflictPriority = $priorityMap[$conflict['role']] ?? 1;
        if ($conflictPriority >= $priority) {
            // Same or higher priority user has this slot - cannot book
            $canBook = false;
            break;
        } else {
            // Lower priority booking found - will be cancelled if we proceed
            $lowerPriorityBookings[] = $conflict['id'];
        }
    }
    
    if (!$canBook) {
        closeDBConnection($conn);
        header('Location: search.php?error=room_unavailable');
        exit();
    }
    
    // Cancel lower priority bookings (faculty/club overrides student, faculty overrides club)
    if (!empty($lowerPriorityBookings)) {
        $cancelStmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        foreach ($lowerPriorityBookings as $cancelId) {
            $cancelStmt->bind_param('i', $cancelId);
            $cancelStmt->execute();
        }
        $cancelStmt->close();
    }
    
    // Create booking
    $bookingStmt = $conn->prepare(
        "INSERT INTO bookings (user_id, room_id, slot_id, booking_date, purpose, priority, status)
         VALUES (?, ?, ?, ?, ?, ?, 'pending')"
    );
    $bookingStmt->bind_param('iiissi', $userId, $roomId, $slotId, $date, $purpose, $priority);
    $ok = $bookingStmt->execute();
    $bookingId = $bookingStmt->insert_id;
    $bookingStmt->close();
    
    if ($ok) {
        closeDBConnection($conn);
        // Redirect to bookings page with success
        header('Location: bookings.php?success=1');
        exit();
    } else {
        closeDBConnection($conn);
        header('Location: search.php?error=booking_failed');
        exit();
    }
} else {
    header('Location: search.php');
    exit();
}
?>