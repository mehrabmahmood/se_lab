<?php
session_start();
require 'db.php';

// Make sure the user is logged in and is a member
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'member') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

$member_id = $_SESSION['user_id'];

// Prepare and execute the query securely
$stmt = $conn->prepare("
    SELECT s.id AS slot_id, u.name AS vet_name
    FROM slot_requests r
    JOIN vet_slots s ON r.slot_id = s.id
    JOIN users u ON s.vet_id = u.id
    WHERE r.member_id = ?
      AND r.status = 'accepted'
      AND s.status = 'booked'
      AND TIMESTAMPDIFF(MINUTE, s.slot_datetime, NOW()) BETWEEN -1 AND 1
");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

$live = [];
while ($row = $result->fetch_assoc()) {
    $live[] = [
        'slot_id' => $row['slot_id'],
        'vet_name' => $row['vet_name']
    ];
}

header('Content-Type: application/json');
echo json_encode($live);
