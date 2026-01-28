<?php
include "db.php";
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header("Location: login.php");
    exit();
}

$volunteer_id = $_SESSION['user_id'];
$name = htmlspecialchars($_SESSION['user_name']);

$query = "SELECT * FROM reports WHERE status = 'saved' AND saved_by = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>
  <title>Saved Reports by <?= $name ?></title>
  <style>
    .report {
      border: 1px solid #ccc;
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 10px;
    }
    img {
      width: 200px;
      border-radius: 10px;
    }
  </style>
</head>
<body>
  <h2>Reports Saved by <?= $name ?></h2>
  <a href="volunteer_dashboard.php">← Back to Dashboard</a>

  <?php while ($row = $result->fetch_assoc()): ?>
    <div class="report">
      <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
      <p><strong>Location:</strong> Lat: <?= $row['latitude'] ?>, Lng: <?= $row['longitude'] ?></p>
      <img src="<?= htmlspecialchars($row['photo_path']) ?>" alt="Animal Photo" />
      <p><em>Reported at: <?= $row['created_at'] ?></em></p>
    </div>
  <?php endwhile; ?>
</body>
</html>
