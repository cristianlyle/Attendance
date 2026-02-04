<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
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
                        <a href="admin-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 text-white">
                            <i class='bx bxs-home text-lg'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage-user-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-green-100 hover:text-white transition-colors">
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
                        <i class='bx bxs-dashboard mr-3 text-green-600'></i>Admin Dashboard
                    </h1>
                    <p class="text-gray-600 mt-1 flex items-center gap-2">
                        <i class='bx bxs-user-circle text-green-500'></i>
                        Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?>
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Admin</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-xl px-4 py-2 shadow-sm flex items-center gap-2">
                        <i class='bx bx-time-five text-gray-400'></i>
                        <span id="currentTime" class="text-gray-600 font-medium"></span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Users</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1" id="totalUsers">--</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-xl">
                            <i class='bx bxs-user text-2xl text-blue-600'></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Today's Attendance</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1" id="todayAttendance">--</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-xl">
                            <i class='bx bx-calendar-check text-2xl text-green-600'></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Tokens</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1" id="totalCount">--</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-xl">
                            <i class='bx bx-qr text-2xl text-purple-600'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= QR GENERATOR ================= -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 rounded-xl">
                        <i class='bx bxs-qr-code text-xl text-green-600'></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">Generate QR Code for Attendance</h2>
                </div>

                <div class="flex flex-col lg:flex-row lg:items-end gap-4 mb-6">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bxs-map-pin mr-1 text-green-500'></i>Location
                        </label>
                        <select id="qrLocation" name="location"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white">
                            <option value="Warehouse">Warehouse</option>
                            <option value="Main Building">Main Building</option>
                            <option value="Remote">Remote</option>
                        </select>
                    </div>
                    <button onclick="generateQRCode()"
                        class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all">
                        <i class='bx bx-qr'></i>
                        Generate QR Code
                    </button>
                    <button onclick="stopQRCode()"
                        class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all">
                        <i class='bx bx-stop'></i>
                        Stop QR
                    </button>
                </div>

                <div class="flex flex-col lg:flex-row gap-6 items-center">
                    <div class="bg-gray-50 rounded-xl p-6 flex items-center justify-center">
                        <canvas id="qrCodeCanvas" class="border-2 border-gray-200 rounded-lg"></canvas>
                    </div>
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 flex-1 w-full">
                        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class='bx bx-info-circle text-green-500'></i>Token Details
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg shadow-sm">
                                <i class='bx bxs-key text-green-500 text-lg'></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500">Token</p>
                                    <p id="qrToken" class="font-mono font-medium text-gray-800 break-all">--</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg shadow-sm">
                                <i class='bx bx-time text-blue-500 text-lg'></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500">Created At</p>
                                    <p id="qrCreated" class="font-medium text-gray-800">--</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg shadow-sm">
                                <i class='bx bx-timer text-orange-500 text-lg'></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500">Expires In</p>
                                    <p id="qrExpires" class="font-medium text-gray-800">--</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg shadow-sm">
                                <i class='bx bxs-map-pin text-purple-500 text-lg'></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500">Location</p>
                                    <p id="qrLocationText" class="font-medium text-gray-800">--</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p id="qrMsg" class="mt-4 font-semibold text-red-500 flex items-center gap-2">
                   
                    <span></span>
                </p>
            </div>
        </main>
    </div>

    <script src="js/admin-dashboard.js" defer></script>
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
