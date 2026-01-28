<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: login.php");
    exit();
}
require 'db.php';
$vet_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointment History</title>
    <style>
        body { font-family: Arial; background: #f9f9f9; padding: 20px; }
        .card {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        a.back-btn {
            display: inline-block;
            margin-bottom: 20px;
            background: #007bff;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<h2>Your Appointment History</h2>
<a class="back-btn" href="vet_dashboard.php">← Back to Dashboard</a>

<?php
$result = $conn->query("SELECT h.*, u.name FROM appointment_history h JOIN users u ON h.member_id = u.id WHERE h.vet_id = $vet_id ORDER BY h.created_at DESC");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='card'>";
        echo "<strong>Time:</strong> " . date("M d, Y H:i", strtotime($row['slot_datetime'])) . "<br>";
        echo "<strong>Member:</strong> " . htmlspecialchars($row['name']) . "<br>";
        echo "<strong>Pet:</strong> {$row['pet_type']} (Age: {$row['pet_age']})<br>";
        echo "<strong>Symptoms:</strong> ";
        $details = array_filter([$row['detail1'], $row['detail2'], $row['detail3'], $row['detail4'], $row['detail5'], $row['detail6']]);
        echo htmlspecialchars(implode(', ', $details)) . "<br>";
        echo "<strong>Completed At:</strong> " . date("M d, Y H:i", strtotime($row['created_at']));
        echo "</div>";
    }
} else {
    echo "<p>No completed appointments found.</p>";
}
?>

</body>
</html>
