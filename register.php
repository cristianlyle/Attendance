<?php
session_start();
require 'db.php';

$table = "users";
$error = '';
$success = '';

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'] ?? 'employee';
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else { 
        $existing = supabase_get($table, "email=eq.$email&limit=1");

        if (!empty($existing)) {
            $error = "Email already exists.";
        } else { 
            $data = [
                "name" => $name,
                "email" => $email,
                "role" => $role,
                "password_hash" => password_hash($password, PASSWORD_DEFAULT),
                "status" => "active"
            ];
            
            // Handle profile image upload to Supabase Storage
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = supabase_upload_image($_FILES['profile_image']);
                
                if (isset($uploadResult['path'])) {
                    $data['profile_image'] = $uploadResult['path'];
                }
            }
            
            supabase_post($table, $data);

            $success = "Account created successfully! Please login.";
        }
        header("Location: manage-user-dashboard.php");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class="bg-gradient-to-br from-green-400 to-green-700 min-h-screen flex items-center justify-center font-sans py-8 px-4">

    <div class="w-full max-w-md">
        <!-- Logo/Icon Section -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-lg mb-3">
                <i class='bx bxs-user-plus text-4xl text-green-600'></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Create Account</h1>
            <p class="text-green-100 text-sm mt-1">Join the attendance system</p>
        </div>

        <!-- Register Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-6">
            <h2 class="text-xl font-bold text-green-800 text-center mb-6">
                <i class='bx bxs-edit mr-2'></i>Register
            </h2>

            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center">
                    <i class='bx bxs-error-circle mr-2'></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center">
                    <i class='bx bxs-check-circle mr-2'></i>
                    <?= htmlspecialchars($success) ?>
                </div>
                <div class="text-center mt-4">
                    <a href="login.php" class="inline-flex items-center px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                        <i class='bx bxs-log-in mr-2'></i>Go to Login
                    </a>
                </div>
            <?php else: ?>

            <form method="POST" action="" class="space-y-4">
                <!-- Name Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class='bx bxs-user mr-1'></i>Full Name
                    </label>
                    <div class="relative">
                        <input type="text" name="name" placeholder="Enter your full name" required
                            class="w-full px-4 py-3 pl-12 border border-green-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <i class='bx bxs-user absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                    </div>
                </div>

                <!-- Email Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class='bx bxs-envelope mr-1'></i>Email Address
                    </label>
                    <div class="relative">
                        <input type="email" name="email" placeholder="Enter your email" required
                            class="w-full px-4 py-3 pl-12 border border-green-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <i class='bx bxs-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                    </div>
                </div>

                <!-- Role Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class='bx bxs-id-card mr-1'></i>Role
                    </label>
                    <div class="relative">
                        <select name="role" required
                            class="w-full px-4 py-3 pl-12 border border-green-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all appearance-none bg-white">
                            <option value="employee">Employee</option>
                            <option value="admin">Admin</option>
                        </select>
                        <i class='bx bxs-id-card absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                        <i class='bx bxs-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                    </div>
                </div>

                <!-- Profile Image Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class='bx bxs-image mr-1'></i>Profile Picture
                    </label>
                    <div class="flex items-center gap-3">
                        <div class="w-16 h-16 rounded-full bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden" id="imagePreview">
                            <i class='bx bxs-camera text-xl text-gray-400'></i>
                        </div>
                        <input type="file" name="profile_image" accept="image/*" onchange="previewImage(event)" class="flex-1 text-sm">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Optional: Upload a profile picture</p>
                </div>

                <!-- Password Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class='bx bxs-lock-alt mr-1'></i>Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password" placeholder="Create a password" required minlength="6"
                            class="w-full px-4 py-3 pl-12 border border-green-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <i class='bx bxs-lock-alt absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class='bx bxs-lock-alt mr-1'></i>Confirm Password
                    </label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required
                            class="w-full px-4 py-3 pl-12 border border-green-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <i class='bx bxs-lock-alt absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                    </div>
                    <p id="passwordMessage" class="text-sm mt-1"></p>
                </div>

                <!-- Show Password -->
                <div class="flex items-center gap-2">
                    <input type="checkbox" onclick="togglePassword()" id="showPassword" class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                    <label for="showPassword" class="text-sm text-gray-600">Show password</label>
                </div>

                <!-- Register Button -->
                <button type="submit" name="register" id="registerBtn" disabled
                    class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3 rounded-lg shadow-lg transform hover:scale-[1.02] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class='bx bxs-user-plus mr-2'></i>Create Account
                </button>
            </form>

            <p class="text-center mt-4 text-gray-600 text-sm">
                Already have an account?
                <a href="login.php" class="text-green-600 hover:text-green-700 font-semibold hover:underline">
                    <i class='bx bxs-log-in mr-1'></i>Login here
                </a>
            </p>

            <?php endif; ?>
        </div>

        <!-- Footer -->
        <p class="text-center text-white/70 text-xs mt-4">
            &copy; <?= date('Y') ?> Attendance System. All rights reserved.
        </p>
    </div>

    <script src="js/password-match.js"></script>
    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                preview.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
            };
            reader.readAsDataURL(file);
        }
    </script>
       
</body>
</html>
