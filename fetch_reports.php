<?php
// include "db.php";
// session_start();

// // Check if user is logged in and is a volunteer
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
//     http_response_code(403);
//     echo json_encode(["error" => "Access denied"]);
//     exit();
// }

// // Include ID so reports can be updated/deleted

// $query = "SELECT r.id, r.description, r.latitude, r.longitude, r.photo_path, r.created_at, r.status,
//                  r.volunteer_id, u.name AS volunteer_name
//           FROM reports r
//           LEFT JOIN users u ON r.volunteer_id = u.id
//           ORDER BY r.created_at DESC
//           LIMIT 10";
// $result = $conn->query($query);

// if (!$result) {
//     http_response_code(500);
//     echo json_encode(["error" => "Database query failed"]);
//     exit();
// }

// $reports = [];
// while ($row = $result->fetch_assoc()) {
//     $reports[] = $row;
// }

// header('Content-Type: application/json');
// echo json_encode($reports);

// // Close the connection
// $conn->close();
?>
<?php
include "db.php";
session_start();

// Check if user is logged in and is a volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    http_response_code(403);
    echo json_encode(["error" => "Access denied"]);
    exit();
}

// Only fetch reports that are NOT marked as 'saved'
$query = "SELECT r.id, r.description, r.latitude, r.longitude, r.photo_path, r.created_at, r.status,
                 r.volunteer_id, u.name AS volunteer_name
          FROM reports r
          LEFT JOIN users u ON r.volunteer_id = u.id
          WHERE r.status != 'saved'
          ORDER BY r.created_at DESC
          LIMIT 10";

$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => "Database query failed"]);
    exit();
}

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

header('Content-Type: application/json');
echo json_encode($reports);

// Close the connection
$conn->close();
?>
