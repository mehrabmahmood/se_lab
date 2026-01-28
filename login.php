<?php
include "db.php"; 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans flex items-center justify-center min-h-screen">
    <div class="flex w-full max-w-4xl bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Left: Login form -->
        <div class="w-1/2 p-10">
            <h2 class="text-3xl font-bold mb-6">Log In</h2>

            <?php
            if (isset($_POST['login'])) {
                $email = htmlspecialchars($_POST['email']);
                $pass = $_POST['password'];

                // ✅ Query user
                $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email=?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $name, $hash, $role);
                    $stmt->fetch();

                    if (password_verify($pass, $hash)) {
                        // ✅ Save to session
                        $_SESSION['user_id'] = $id;
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_role'] = $role;
                        $_SESSION['email'] = $email;

                        // ✅ Role-based redirect
                        if ($role === 'member') {
                            header("Location: home.html");
                        } elseif ($role === 'volunteer') {
                            header("Location: volunteer_dashboard.php");
                        } elseif ($role === 'vet') {
                            header("Location: vet_dashboard.php");
                        } else {
                            echo '<p class="text-red-500 mb-4">Invalid role!</p>';
                        }
                        exit();
                    } else {
                        echo '<p class="text-red-500 mb-4">Invalid password!</p>';
                    }
                } else {
                    echo '<p class="text-red-500 mb-4">Email not found!</p>';
                }
                $stmt->close();
            }
            ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="email" class="block text-gray-700">Email Address</label>
                    <input type="email" id="email" name="email" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="password" class="block text-gray-700">Password</label>
                    <input type="password" id="password" name="password" class="w-full p-2 border rounded" required>
                </div>
                <button type="submit" name="login" class="w-full bg-orange-500 text-white p-2 rounded-lg hover:bg-orange-600">Log In</button>
            </form>

            <p class="mt-4 text-center">
                Don't have an account?
                <a href="register.php" class="text-orange-500 hover:underline">Register here</a>
            </p>
        </div>

        <!-- Right: Branding -->
        <div class="w-1/2 bg-orange-500 text-white p-10 flex flex-col items-center justify-center">
            <h2 class="text-2xl font-semibold mb-4">Health care for every crop</h2>
            <img src="./planting.png" alt="Pet Illustration" class="mb-4">
            <h1 class="text-3xl font-bold">CropWise</h1>
        </div>
    </div>
</body>
</html>
