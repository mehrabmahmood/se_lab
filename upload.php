<?php
require 'config.php';
require_login();

header('Content-Type: application/json');

if ($_POST['action'] === 'upload_issue' && $_SESSION['user_type'] === 'farmer') {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = time() . "_" . basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        $stmt = $pdo->prepare("INSERT INTO issues (farmer_id, image_path, description) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $targetFile, $_POST['description']]);

        $issue_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'issue' => [
                'id' => $issue_id,
                'imagePath' => $targetFile,
                'description' => $_POST['description']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Upload failed']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
}
?>