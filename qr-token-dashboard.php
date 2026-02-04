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
    <title>QR Tokens Dashboard - Attendance System</title>
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
                        <a href="attendance-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-green-100 hover:text-white transition-colors">
                            <i class='bx bx-calendar-check text-lg'></i>
                            <span>Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="qr-token-dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 text-white">
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
                        <i class='bx bx-qr mr-3 text-green-600'></i>QR Tokens
                    </h1>
                    <p class="text-gray-600 mt-1 flex items-center gap-2">
                        <i class='bx bxs-barcode text-gray-400'></i>
                        View all generated QR codes for attendance
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-xl px-4 py-2 shadow-sm flex items-center gap-2">
                        <i class='bx bx-time-five text-gray-400'></i>
                        <span id="currentTime" class="text-gray-600 font-medium"></span>
                    </div>
                    <a href="admin-dashboard.php" class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all">
                        <i class='bx bxs-plus-circle'></i>
                        Generate New QR
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Active Tokens</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1" id="activeCount">--</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-xl">
                            <i class='bx bx-check-circle text-2xl text-green-600'></i>
                        </div>
                    </div>
                </div>
               
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Expired Tokens</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1" id="expiredCount">--</p>
                        </div>
                        <div class="bg-gray-100 p-3 rounded-xl">
                            <i class='bx bx-x-circle text-2xl text-gray-500'></i>
                        </div>
                    </div>
                </div>
                 <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Tokens</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1" id="totalCount">--</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-xl">
                            <i class='bx bx-qr text-2xl text-blue-600'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= QR TABLE ================= -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="bg-purple-100 p-2 rounded-lg">
                            <i class='bx bx-table text-xl text-purple-600'></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">All QR Tokens</h2>
                    </div>
                </div>
                <div class="overflow-x-auto max-h-[700px] overflow-y-auto">
                    <table id="qrTable" class="w-full">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bxs-key mr-1'></i>Token
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bxs-map-pin mr-1'></i>Location
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bx-time-five mr-1'></i>Created At
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bx-timer mr-1'></i>Expires At
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <i class='bx bx-calendar-x mr-1'></i>Expired
                                </th>
                            
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- JS will inject rows here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ================= TOKEN DETAILS MODAL ================= -->
    <div id="tokenModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 mx-4 transform transition-all">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-gray-800">Token Details</h2>
                </div>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>

            <div class="space-y-4">
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-500 mb-1">Token</p>
                    <p id="modalToken" class="font-mono text-sm text-gray-800 break-all"></p>
                </div>
                <div class="flex gap-4">
                    <div class="flex-1 bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Location</p>
                        <p id="modalLocation" class="font-medium text-gray-800"></p>
                    </div>
                    <div class="flex-1 bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Status</p>
                        <p id="modalStatus" class="font-medium"></p>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-500 mb-1">Created At</p>
                    <p id="modalCreated" class="font-medium text-gray-800"></p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-500 mb-1">Expires At</p>
                    <p id="modalExpires" class="font-medium text-gray-800"></p>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeModal()"
                    class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                    <i class='bx bx-x mr-1'></i>Close
                </button>
                <button onclick="deleteToken()"
                    class="px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl font-medium shadow-lg transition-all">
                    <i class='bx bx-trash mr-1'></i>Delete
                </button>
            </div>
        </div>
    </div>

    <script src="js/qr-token-dashboard.js" defer></script>
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
