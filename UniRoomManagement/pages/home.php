<?php
session_start();
require_once '../includes/auth_functions.php';
// Demo mode - removed authentication requirement
// AuthHelper::requireLogin('loginstudent.php');

$displayName = $_SESSION['first_name'] ?? $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniRoom - Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <nav class="sidebar">
        <div class="logo">
            <i class='bx bxs-school'></i>
            <span>UniRoom</span>
        </div>
        <ul class="nav-links">
            <li><a href="home.php" class="active"><i class='bx bxs-dashboard'></i> Dashboard</a></li>
            <li><a href="search.php"><i class='bx bx-search-alt'></i> Search Rooms</a></li>
            <li><a href="report_room.php"><i class='bx bx-error'></i> Report Room</a></li>
            <li><a href="bookings.php"><i class='bx bx-calendar-check'></i> My Bookings</a></li>
            <li style="margin-top: auto;"><a href="../logout.php"><i class='bx bx-log-out'></i> Sign out</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header>
            <div class="welcome">
                <h1>Dashboard</h1>
                <p>Welcome back, <strong id="user-name"><?php echo htmlspecialchars($displayName); ?></strong></p>
            </div>
            <div class="header-right">
                <div style="position: relative; margin-right: 1.5rem; cursor: pointer;" onclick="toggleNotifications()">
                    <i class='bx bx-bell' style="font-size: 1.5rem;"></i>
                    <span id="notif-badge" style="position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; display: none;">0</span>
                </div>
                <div class="user-badge">
                    <span class="role-tag <?php echo htmlspecialchars($role); ?>" id="user-role">
                        <?php echo ucfirst(htmlspecialchars($role)); ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Notifications Modal -->
        <div id="notificationsModal" style="display: none; position: fixed; top: 60px; right: 20px; background: white; border: 1px solid #eee; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 350px; max-height: 400px; z-index: 1000; overflow-y: auto;">
            <div style="padding: 1rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Notifications</h3>
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="loadNotifications()" style="background: none; border: none; color: #666; cursor: pointer; font-size: 1rem; padding: 0 4px;" title="Refresh">
                        <i class='bx bx-refresh' style="font-size: 1.2rem;"></i>
                    </button>
                    <button onclick="markAllNotificationsRead()" style="background: none; border: none; color: #2196F3; cursor: pointer; font-size: 0.85rem;">Mark all read</button>
                </div>
            </div>
            <div id="notificationsContainer">
                <p style="padding: 1rem; text-align: center; color: #999;">No notifications</p>
            </div>
        </div>

        <section class="stats-grid">
            <div class="stat-card">
                <i class='bx bx-time-five'></i>
                <div>
                    <h3>Pending</h3>
                    <p class="stat-number" id="pending-count">--</p>
                </div>
            </div>
            <div class="stat-card">
                <i class='bx bx-calendar-star'></i>
                <div>
                    <h3>Upcoming</h3>
                    <p class="stat-number" id="upcoming-count">--</p>
                </div>
            </div>
            <div class="stat-card">
                <i class='bx bx-check-double'></i>
                <div>
                    <h3>Approved</h3>
                    <p class="stat-number" id="approved-count">--</p>
                </div>
            </div>
        </section>

        <section class="card-container full-width">
            <div class="card-header">
                <h2><i class='bx bx-calendar-event'></i> Campus Events</h2>
            </div>
            <div class="event-body">
                <ul class="event-list" id="events-list">
                    <li class="event-item" id="events-empty">Loading events...</li>
                </ul>
            </div>
        </section>
    </main>
<script>
async function loadDashboard() {
    try {
        const res = await fetch('../ajax/dashboard_stats.php');
        const data = await res.json();
        if (!data.success) throw new Error('Failed to load');

        document.getElementById('pending-count').textContent = data.pending;
        document.getElementById('upcoming-count').textContent = data.upcoming;
        document.getElementById('approved-count').textContent = data.approved;

        renderEvents(data.events || []);
    } catch (err) {
        console.error(err);
        document.getElementById('pending-count').textContent = '--';
        document.getElementById('upcoming-count').textContent = '--';
        document.getElementById('approved-count').textContent = '--';
        renderEvents([]);
    }
}

