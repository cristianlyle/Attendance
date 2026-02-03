<?php
require 'db.php';
$error = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user by email
    $users = supabase_get($table, "email=eq.$email");

    if (isset($users[0])) {
        $user = $users[0];

        // Verify hashed password
        if (password_verify($password, $user['password_hash'])) {
            // Save session
            $_SESSION['user'] = [
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'id' => $user['id']
            ];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin-dashboard.php");
                exit();
            } else {
                header("Location: employee-dashboard.php");
                exit();
            }
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class="bg-gradient-to-br from-green-400 to-green-700 min-h-screen flex items-center justify-center font-sans">

    <div class="w-full max-w-md px-4">
        <!-- Logo/Icon Section -->
        <div class="text-center mb-8">
            <div class="bx bxs-dashboard text-5xl text-green-600 inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-4">
            </div>
            <h1 class="text-3xl font-bold text-white">Attendance System</h1>
            <p class="text-green-100 mt-2">Sign in to your account</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-green-800 text-center mb-6">
                <i class='bx bxs-log-in-circle mr-2'></i>Login
            </h2>

            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class='bx bxs-error-circle mr-2'></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-5">
                <!-- Email Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class='bx bxs-envelope mr-1'></i>Email Address
                    </label>
                    <div class="relative">
                        <input type="email" name="email" placeholder="Enter your email" required
                            class="w-full px-4 py-3 pl-12 border border-green-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <i class='bx bxs-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class='bx bxs-lock-alt mr-1'></i>Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password" placeholder="Enter your password" required
                            class="w-full px-4 py-3 pl-12 border border-green-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <i class='bx bxs-lock-alt absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-green-600 transition-colors">
                            <i class='bx bxs-hide' id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Login Button -->
                <button type="submit" name="login"
                    class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3 rounded-lg shadow-lg transform hover:scale-[1.02] transition-all duration-200">
                    <i class='bx bxs-log-in mr-2'></i>Sign In
                </button>
            </form>

            
        </div>

        <!-- Footer -->
        <p class="text-center text-white/70 text-sm mt-6">
            &copy; <?= date('Y') ?> Attendance System. All rights reserved.
        </p>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bxs-hide');
                toggleIcon.classList.add('bxs-show');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bxs-show');
                toggleIcon.classList.add('bxs-hide');
            }
        }
    </script>
</body>
</html>
