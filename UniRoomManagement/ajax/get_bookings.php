<?php
require_once '../includes/room_functions.php';
require_once '../includes/auth_functions.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'student';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get parameters for filtering
    $status = $_GET['status'] ?? null;
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    $offset = ($page - 1) * $limit;
    
    $conn = getDBConnection();
    
    // Build query based on user role
    if ($userRole === 'admin') {
        // Admin can see all bookings
        $query = "
            SELECT b.*, 
                   r.room_number, r.room_name, r.capacity,
                   s.slot_name, s.start_time, s.end_time,
                   u.username, u.email, u.role as user_role
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN time_slots s ON b.slot_id = s.id
            JOIN users u ON b.user_id = u.id
            WHERE 1=1
        ";
        
        $countQuery = "
            SELECT COUNT(*) as total
            FROM bookings b
            WHERE 1=1
        ";
        
        $params = [];
        $types = "";
        
        // Add filters
        if ($status) {
            $query .= " AND b.status = ?";
            $countQuery .= " AND b.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        if ($dateFrom) {
            $query .= " AND b.booking_date >= ?";
            $countQuery .= " AND b.booking_date >= ?";
            $params[] = $dateFrom;
            $types .= "s";
        }
        
        if ($dateTo) {
            $query .= " AND b.booking_date <= ?";
            $countQuery .= " AND b.booking_date <= ?";
            $params[] = $dateTo;
            $types .= "s";
        }

        // Preserve count params/types before pagination
        $countParams = $params;
        $countTypes = $types;
        
        // Add ordering and pagination
        $query .= " ORDER BY b.booking_date DESC, b.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
    } else {
        // Regular users only see their own bookings
        $query = "
            SELECT b.*, 
                   r.room_number, r.room_name, r.capacity,
                   s.slot_name, s.start_time, s.end_time
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN time_slots s ON b.slot_id = s.id
            WHERE b.user_id = ?
        ";
        
        $countQuery = "
            SELECT COUNT(*) as total
            FROM bookings b
            WHERE b.user_id = ?
        ";
        
        $params = [$userId];
        $types = "i";
        
        // Add filters for regular users
        if ($status) {
            $query .= " AND b.status = ?";
            $countQuery .= " AND b.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        if ($dateFrom) {
            $query .= " AND b.booking_date >= ?";
            $countQuery .= " AND b.booking_date >= ?";
            $params[] = $dateFrom;
            $types .= "s";
        }
        
        if ($dateTo) {
            $query .= " AND b.booking_date <= ?";
            $countQuery .= " AND b.booking_date <= ?";
            $params[] = $dateTo;
            $types .= "s";
        }

        // Preserve count params/types before pagination
        $countParams = $params;
        $countTypes = $types;
        
        // Add ordering and pagination
        $query .= " ORDER BY b.booking_date DESC, b.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
    }
    
    // Get total count
    $stmt = $conn->prepare($countQuery);
    if (!empty($countParams)) {
        $stmt->bind_param($countTypes, ...$countParams);
    }
    $stmt->execute();
    $countResult = $stmt->get_result();
    $totalRow = $countResult->fetch_assoc();
    $totalBookings = $totalRow['total'];
    $stmt->close();
    
    // Get bookings data
    $stmt = $conn->prepare($query);
    
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        // Format dates and times
        $row['booking_date_formatted'] = date('d M, Y', strtotime($row['booking_date']));
        $row['start_time_formatted'] = date('g:i A', strtotime($row['start_time']));
        $row['end_time_formatted'] = date('g:i A', strtotime($row['end_time']));
        $row['time_slot'] = $row['start_time_formatted'] . ' - ' . $row['end_time_formatted'];
        
        // Add status badge class
        $row['status_class'] = 'status-' . $row['status'];
        
        // Add priority label
        $priorityLabels = [
            1 => 'Student',
            2 => 'Club/Organization',
            3 => 'Faculty'
        ];
        $row['priority_label'] = $priorityLabels[$row['priority']] ?? 'Unknown';
        
        $bookings[] = $row;
    }
    
    $stmt->close();
    closeDBConnection($conn);
    
    $response = [
        'success' => true,
        'bookings' => $bookings,
        'total' => $totalBookings,
        'page' => (int)$page,
        'total_pages' => ceil($totalBookings / $limit),
        'user_role' => $userRole
    ];
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle booking actions (cancel, edit, etc.)
    $action = $_POST['action'] ?? '';
    $bookingId = $_POST['booking_id'] ?? 0;
    
    if (!$bookingId || !$action) {
        $response = ['success' => false, 'message' => 'Missing parameters'];
        echo json_encode($response);
        exit;
    }
    
    $conn = getDBConnection();
    
    // First, verify the booking exists and user has permission
    $stmt = $conn->prepare("
        SELECT b.*, u.id as user_id 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result->num_rows) {
        $response = ['success' => false, 'message' => 'Booking not found'];
        echo json_encode($response);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    // Check permissions (admin or booking owner)
    $isAdmin = ($userRole === 'admin');
    $isOwner = ($booking['user_id'] == $userId);
    
    if (!$isAdmin && !$isOwner) {
        $response = ['success' => false, 'message' => 'Permission denied'];
        echo json_encode($response);
        exit;
    }
    
    switch ($action) {
        case 'cancel':
            // Only allow cancellation if booking is pending or approved
            if (!in_array($booking['status'], ['pending', 'approved'])) {
                $response = ['success' => false, 'message' => 'Cannot cancel booking in current status'];
                break;
            }
            
            // Check if booking date is in the future
            $bookingDate = new DateTime($booking['booking_date']);
            $today = new DateTime();
            
            if ($bookingDate < $today) {
                $response = ['success' => false, 'message' => 'Cannot cancel past bookings'];
                break;
            }
            
            $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param("i", $bookingId);
            
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Booking cancelled successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to cancel booking'];
            }
            $stmt->close();
            break;
            
        case 'edit':
            // Allow editing of purpose only for pending bookings
            if ($booking['status'] !== 'pending') {
                $response = ['success' => false, 'message' => 'Only pending bookings can be edited'];
                break;
            }
            
            $newPurpose = $_POST['purpose'] ?? '';
            if (empty($newPurpose)) {
                $response = ['success' => false, 'message' => 'Purpose is required'];
                break;
            }
            
            $stmt = $conn->prepare("UPDATE bookings SET purpose = ? WHERE id = ?");
            $stmt->bind_param("si", $newPurpose, $bookingId);
            
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Booking updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update booking'];
            }
            $stmt->close();
            break;
            
        case 'admin_approve':
            if (!$isAdmin) {
                $response = ['success' => false, 'message' => 'Admin access required'];
                break;
            }
            
            $stmt = $conn->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
            $stmt->bind_param("i", $bookingId);
            
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Booking approved successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to approve booking'];
            }
            $stmt->close();
            break;
            
        case 'admin_reject':
            if (!$isAdmin) {
                $response = ['success' => false, 'message' => 'Admin access required'];
                break;
            }
            
            $reason = $_POST['reason'] ?? '';
            $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $bookingId);
            
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Booking rejected successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to reject booking'];
            }
            $stmt->close();
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    closeDBConnection($conn);
} else {
    $response = ['success' => false, 'message' => 'Invalid request method'];
}

echo json_encode($response);
?>