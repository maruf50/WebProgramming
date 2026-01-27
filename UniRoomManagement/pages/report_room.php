<?php
session_start();
require_once '../includes/auth_functions.php';
require_once '../database/db.php';

// Demo mode - removed authentication requirement
// AuthHelper::requireLogin('loginstudent.php');

$conn = getDBConnection();
$rooms = $conn->query("SELECT id, room_number, room_name FROM rooms ORDER BY room_number");
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report a Room Issue</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/report_room.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
            <li><a href="report_room.php" class="active"><i class='bx bx-error'></i> Report Room</a></li>
            <li><a href="bookings.php"><i class='bx bx-calendar-check'></i> My Bookings</a></li>
            <li style="margin-top: auto;"><a href="../logout.php"><i class='bx bx-log-out'></i> Sign out</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header>
            <h1>Report a Room Issue</h1>
        </header>
        <div class="report-card">
            <form id="reportForm">
                <div class="form-group">
                    <label for="room_id">Room</label>
                    <select id="room_id" name="room_id" required>
                        <option value="">Select room</option>
                        <?php while ($row = $rooms->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>">Room <?php echo htmlspecialchars($row['room_number']); ?> - <?php echo htmlspecialchars($row['room_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" placeholder="Short summary" required>
                </div>
                <div class="form-group">
                    <label for="severity">Severity</label>
                    <select id="severity" name="severity">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" placeholder="Describe the issue..." required></textarea>
                </div>
                <button type="submit" class="btn-primary">Submit Report</button>
                <div id="reportMessage" style="margin-top:10px;"></div>
            </form>
        </div>
    </main>

    <script>
    document.getElementById('reportForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const msg = document.getElementById('reportMessage');
        msg.textContent = 'Submitting...';
        try {
            const res = await fetch('../ajax/report_room.php', { method: 'POST', body: formData });
            const data = await res.json();
            msg.textContent = data.message || 'Saved';
            if (data.success) {
                form.reset();
            }
        } catch (err) {
            msg.textContent = 'Failed to submit report';
        }
    });
    </script>
</body>
</html>
