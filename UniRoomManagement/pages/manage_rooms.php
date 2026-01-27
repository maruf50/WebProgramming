<?php
session_start();
require_once '../includes/auth_functions.php';
require_once '../database/db.php';
// Demo mode - removed authentication requirement
// AuthHelper::requireRole('admin', 'loginstudent.php');

$conn = getDBConnection();
$rooms = $conn->query("SELECT id, room_number, room_name FROM rooms ORDER BY room_number");
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniRoom - Manage Rooms</title>
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
        <li><a href="manage_rooms.php" class="active"><i class='bx bx-door-open'></i> Manage Rooms</a></li>
        <li><a href="room_history.php"><i class='bx bx-history'></i> Room History</a></li>
        <li><a href="admin_reports.php"><i class='bx bx-error'></i> Room Reports</a></li>
        <li style="margin-top: auto;"><a href="../logout.php"><i class='bx bx-log-out'></i> Exit Admin</a></li>
    </ul>
    </nav>

    <main class="main-content">
        <header>
            <h1>Manage Rooms</h1>
            <div class="user-badge">
                <span class="role-tag faculty">Administrator</span>
            </div>
        </header>

        <section class="block-section">
                <h2><i class='bx bx-plus'></i> Add New Room</h2>
            <div class="add-room-form">
                <div class="form-row">
                    <input type="text" id="addRoomNumber" class="form-input" placeholder="Room Number (e.g., 101)">
                    <input type="text" id="addRoomName" class="form-input" placeholder="Room Name">
                </div>
                <div class="form-row">
                    <input type="number" id="addCapacity" class="form-input" placeholder="Capacity" min="1">
                    <input type="text" id="addBuilding" class="form-input" placeholder="Building">
                </div>
                <div class="form-row">
                    <input type="number" id="addFloor" class="form-input" placeholder="Floor" min="0">
                </div>
                <button class="block-btn" onclick="addRoom()" style="background: #4CAF50;">
                    <i class='bx bx-plus'></i> Add Room
                </button>
            </div>
        </section>
        
        <section class="history-section">
            <h2><i class='bx bx-building-house'></i> All Rooms</h2>
            <table class="rooms-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Capacity</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="roomsTableBody">
                    <tr><td colspan="5" style="text-align:center;">Loading...</td></tr>
                </tbody>
            </table>
        </section>
        
        <section class="history-section" style="margin-top: 2rem;">
            <h3><i class='bx bx-lock'></i> Currently Unavailable Rooms</h3>
            <div id="blockedList" style="margin-top: 1rem;">
                <p>Loading...</p>
            </div>
        </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        loadAllRooms();
        loadRoomData();
    });
    
    async function loadRoomData() {
        try {
            const res = await fetch('../ajax/manage_rooms.php');
            const data = await res.json();
            if (!data.success) throw new Error('Failed to load room data');
            
            // Load unavailable rooms
            const blockedList = document.getElementById('blockedList');
            if (data.blocked && data.blocked.length > 0) {
                let html = '';
                data.blocked.forEach(room => {
                    html += `
                        <div class="history-item">
                            <div>
                                <strong>Room ${escapeHtml(room.room_number)} - ${escapeHtml(room.room_name)}</strong>
                                <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">
                                    Capacity: ${room.capacity} people<br>
                                    Location: ${escapeHtml(room.building || '-')} • Floor ${room.floor || '-'}
                                </p>
                            </div>
                            <span class="status-badge blocked">Unavailable</span>
                        </div>
                    `;
                });
                blockedList.innerHTML = html;
            } else {
                blockedList.innerHTML = '<p style="text-align:center; color:#999;">No unavailable rooms</p>';
            }
            
        } catch (err) {
            console.error('Error loading room data:', err);
            document.getElementById('blockedList').innerHTML = '<p style="color:red;">Failed to load data</p>';
        }
    }
    
    async function loadAllRooms() {
        try {
            const res = await fetch('../ajax/manage_rooms_admin.php');
            const data = await res.json();
            if (!data.success) throw new Error('Failed to load');
            renderRoomsTable(data.rooms || []);
        } catch (err) {
            console.error(err);
        }
    }
    
    function renderRoomsTable(rooms) {
        const tbody = document.getElementById('roomsTableBody');
        if (!rooms.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No rooms</td></tr>';
            return;
        }
        
        let html = '';
        rooms.forEach(room => {
            const status = room.is_available ? 'Available' : 'Unavailable';
            const statusColor = room.is_available ? 'green' : 'red';
            html += `
                <tr>
                    <td><strong>Room ${escapeHtml(room.room_number)}</strong> - ${escapeHtml(room.room_name)}</td>
                    <td>${room.capacity} people</td>
                    <td>${escapeHtml(room.building || '-')} • Floor ${room.floor || 0}</td>
                    <td><span style="color:${statusColor};">${status}</span></td>
                    <td>
                        <button class="btn-toggle" onclick="toggleRoomAvailability(${room.id})">
                            Toggle
                        </button>
                        <button class="btn-delete" onclick="deleteRoom(${room.id})">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }
    
    async function addRoom() {
        const roomNumber = document.getElementById('addRoomNumber').value.trim();
        const roomName = document.getElementById('addRoomName').value.trim();
        const capacity = parseInt(document.getElementById('addCapacity').value) || 0;
        const building = document.getElementById('addBuilding').value.trim();
        const floor = parseInt(document.getElementById('addFloor').value) || 0;
        
        if (!roomNumber || !roomName || capacity <= 0) {
            alert('Please fill in all required fields');
            return;
        }
        
        try {
            const res = await fetch('../ajax/manage_rooms_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add_room&room_number=${encodeURIComponent(roomNumber)}&room_name=${encodeURIComponent(roomName)}&capacity=${capacity}&building=${encodeURIComponent(building)}&floor=${floor}`
            });
            const data = await res.json();
            alert(data.message);
            if (data.success) {
                document.getElementById('addRoomNumber').value = '';
                document.getElementById('addRoomName').value = '';
                document.getElementById('addCapacity').value = '';
                document.getElementById('addBuilding').value = '';
                document.getElementById('addFloor').value = '';
                loadAllRooms();
            }
        } catch (err) {
            alert('Failed to add room');
        }
    }
    
    async function deleteRoom(roomId) {
        if (!confirm('Are you sure you want to delete this room?')) {
            return;
        }
        
        try {
            const res = await fetch('../ajax/manage_rooms_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_room&room_id=${roomId}`
            });
            const data = await res.json();
            alert(data.message);
            if (data.success) {
                loadAllRooms();
            }
        } catch (err) {
            alert('Failed to delete room');
        }
    }
    
    async function toggleRoomAvailability(roomId) {
        try {
            const res = await fetch('../ajax/manage_rooms_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=toggle_available&room_id=${roomId}`
            });
            const data = await res.json();
            alert(data.message);
            if (data.success) {
                loadAllRooms();
            }
        } catch (err) {
            alert('Failed to toggle room availability');
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