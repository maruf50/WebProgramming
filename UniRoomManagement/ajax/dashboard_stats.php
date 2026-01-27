<?php
require_once '../includes/auth_functions.php';
require_once '../database/db.php';
session_start();

AuthHelper::requireLogin('loginstudent.php');

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'student';
$today = date('Y-m-d');

$conn = getDBConnection();

function fetchCount(mysqli $conn, string $sql, string $types = '', array $params = []): int {
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

if ($userRole === 'admin') {
    $pendingCount = fetchCount($conn,
        "SELECT COUNT(*) AS total FROM bookings WHERE status = 'pending' AND booking_date >= ?",
        's', [$today]
    );
    $upcomingCount = fetchCount($conn,
        "SELECT COUNT(*) AS total FROM bookings WHERE status = 'approved' AND booking_date >= ?",
        's', [$today]
    );
    $approvedCount = fetchCount($conn,
        "SELECT COUNT(*) AS total FROM bookings WHERE status = 'approved'"
    );
} else {
    $pendingCount = fetchCount($conn,
        "SELECT COUNT(*) AS total FROM bookings WHERE user_id = ? AND status = 'pending' AND booking_date >= ?",
        'is', [$userId, $today]
    );
    $upcomingCount = fetchCount($conn,
        "SELECT COUNT(*) AS total FROM bookings WHERE user_id = ? AND status = 'approved' AND booking_date >= ?",
        'is', [$userId, $today]
    );
    $approvedCount = fetchCount($conn,
        "SELECT COUNT(*) AS total FROM bookings WHERE user_id = ? AND status = 'approved'",
        'i', [$userId]
    );
}

// Upcoming events (next 30 days)
$eventsStmt = $conn->prepare(
    "SELECT id, title, description, start_at, end_at, location, audience
     FROM campus_events
     WHERE is_published = 1 AND start_at >= NOW() AND start_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)
     ORDER BY start_at ASC
     LIMIT 10"
);
$eventsStmt->execute();
$eventsResult = $eventsStmt->get_result();
$events = [];
while ($row = $eventsResult->fetch_assoc()) {
    $row['start_at_iso'] = date('c', strtotime($row['start_at']));
    $row['end_at_iso'] = date('c', strtotime($row['end_at']));
    $events[] = $row;
}
$eventsStmt->close();

closeDBConnection($conn);

echo json_encode([
    'success' => true,
    'pending' => $pendingCount,
    'upcoming' => $upcomingCount,
    'approved' => $approvedCount,
    'events' => $events
]);