function renderEvents(events) {
    const list = document.getElementById('events-list');
    list.innerHTML = '';

    if (!events.length) {
        const li = document.createElement('li');
        li.className = 'event-item';
        li.textContent = 'No upcoming events in the next 30 days.';
        list.appendChild(li);
        return;
    }

    events.forEach(evt => {
        const start = new Date(evt.start_at_iso);
        const day = start.getDate().toString().padStart(2, '0');
        const month = start.toLocaleString('en', { month: 'short' }).toUpperCase();
        const time = start.toLocaleTimeString('en', { hour: 'numeric', minute: '2-digit' });

        const li = document.createElement('li');
        li.className = 'event-item';
        li.innerHTML = `
            <div class="event-date">
                <span class="day">${day}</span>
                <span class="month">${month}</span>
            </div>
            <div class="event-info">
                <strong>${escapeHtml(evt.title)}</strong>
                <p>${escapeHtml(evt.location || 'TBA')} • ${time}</p>
            </div>
            <i class='bx bx-chevron-right'></i>
        `;
        list.appendChild(li);
    });
}

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function loadNotifications() {
    try {
        const res = await fetch('../ajax/get_notifications.php');
        const data = await res.json();
        if (!data.success) throw new Error('Failed to load');
        
        const badge = document.getElementById('notif-badge');
        const container = document.getElementById('notificationsContainer');
        
        if (data.count > 0) {
            badge.textContent = data.count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
        
        if (data.notifications.length === 0) {
            container.innerHTML = '<p style="padding: 1rem; text-align: center; color: #999;">No notifications</p>';
            return;
        }
        
        let html = '';
        data.notifications.forEach(notif => {
            const typeColor = {
                'booking_cancelled': '#ff4757',
                'booking_overridden': '#ff9800',
                'booking_approved': '#4CAF50',
                'booking_rejected': '#ff6b6b'
            };
            const color = typeColor[notif.type] || '#2196F3';
            
            html += `
                <div style="padding: 1rem; border-bottom: 1px solid #eee; cursor: pointer;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'" onclick="markNotificationRead(${notif.id})">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <div style="color: ${color}; font-weight: bold; font-size: 0.9rem;">●</div>
                            <strong style="font-size: 0.95rem;">${escapeHtml(notif.title)}</strong>
                            <p style="margin: 4px 0 0 0; color: #666; font-size: 0.85rem;">${escapeHtml(notif.message)}</p>
                            ${notif.booking_date ? `<p style="margin: 4px 0 0 0; color: #999; font-size: 0.8rem;">Room ${escapeHtml(notif.room_number)} • ${notif.booking_date}</p>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    } catch (err) {
        console.error('Error loading notifications:', err);
    }
}

function toggleNotifications() {
    const modal = document.getElementById('notificationsModal');
    if (modal.style.display === 'none') {
        loadNotifications();
        modal.style.display = 'block';
    } else {
        modal.style.display = 'none';
    }
}

async function markNotificationRead(notificationId) {
    try {
        const res = await fetch('../ajax/get_notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=mark_read&notification_id=${notificationId}`
        });
        const data = await res.json();
        if (data.success) {
            loadNotifications();
        }
    } catch (err) {
        console.error('Error marking notification as read:', err);
    }
}

async function markAllNotificationsRead() {
    try {
        const res = await fetch('../ajax/get_notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_all_read'
        });
        const data = await res.json();
        if (data.success) {
            loadNotifications();
        }
    } catch (err) {
        console.error('Error marking all as read:', err);
    }
}

// Load dashboard and notifications
document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
    loadNotifications();
    // Check for notifications every 3 seconds for instant updates
    setInterval(loadNotifications, 3000);
});

// Also refresh notifications when page comes back into focus
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        loadNotifications();
    }
});
</script>
</body>
</html>