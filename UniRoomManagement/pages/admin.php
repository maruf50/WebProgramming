<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/auth_functions.php';
// Demo mode - removed authentication requirement
// AuthHelper::requireRole('admin', 'loginstudent.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniRoom - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <nav class="sidebar">
    <div class="logo">
        <i class='bx bxs-school'></i>
        <span>UniRoom Admin</span>
    </div>
    <ul class="nav-links">
        <li><a href="admin.php" class="active"><i class='bx bx-check-shield'></i> Requests</a></li>
        <li><a href="manage_rooms.php"><i class='bx bx-door-open'></i> Manage Rooms</a></li>
        <li><a href="room_history.php"><i class='bx bx-history'></i> Room History</a></li>
        <li><a href="admin_reports.php"><i class='bx bx-error'></i> Room Reports</a></li>
        <li style="margin-top: auto;"><a href="../logout.php"><i class='bx bx-log-out'></i> Exit Admin</a></li>
    </ul>
    </nav>

    <main class="main-content">
        <header>
            <h1>Admin Verification Queue</h1>
            <div class="user-badge">
                <span class="role-tag faculty">Administrator</span>
            </div>
        </header>

        <section class="card-container">
            <div class="card-header">
                <h2><i class='bx bx-transfer-alt'></i> Pending Booking Requests</h2>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Requester (Priority)</th>
                            <th>Room</th>
                            <th>Date & Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="requestsBody">
                        <tr><td colspan="4" style="text-align:center;padding:2rem;">Loading requests...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', loadRequests);
    
    async function loadRequests() {
        try {
            const res = await fetch('../ajax/admin_requests.php');
            const data = await res.json();
            if (!data.success) throw new Error('Failed to load');
            renderRequests(data.requests || []);
        } catch (err) {
            console.error(err);
            document.getElementById('requestsBody').innerHTML = 
                '<tr><td colspan="4" style="text-align:center;color:red;">Failed to load requests</td></tr>';
        }
    }
    
    function renderRequests(requests) {
        const tbody = document.getElementById('requestsBody');
        if (!requests.length) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:2rem;color:#999;">No pending requests</td></tr>';
            return;
        }
        
        let html = '';
        requests.forEach(req => {
            const priorityMap = { 1: 'Student', 2: 'Club/Organization', 3: 'Faculty' };
            const priorityClass = 'priority-' + req.priority;
            const priorityLabel = priorityMap[req.priority] || 'Student';
            const startTime = req.start_time.substring(0, 5);
            const endTime = req.end_time.substring(0, 5);
            
            html += `
                <tr onclick="toggleDetails(${req.id})">
                    <td>
                        <strong>${escapeHtml(req.first_name || req.username)}</strong>
                        <span class="priority-badge ${priorityClass}">${escapeHtml(priorityLabel)}</span>
                    </td>
                    <td>${escapeHtml(req.room_name)} (Room ${escapeHtml(req.room_number)})</td>
                    <td>${escapeHtml(req.booking_date)} ${startTime}-${endTime}</td>
                    <td>
                        <button class="btn-approve" onclick="event.stopPropagation(); handleRequest(${req.id}, 'approve')">Approve</button>
                        <button class="btn-deny" onclick="event.stopPropagation(); handleRequest(${req.id}, 'reject')">Deny</button>
                    </td>
                </tr>
                <tr id="details-${req.id}" style="display:none;">
                    <td colspan="4">
                        <div class="request-details">
                            <h4>Booking Details:</h4>
                            <p><strong>User:</strong> ${escapeHtml(req.username)} (${escapeHtml(req.email)})</p>
                            <p><strong>Room:</strong> ${escapeHtml(req.room_name)} - Capacity: ${req.capacity} people</p>
                            <p><strong>Date & Time:</strong> ${escapeHtml(req.booking_date)} ${startTime}-${endTime}</p>
                            <p><strong>Purpose:</strong> ${escapeHtml(req.purpose || 'No description')}</p>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }
    
    function toggleDetails(requestId) {
        const detailsRow = document.getElementById(`details-${requestId}`);
        if (detailsRow) {
            detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
        }
    }
    
    async function handleRequest(bookingId, action) {
        if (!confirm(`Are you sure you want to ${action} this booking?`)) {
            return;
        }
        
        try {
            const res = await fetch('../ajax/admin_requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=${action}&booking_id=${bookingId}`
            });
            const data = await res.json();
            alert(data.message);
            if (data.success) {
                loadRequests();
            }
        } catch (err) {
            alert('Failed to process request');
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