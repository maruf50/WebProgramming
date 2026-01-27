<?php
session_start();
require_once '../includes/auth_functions.php';
require_once '../database/db.php';

// Demo mode - removed authentication requirement
// AuthHelper::requireLogin();

$userId = $_SESSION['user_id'] ?? 1; // Default to user 1 for demo

// Handle cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $bookingId = (int)$_POST['booking_id'];
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $bookingId, $userId);
    $stmt->execute();
    $stmt->close();
    closeDBConnection($conn);
    header("Location: bookings.php?msg=cancelled");
    exit();
}

// Fetch all bookings for the user
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT b.*, r.room_number, r.room_name, r.capacity, 
           ts.slot_name, ts.start_time, ts.end_time
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN time_slots ts ON b.slot_id = ts.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, ts.start_time DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
closeDBConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniRoom - My Bookings</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .bookings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .booking-card { background: rgba(255,255,255,0.9); border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .booking-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .booking-room { font-size: 1.25rem; font-weight: bold; color: #333; }
        .booking-date { color: #666; font-size: 0.9rem; margin-top: 0.25rem; }
        .booking-status { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-cancelled { background: #e2e3e5; color: #383d41; }
        .booking-details { display: flex; flex-wrap: wrap; gap: 1rem; margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 8px; }
        .detail-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: #555; }
        .booking-purpose { padding: 1rem; background: #f0f0f0; border-radius: 8px; font-size: 0.9rem; color: #444; margin-bottom: 1rem; }
        .booking-actions { display: flex; gap: 0.5rem; }
        .action-btn { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
        .cancel-btn { background: #ff4757; color: white; }
        .cancel-btn:hover { background: #ff3142; }
        .no-bookings { text-align: center; padding: 3rem; background: rgba(255,255,255,0.9); border-radius: 16px; }
        .no-bookings i { font-size: 4rem; color: #ddd; }
        .no-bookings h3 { margin: 1rem 0 0.5rem; color: #333; }
        .no-bookings p { color: #666; }
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
            <li><a href="search.php"><i class='bx bx-search-alt'></i> Search Rooms</a></li>
            <li><a href="report_room.php"><i class='bx bx-error'></i> Report Room</a></li>
            <li><a href="bookings.php" class="active"><i class='bx bx-calendar-check'></i> My Bookings</a></li>
            <li style="margin-top: auto;"><a href="../logout.php"><i class='bx bx-log-out'></i> Sign out</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header>
            <h1>My Bookings</h1>
            <div class="user-badge">
                <span class="role-tag <?php echo $_SESSION['role'] ?? 'student'; ?>">
                    <?php echo ucfirst($_SESSION['role'] ?? 'Student'); ?>
                </span>
            </div>
        </header>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
        <div class="success-msg">
            <i class='bx bx-check'></i> Booking cancelled successfully.
        </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
        <div class="no-bookings">
            <i class='bx bx-calendar-x'></i>
            <h3>No Bookings Found</h3>
            <p>You haven't made any bookings yet.</p>
            <a href="search.php" style="display: inline-block; margin-top: 1rem; 
               background: var(--primary-color); color: white; padding: 10px 20px; 
               border-radius: 8px; text-decoration: none;">
                <i class='bx bx-plus'></i> Book a Room
            </a>
        </div>
        <?php else: ?>
        <div class="bookings-grid">
            <?php foreach ($bookings as $booking): 
                $isUpcoming = strtotime($booking['booking_date']) >= strtotime(date('Y-m-d'));
                $statusClass = 'status-' . $booking['status'];
            ?>
            <div class="booking-card">
                <div class="booking-header">
                    <div>
                        <div class="booking-room">Room <?php echo htmlspecialchars($booking['room_number']); ?></div>
                        <div class="booking-date"><?php echo date('l, F j, Y', strtotime($booking['booking_date'])); ?></div>
                    </div>
                    <div class="booking-status <?php echo $statusClass; ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </div>
                </div>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <i class='bx bx-time'></i>
                        <span><?php echo htmlspecialchars($booking['slot_name']); ?> (<?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?>)</span>
                    </div>
                    <div class="detail-item">
                        <i class='bx bx-group'></i>
                        <span>Capacity: <?php echo $booking['capacity']; ?> people</span>
                    </div>
                </div>
                
                <div class="booking-purpose">
                    <?php echo htmlspecialchars($booking['purpose'] ?: 'No description provided.'); ?>
                </div>
                
                <div class="booking-actions">
                    <?php if (($booking['status'] === 'pending' || ($booking['status'] === 'approved' && $isUpcoming))): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        <button type="submit" class="action-btn cancel-btn">
                            <i class='bx bx-x-circle'></i> Cancel
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>