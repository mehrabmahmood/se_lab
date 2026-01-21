<!-- register file included -->
 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Agri Consultancy</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-10 rounded-xl shadow-2xl w-full max-w-md">
        <h2 class="text-3xl font-bold mb-8 text-center text-green-700">Register</h2>
        <form action="register_process.php" method="POST" class="space-y-5">
            <input type="text" name="username" placeholder="Username" required class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none">
            <input type="email" name="email" placeholder="Email" required class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none">
            <input type="password" name="password" placeholder="Password" required class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none">
            <select name="user_type" required class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none">
                <option value="" disabled selected>Select User Type</option>
                <option value="farmer">Farmer</option>
                <option value="expert">Agri Expert</option>
            </select>
            <button type="submit" class="w-full bg-green-600 text-white py-4 rounded-lg hover:bg-green-700 font-semibold text-lg">Register</button>
        </form>
        <p class="mt-6 text-center text-gray-600">Already have an account? <a href="login.php" class="text-green-600 font-bold hover:underline">Login</a></p>
    </div>
</body>
</html>