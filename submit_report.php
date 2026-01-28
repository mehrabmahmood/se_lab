<?php
session_start();
require 'db.php'; // Connect to your MySQL DB

// Ensure user is logged in and is a member
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'member') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$description = trim($_POST['description']);
$latitude = trim($_POST['latitude']);
$longitude = trim($_POST['longitude']);

$photo_path = null;

// Check if photo is uploaded
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/';
    $photo_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);

    // Validate file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($photo_ext), $allowed_types)) {
        die("Unsupported file type. Only JPG, PNG, and GIF allowed.");
    }

    // Create upload directory if needed
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Unique file name
    $photo_name = time() . "_" . uniqid() . "." . $photo_ext;
    $target_path = $upload_dir . $photo_name;

    // Move file
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
        $photo_path = $target_path;
    } else {
        die("Failed to upload photo.");
    }
} else {
    die("Photo is required.");
}

// Insert into database
$stmt = $conn->prepare("INSERT INTO reports (user_id, description, latitude, longitude, photo_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("issss", $user_id, $description, $latitude, $longitude, $photo_path);

if ($stmt->execute()) {
    echo "<script>alert('Report submitted successfully!'); window.location='member_dashboard.php';</script>";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
