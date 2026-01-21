<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $user_type = $_POST['user_type'];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $user_type]);
        header('Location: login.php?msg=registered');
        exit();
    } catch (PDOException $e) {
        echo "<div class='text-red-600 text-center mt-10'>Error: Username or Email already exists!</div>";
    }
}
?>