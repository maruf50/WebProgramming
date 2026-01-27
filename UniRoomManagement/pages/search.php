<?php
session_start();
require_once '../includes/auth_functions.php';
require_once '../includes/room_functions.php';
require_once '../database/db.php';

// Demo mode - removed authentication requirement
// AuthHelper::requireLogin('loginstudent.php');

$userRole = $_SESSION['role'] ?? 'student';
$timeSlots = RoomManager::getTimeSlots();

// Handle form submissions
$selectedDate = $_GET['date'] ?? '';
$selectedSlot = $_GET['slot_id'] ?? '';
$rooms = [];
$error = '';

// If date and slot are selected, fetch available rooms
if ($selectedDate && $selectedSlot) {
    $conn = getDBConnection();
    
    // Get all rooms with their booking status for the selected date and slot
    // Only show as "booked" if the booking is APPROVED (not pending)
    $sql = "SELECT r.*, 
            (SELECT b.status FROM bookings b 
             WHERE b.room_id = r.id AND b.booking_date = ? AND b.slot_id = ? 
             AND b.status = 'approved' 
             ORDER BY b.priority DESC LIMIT 1) as booking_status,
            (SELECT u.role FROM bookings b 
             JOIN users u ON b.user_id = u.id 
             WHERE b.room_id = r.id AND b.booking_date = ? AND b.slot_id = ? 
             AND b.status = 'approved' 
             ORDER BY b.priority DESC LIMIT 1) as booked_by_role,
            (SELECT 1 FROM blocked_rooms br 
             WHERE br.room_id = r.id AND br.blocked_date = ? LIMIT 1) as is_blocked
            FROM rooms r 
            WHERE r.is_available = 1
            ORDER BY r.room_number";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisss", $selectedDate, $selectedSlot, $selectedDate, $selectedSlot, $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $rooms = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    closeDBConnection($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniRoom - Search Rooms</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .search-form { background: rgba(255,255,255,0.9); padding: 2rem; border-radius: 16px; margin-bottom: 2rem; }
        .form-row { display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        .search-btn { background: var(--primary-color, #ee6b43); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 0.5rem; }
        .search-btn:hover { opacity: 0.9; }
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .room-card { background: rgba(255,255,255,0.95); border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
        .room-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .room-card.available { border-color: #4CAF50; }
        .room-card.booked { border-color: #ff9800; opacity: 0.7; }
        .room-card.blocked { border-color: #f44336; opacity: 0.6; cursor: not-allowed; }
        .room-card.selected { border-color: var(--primary-color, #ee6b43); background: rgba(238,107,67,0.1); }
        .room-number { font-size: 1.5rem; font-weight: bold; color: #333; }
        .room-name { color: #666; margin: 0.25rem 0; }
        .room-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-top: 0.5rem; }
        .status-available { background: #d4edda; color: #155724; }
        .status-booked { background: #fff3cd; color: #856404; }
        .status-blocked { background: #f8d7da; color: #721c24; }
        .room-capacity { color: #888; font-size: 0.9rem; margin-top: 0.5rem; }
        .booking-section { background: rgba(255,255,255,0.9); padding: 2rem; border-radius: 16px; margin-top: 2rem; display: none; }
        .booking-section.show { display: block; }
        .booking-summary { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .booking-summary p { margin: 0.5rem 0; }
        textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; min-height: 100px; font-family: inherit; }
        .submit-btn { background: #4CAF50; color: white; border: none; padding: 14px 28px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 1rem; margin-top: 1rem; }
        .submit-btn:hover { background: #45a049; }
        .submit-btn:disabled { background: #ccc; cursor: not-allowed; }
        .empty-state { text-align: center; padding: 3rem; color: #666; }
        .empty-state i { font-size: 4rem; color: #ddd; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .success-msg { background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="logo">
            <i class='bx bxs-school'></i>
            <span>UniRoom</span>
        </div>
        <ul class="nav-links">
            <li><a href="home.php"><i class='bx bxs-dashboard'></i> Dashboard</a></li>
            <li><a href="search.php" class="active"><i class='bx bx-search-alt'></i> Search Rooms</a></li>
            <li><a href="report_room.php"><i class='bx bx-error'></i> Report Room</a></li>
            <li><a href="bookings.php"><i class='bx bx-calendar-check'></i> My Bookings</a></li>
            <li style="margin-top: auto;"><a href="../logout.php"><i class='bx bx-log-out'></i> Sign out</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header>
            <h1>Search Available Rooms</h1>
        </header>

        <?php if (isset($_GET['error'])): ?>
        <div class="error-msg">
            <?php 
            $errors = [
                'invalid_data' => 'Invalid booking data. Please try again.',
                'room_blocked' => 'This room is blocked for the selected date.',
                'room_unavailable' => 'This room is no longer available.',
                'booking_failed' => 'Failed to create booking. Please try again.'
            ];
            echo $errors[$_GET['error']] ?? 'An error occurred.';
            ?>
        </div>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="search-form">
            <h2><i class='bx bx-search'></i> Select Date & Time Slot</h2>
            <form method="GET" action="search.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Select Date</label>
                        <input type="date" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo htmlspecialchars($selectedDate); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="slot_id">Select Time Slot</label>
                        <select id="slot_id" name="slot_id" required>
                            <option value="">-- Choose a slot --</option>
                            <?php foreach ($timeSlots as $slot): ?>
                            <option value="<?php echo $slot['id']; ?>" <?php echo $selectedSlot == $slot['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($slot['slot_name']); ?> (<?php echo $slot['start_time_formatted']; ?> - <?php echo $slot['end_time_formatted']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="search-btn">
                        <i class='bx bx-search'></i> Search Rooms
                    </button>
                </div>
            </form>
        </div>

        <?php if ($selectedDate && $selectedSlot): ?>
        <!-- Available Rooms -->
        <section>
            <h2><i class='bx bx-building-house'></i> Available Rooms for <?php echo date('F j, Y', strtotime($selectedDate)); ?></h2>
            
            <?php if (empty($rooms)): ?>
            <div class="empty-state">
                <i class='bx bx-building-house'></i>
                <h3>No Rooms Found</h3>
                <p>No rooms are available in the system.</p>
            </div>
            <?php else: ?>
            <p>Click on an available room to book it:</p>
            <div class="rooms-grid">
                <?php foreach ($rooms as $room): 
                    $isBlocked = !empty($room['is_blocked']);
                    $isBooked = !empty($room['booking_status']);
                    $status = $isBlocked ? 'blocked' : ($isBooked ? 'booked' : 'available');
                    $statusLabel = $isBlocked ? 'Blocked' : ($isBooked ? 'Booked' : 'Available');
                    $canBook = !$isBlocked && !$isBooked;
                ?>
                <div class="room-card <?php echo $status; ?>" 
                     <?php if ($canBook): ?>onclick="selectRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>', '<?php echo htmlspecialchars($room['room_name'] ?? ''); ?>')"<?php endif; ?>>
                    <div class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                    <?php if ($room['room_name']): ?>
                    <div class="room-name"><?php echo htmlspecialchars($room['room_name']); ?></div>
                    <?php endif; ?>
                    <div class="room-status status-<?php echo $status; ?>"><?php echo $statusLabel; ?></div>
                    <div class="room-capacity"><i class='bx bx-group'></i> Capacity: <?php echo $room['capacity']; ?> people</div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Booking Form -->
        <section id="bookingSection" class="booking-section">
            <h2><i class='bx bx-edit-alt'></i> Book Room</h2>
            <form method="POST" action="process_booking.php">
                <input type="hidden" name="room_id" id="bookingRoomId">
                <input type="hidden" name="slot_id" value="<?php echo htmlspecialchars($selectedSlot); ?>">
                <input type="hidden" name="booking_date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                
                <div class="booking-summary">
                    <h4>Booking Summary</h4>
                    <p><strong>Date:</strong> <?php echo date('l, F j, Y', strtotime($selectedDate)); ?></p>
                    <p><strong>Time:</strong> <?php 
                        foreach ($timeSlots as $slot) {
                            if ($slot['id'] == $selectedSlot) {
                                echo htmlspecialchars($slot['slot_name']) . ' (' . $slot['start_time_formatted'] . ' - ' . $slot['end_time_formatted'] . ')';
                                break;
                            }
                        }
                    ?></p>
                    <p><strong>Room:</strong> <span id="selectedRoomText">-</span></p>
                </div>
                
                <div class="form-group">
                    <label for="purpose">Purpose of Booking *</label>
                    <textarea name="purpose" id="purpose" placeholder="Describe the purpose of your booking..." required></textarea>
                </div>
                
                <button type="submit" class="submit-btn" id="submitBtn" disabled>
                    <i class='bx bx-send'></i> Submit Booking Request
                </button>
            </form>
        </section>
        <?php endif; ?>
    </main>

    <script>
    function selectRoom(roomId, roomNumber, roomName) {
        // Update hidden field
        document.getElementById('bookingRoomId').value = roomId;
        
        // Update display text
        document.getElementById('selectedRoomText').textContent = 'Room ' + roomNumber + (roomName ? ' - ' + roomName : '');
        
        // Show booking section
        document.getElementById('bookingSection').classList.add('show');
        
        // Enable submit button
        document.getElementById('submitBtn').disabled = false;
        
        // Highlight selected room
        document.querySelectorAll('.room-card').forEach(card => card.classList.remove('selected'));
        event.currentTarget.classList.add('selected');
        
        // Scroll to booking form
        document.getElementById('bookingSection').scrollIntoView({ behavior: 'smooth' });
    }
    </script>
</body>
</html>