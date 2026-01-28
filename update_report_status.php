<?php
#include "db.php";
#session_start();

#if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
#    http_response_code(403);
#    exit(json_encode(['error' => 'Unauthorized']));
#}

// $data = json_decode(file_get_contents('php://input'), true);
// $report_id = intval($data['report_id'] ?? 0);
// $status = $data['status'] ?? '';

// if (!in_array($status, ['on_the_way', 'saved'])) {
//     http_response_code(400);
//     exit(json_encode(['error' => 'Invalid status']));
// }

// if ($status === 'on_the_way') {
//     $stmt = $conn->prepare("UPDATE reports SET status = ?, volunteer_id = ? WHERE id = ?");
//     $stmt->bind_param("sii", $status, $_SESSION['user_id'], $report_id);
// } else { // saved
//     $stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
//     $stmt->bind_param("i", $report_id);
// }

// if ($stmt->execute()) {
//     echo json_encode(['success' => true]);
// } else {
//     http_response_code(500);
//     echo json_encode(['error' => 'Update failed']);
// }





include "db.php";
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true);
$report_id = intval($data['report_id'] ?? 0);
$status = $data['status'] ?? '';

if (!in_array($status, ['on_the_way', 'saved'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid status']));
}

if ($status === 'on_the_way') {
    $stmt = $conn->prepare("UPDATE reports SET status = ?, volunteer_id = ? WHERE id = ?");
    $stmt->bind_param("sii", $status, $_SESSION['user_id'], $report_id);
} else { // saved
    $stmt = $conn->prepare("UPDATE reports SET status = ?, saved_by = ? WHERE id = ?");
    $stmt->bind_param("sii", $status, $_SESSION['user_id'], $report_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed']);
}
