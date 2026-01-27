<?php
session_start();
require_once '../includes/auth_functions.php';
require_once '../database/db.php';
// Demo mode - removed authentication requirement
// AuthHelper::requireRole('admin', 'loginstudent.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniRoom - Room History</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/manage_rooms.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <nav class="sidebar">
    <div class="logo">
        <i class='bx bxs-school'></i>
        <span>UniRoom Admin</span>
    </div>
    <ul class="nav-links">
        <li><a href="admin.php"><i class='bx bx-check-shield'></i> Requests</a></li>
        <li><a href="manage_rooms.php"><i class='bx bx-door-open'></i> Manage Rooms</a></li>
        <li><a href="room_history.php" class="active"><i class='bx bx-history'></i> Room History</a></li>
        <li><a href="admin_reports.php"><i class='bx bx-error'></i> Room Reports</a></li>
        <li style="margin-top: auto;"><a href="../logout.php"><i class='bx bx-log-out'></i> Exit Admin</a></li>
    </ul>
    </nav>

    <main class="main-content">
        <header>
            <h1>Room History</h1>
            <div class="user-badge">
                <span class="role-tag faculty">Administrator</span>
            </div>
        </header>

        <section class="history-section">
            <h3><i class='bx bx-check'></i> Approved Bookings</h3>
            <div id="approvedList" style="margin-top: 1rem;">
                <p>Loading...</p>
            </div>
        </section>
        
        <section class="history-section" style="margin-top: 2rem;">
            <h3><i class='bx bx-x'></i> Rejected/Cancelled Bookings</h3>
            <div id="rejectedList" style="margin-top: 1rem;">
                <p>Loading...</p>
            </div>
        </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        loadRoomHistory();
    });
    
    async function loadRoomHistory() {
        try {
            const res = await fetch('../ajax/manage_rooms.php');
            const data = await res.json();
            if (!data.success) throw new Error('Failed to load room data');
            
            // Load approved bookings
            const approvedList = document.getElementById('approvedList');
            if (data.approved && data.approved.length > 0) {
                let html = '';
                data.approved.forEach(booking => {
                    html += `
                        <div class="history-item">
                            <div>
                                <strong>${escapeHtml(booking.username)} → Room ${escapeHtml(booking.room_number)}</strong>
                                <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">
                                    Slot: ${escapeHtml(booking.slot_name)}<br>
                                    Date: ${booking.booking_date}
                                </p>
                            </div>
                            <span class="status-badge approved">Approved</span>
                        </div>
                    `;
                });
                approvedList.innerHTML = html;
            } else {
                approvedList.innerHTML = '<p style="text-align:center; color:#999;">No approved bookings</p>';
            }
            
            // Load rejected/cancelled bookings
            const rejectedList = document.getElementById('rejectedList');
            if (data.rejected && data.rejected.length > 0) {
                let html = '';
                data.rejected.forEach(booking => {
                    html += `
                        <div class="history-item">
                            <div>
                                <strong>${escapeHtml(booking.username)} → Room ${escapeHtml(booking.room_number)}</strong>
                                <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">
                                    Slot: ${escapeHtml(booking.slot_name)}<br>
                                    Date: ${booking.booking_date}
                                </p>
                            </div>
                            <span class="status-badge denied">Rejected</span>
                        </div>
                    `;
                });
                rejectedList.innerHTML = html;
            } else {
                rejectedList.innerHTML = '<p style="text-align:center; color:#999;">No rejected bookings</p>';
            }
        } catch (err) {
            console.error('Error loading room history:', err);
            document.getElementById('approvedList').innerHTML = '<p style="color:red;">Failed to load approved bookings</p>';
            document.getElementById('rejectedList').innerHTML = '<p style="color:red;">Failed to load rejected bookings</p>';
        }
    }
    
    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
    </script>
</body>
</html>
