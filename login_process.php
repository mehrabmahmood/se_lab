<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];

        if ($user['user_type'] === 'farmer') {
            header('Location: farmer.php');
        } else {
            header('Location: expert.php');
        }
        exit();
    } else {
        echo "<div class='text-red-600 text-center mt-10 text-xl'>Invalid email or password!</div>";
        echo "<p class='text-center mt-4'><a href='login.php' class='text-green-600 underline'>Back to Login</a></p>";
    }
}
?>