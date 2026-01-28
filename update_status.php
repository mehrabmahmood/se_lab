<?php
$conn = new mysqli("localhost", "root", "", "pet_health");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$id = $_POST['id'];
$action = $_POST['action'];

if ($action === 'done') {
  $stmt = $conn->prepare("UPDATE appointments SET status='Completed' WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  echo 'success';
} elseif ($action === 'view') {
  // No update needed, but we return success so that JS doesn't break.
  echo 'success';
} else {
  echo 'error';
}
?>
