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
    <title>Attendance Dashboard - Attendance System</title>
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
                        <a href="manage-user-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-green-100 hover:text-white transition-colors">
                            <i class='bx bxs-user-detail text-lg'></i>
                            <span>Manage Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="attendance-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 text-white">
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
                        <i class='bx bx-calendar-check mr-3 text-green-600'></i>Employee Attendance
                    </h1>
                    <p class="text-gray-600 mt-1 flex items-center gap-2">
                        <i class='bx bxs-data text-gray-400'></i>
                        View all employee attendance records
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-xl px-4 py-2 shadow-sm flex items-center gap-2">
                        <i class='bx bx-time-five text-gray-400'></i>
                        <span id="currentTime" class="text-gray-600 font-medium"></span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Records</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1" id="totalRecords">--</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-xl">
                            <i class='bx bx-list-ul text-2xl text-blue-600'></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Today's Check-ins</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1" id="todayCheckins">--</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-xl">
                            <i class='bx bxs-log-in-circle text-2xl text-green-600'></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Today's Check-outs</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1" id="todayCheckouts">--</p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-xl">
                            <i class='bx bxs-log-out-circle text-2xl text-orange-600'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= TABLE ================= -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="bg-purple-100 p-2 rounded-lg">
                            <i class='bx bx-table text-xl text-purple-600'></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Attendance Records</h2>
                    </div>
                    <button onclick="exportCSV()" class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                        <i class='bx bx-download'></i>
                        Export CSV
                    </button>
                </div>
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table id="attendanceTable" class="w-full">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bxs-user mr-1'></i>Employee
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bx-calendar mr-1'></i>Date
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bxs-log-in-circle mr-1 text-green-500'></i>Time In
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bxs-log-out-circle mr-1 text-orange-500'></i>Time Out
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bxs-map-pin mr-1 text-purple-500'></i>Location
                                </th>
                              
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- JS injects rows -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="js/attendance-dashboard.js" defer></script>
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
