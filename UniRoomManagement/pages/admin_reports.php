<?php
session_start();
require_once '../includes/auth_functions.php';
// Demo mode - removed authentication requirement
// AuthHelper::requireRole('admin', 'loginstudent.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Room Reports</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin_reports.css">
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
            <li><a href="room_history.php"><i class='bx bx-history'></i> Room History</a></li>
            <li><a href="admin_reports.php" class="active"><i class='bx bx-error'></i> Room Reports</a></li>
            <li style="margin-top: auto;"><a href="../logout.php"><i class='bx bx-log-out'></i> Exit Admin</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header>
            <h1>Room Reports</h1>
        </header>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Reported By</th>
                        <th>Room</th>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody id="reportsBody">
                    <tr><td colspan="6">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </main>

<script>
async function loadReports() {
    const body = document.getElementById('reportsBody');
    body.innerHTML = '<tr><td colspan="6">Loading...</td></tr>';
    try {
        const res = await fetch('../ajax/get_reports.php');
        const data = await res.json();
        if (!data.success) throw new Error('Failed');
        render(data.reports || []);
    } catch (err) {
        body.innerHTML = '<tr><td colspan="6">Failed to load</td></tr>';
    }
}

function render(reports) {
    const body = document.getElementById('reportsBody');
    body.innerHTML = '';
    if (!reports.length) {
        body.innerHTML = '<tr><td colspan="6">No reports yet.</td></tr>';
        return;
    }
    reports.forEach(r => {
        const tr = document.createElement('tr');
        const sevClass = `sev-${r.severity}`;
        tr.innerHTML = `
            <td>${escapeHtml(r.username || '')}</td>
            <td>Room ${escapeHtml(r.room_number || '')}</td>
            <td>${escapeHtml(r.title)}</td>
            <td><span class="badge ${sevClass}">${escapeHtml(r.severity)}</span></td>
            <td>${escapeHtml(r.status)}</td>
            <td>${escapeHtml(r.created_at)}</td>
        `;
        body.appendChild(tr);
    });
}

function escapeHtml(str){
    return String(str || '')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
}

loadReports();
</script>
</body>
</html>
