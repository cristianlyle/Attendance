<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'db.php';
$table = "users";

/* Fetch users for table */
$users = supabase_get($table, "select=id,name,email,role,status,created_at&order=created_at.desc");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 font-sans">

    <div class="flex min-h-screen">
        <!-- ================= SIDEBAR ================= -->
        <aside class="w-64 bg-gradient-to-b from-green-700 to-green-800 text-white flex flex-col fixed min-h-screen shadow-xl">
            <div class="p-6">
                <div class="flex items-center justify-center mb-6">
                    <div class="bg-white/20 p-3 rounded-full">
                        <i class='bx bxs-dashboard text-3xl'></i>
                    </div>
                </div>
                <h2 class="text-xl font-bold text-center">Admin Panel</h2>
                <p class="text-green-200 text-sm text-center mt-1">Attendance System</p>
            </div>
            <nav class="flex-1 px-4 pb-6">
                <ul class="space-y-2">
                    <li>
                        <a href="admin-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-green-100 hover:text-white transition-colors">
                            <i class='bx bxs-home text-lg'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage-user-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 text-white">
                            <i class='bx bxs-user-detail text-lg'></i>
                            <span>Manage Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="attendance-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-green-100 hover:text-white transition-colors">
                            <i class='bx bx-calendar-check text-lg'></i>
                            <span>Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="qr-token-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-green-100 hover:text-white transition-colors">
                            <i class='bx bx-qr text-lg'></i>
                            <span>QR Tokens</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="px-4 pb-6">
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/80 hover:bg-red-500 text-white transition-colors">
                    <i class='bx bxs-log-out text-lg'></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- ================= MAIN CONTENT ================= -->
        <main class="flex-1 ml-64 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class='bx bxs-user-detail mr-3 text-green-600'></i>Manage Users
                    </h1>
                    <p class="text-gray-600 mt-1 flex items-center gap-2">
                        <i class='bx bxs-group text-gray-400'></i>
                        View and manage system users
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-xl px-4 py-2 shadow-sm flex items-center gap-2">
                        <i class='bx bx-time-five text-gray-400'></i>
                        <span id="currentTime" class="text-gray-600 font-medium"></span>
                    </div>
                    <button onclick="openAddUserModal()" 
                        class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all">
                        <i class='bx bxs-user-plus'></i>
                        Add User
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Users</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1"><?= count($users) ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-xl">
                            <i class='bx bxs-user text-2xl text-blue-600'></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Admin</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1"><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-xl">
                            <i class='bx bxs-crown text-2xl text-purple-600'></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Employees</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1"><?= count(array_filter($users, fn($u) => $u['role'] === 'employee')) ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-xl">
                            <i class='bx bxs-user-badge text-2xl text-green-600'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= USERS TABLE ================= -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="bg-orange-100 p-2 rounded-lg">
                            <i class='bx bx-table text-xl text-orange-600'></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">All Users</h2>
                    </div>
                </div>
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bxs-user mr-1'></i>Name
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bxs-envelope mr-1'></i>Email
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bxs-id-card mr-1'></i>Role
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bxs-check-circle mr-1'></i>Status
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bx-calendar mr-1'></i>Created
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 <?= $user['role'] === 'admin' ? 'bg-purple-100' : 'bg-green-100' ?> rounded-full flex items-center justify-center">
                                                <span class="<?= $user['role'] === 'admin' ? 'text-purple-600' : 'text-green-600' ?> font-semibold">
                                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                                </span>
                                            </div>
                                            <span class="font-medium text-gray-800"><?= htmlspecialchars($user['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <i class='bx bxs-envelope text-gray-400'></i>
                                            <?= htmlspecialchars($user['email']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' ?> rounded-full text-sm font-medium">
                                            <i class='<?= $user['role'] === 'admin' ? 'bxs-crown' : 'bxs-user-badge' ?>'></i>
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 <?= $user['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> rounded-full text-sm font-medium">
                                            <i class='<?= $user['status'] === 'active' ? 'bxs-check-circle' : 'bxs-x-circle' ?>'></i>
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        <div class="flex items-center gap-2">
                                            <i class='bx bx-calendar text-gray-400'></i>
                                            <?= date("M d, Y", strtotime($user['created_at'])) ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class='bx bxs-user-x text-5xl text-gray-300 mb-3'></i>
                                            <p class="text-gray-500 text-lg">No users found</p>
                                            <p class="text-gray-400 text-sm">Click "Add User" to create a new user</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ================= ADD USER MODAL ================= -->
    <div id="addUserModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 mx-4 transform transition-all">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="bg-green-100 p-2 rounded-lg">
                        <i class='bx bxs-user-plus text-xl text-green-600'></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">Add New User</h2>
                </div>
                <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>

            <form method="POST" action="register.php" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class='bx bxs-user mr-1'></i>Full Name
                    </label>
                    <input name="name" required placeholder="Enter full name"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class='bx bxs-envelope mr-1'></i>Email Address
                    </label>
                    <input name="email" type="email" required placeholder="Enter email"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class='bx bxs-lock-alt mr-1'></i>Password
                    </label>
                    <input id="password" name="password" type="password" required placeholder="Create password" minlength="6"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class='bx bxs-lock-alt mr-1'></i>Confirm Password
                    </label>
                    <input id="confirm_password" name="confirm_password" type="password" required placeholder="Confirm password"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    <p id="passwordMessage" class="text-sm mt-1"></p>
                </div>

                <div class="flex items-center gap-2 text-sm">
                    <input type="checkbox" onclick="togglePassword()" id="showPassword" class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                    <label for="showPassword" class="text-gray-600">Show password</label>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeAddUserModal()"
                        class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                        Cancel
                    </button>
                    <button name="register" type="submit" id="registerBtn" disabled
                        class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl font-medium shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class='bx bxs-user-plus mr-1'></i>Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= SUCCESS MODAL ================= -->
    <?php if (isset($_SESSION['success'])): ?>
    <div id="successModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 mx-4 text-center">
            <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class='bx bxs-check-circle text-4xl text-green-600'></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Success!</h2>
            <p class="text-gray-600 mb-6"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
            <button onclick="closeSuccessModal()"
                class="w-full px-5 py-2.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl font-medium transition-all">
                OK
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- ================= ERROR MODAL ================= -->
    <?php if (isset($_SESSION['error'])): ?>
    <div id="errorModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 mx-4 text-center">
            <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class='bx bxs-error-circle text-4xl text-red-600'></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Error</h2>
            <p class="text-gray-600 mb-6"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
            <button onclick="closeErrorModal()"
                class="w-full px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-all">
                OK
            </button>
        </div>
    </div>
    <?php endif; ?>

    <script src="js/manage-user.js"></script>
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>
