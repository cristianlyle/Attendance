<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'db.php';
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .sidebar-link {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-link:hover .nav-icon {
            transform: translateY(-4px) scale(1.1);
            text-shadow: 0 4px 8px rgba(255, 255, 255, 0.3);
        }
        
        .sidebar-link.active {
            background-color: transparent;
        }
        
        .sidebar-link.active .nav-icon {
            transform: translateY(-6px) scale(1.15);
            text-shadow: 0 6px 12px rgba(255, 255, 255, 0.4);
        }
        
        .nav-icon {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
        }
        
        .stat-card {
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: #15803D;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] font-sans">

    <div class="flex min-h-screen">
        <!-- ================= SIDEBAR ================= -->
        <aside class="w-64 bg-[#14532D] text-white flex flex-col fixed min-h-screen shadow-lg">
            <div class="p-6">
                <div class="flex items-center justify-center mb-6">
                    <div class="bg-white/10 p-3 rounded-full">
                        <i class='bx bxs-dashboard text-2xl'></i>
                    </div>
                </div>
                <h2 class="text-lg font-semibold text-center">Admin Panel</h2>
                <p class="text-green-200 text-xs text-center mt-1">Attendance System</p>
            </div>
            <nav class="flex-1 px-4 pb-6">
                <ul class="space-y-1">
                    <li>
                        <a href="admin-dashboard.php" class="sidebar-link active flex items-center gap-3 px-4 py-2.5 rounded-lg text-white">
                            <i class='bx bxs-home text-lg nav-icon'></i>
                            <span class="text-sm">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage-user-dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 text-green-100 hover:text-white">
                            <i class='bx bxs-user-detail text-lg nav-icon'></i>
                            <span class="text-sm">Manage Employees</span>
                        </a>
                    </li>
                    <li>
                        <a href="attendance-dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 text-green-100 hover:text-white">
                            <i class='bx bx-calendar-check text-lg nav-icon'></i>
                            <span class="text-sm">Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="qr-token-dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 text-green-100 hover:text-white">
                            <i class='bx bx-qr text-lg nav-icon'></i>
                            <span class="text-sm">QR Tokens</span>
                        </a>
                    </li>
                    <li>
                        <a href="leave-management-dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 text-green-100 hover:text-white">
                            <i class='bx bx-calendar-minus text-lg nav-icon'></i>
                            <span class="text-sm">Leave Management</span>
                            <span id="leaveNotificationBadge" class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full" style="display: none;">0</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="px-4 pb-6">
                <a href="logout.php" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 text-green-100 hover:text-white transition-colors">
                    <i class='bx bxs-log-out text-lg nav-icon'></i>
                    <span class="text-sm">Logout</span>
                </a>
            </div>
        </aside>

        <!-- ================= MAIN CONTENT ================= -->
        <main class="flex-1 ml-64 p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#0F172A] flex items-center gap-3">
                        Admin Dashboard
                    </h1>
                    <div class="flex items-center gap-3 mt-2">
                        <?php
                        $profileImage = get_profile_image($_SESSION['user']['profile_image'] ?? null);
                        ?>
                        <span class="text-[#475569] text-sm">Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                        <span class="px-2.5 py-1 bg-[#DCFCE7] text-[#166534] text-xs font-medium rounded-full">Admin</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-lg px-4 py-2 shadow-sm border border-gray-100 flex items-center gap-2">
                        <i class='bx bx-time-five text-[#94A3B8]'></i>
                        <span id="currentTime" class="text-[#475569] text-sm font-medium">--:--:--</span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Total Users Card -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[#94A3B8] text-sm font-medium uppercase tracking-wide">Total Users</p>
                            <p class="text-3xl font-bold text-[#0F172A] mt-1" id="totalUsers">--</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-xl flex items-center justify-center">
                            <i class='bx bxs-user text-2xl text-[#3B82F6]'></i>
                        </div>
                    </div>
                </div>
                
                <!-- Today's Attendance Card -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[#94A3B8] text-sm font-medium uppercase tracking-wide">Today's Attendance</p>
                            <p class="text-3xl font-bold text-[#0F172A] mt-1" id="todayAttendance">--</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-xl flex items-center justify-center">
                            <i class='bx bx-calendar-check text-2xl text-[#22C55E]'></i>
                        </div>
                    </div>
                </div>
                
                <!-- Tokens Card -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[#94A3B8] text-sm font-medium uppercase tracking-wide">Total Tokens</p>
                            <p class="text-3xl font-bold text-[#0F172A] mt-1" id="totalCount">--</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-xl flex items-center justify-center">
                            <i class='bx bx-qr text-2xl text-[#A855F7]'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= QR GENERATOR ================= -->
            <div class="bg-white mt-5 rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center">
                            <i class='bx bx-qr-scan text-4xl mr-1 text-[#166534]'></i>
                        </div>
                        <h3 class="text-xl font-semibold text-[#0F172A]">Generate QR Code for Attendance</h3>
                    </div>
                <div class="flex flex-col lg:flex-row lg:items-end gap-4 mb-6">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-medium text-[#475569] mb-2">
                            <i class='bx bxs-map-pin mr-1 text-[#22C55E]'></i>Location
                        </label>
                        <select id="qrLocation" name="location"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#166534] focus:border-transparent bg-white text-[#0F172A] text-sm">
                            <option value="Warehouse">Warehouse</option>
                            <option value="Main Building">Main Building</option>
                            <option value="Remote">Remote</option>
                        </select>
                    </div>
                    <button onclick="generateQRCode()"
                        class="btn-primary flex items-center gap-2 px-5 py-2.5 bg-[#166534] hover:bg-[#15803D] text-white rounded-lg font-medium shadow-sm text-sm">
                        <i class='bx bx-qr'></i>
                        Generate QR Code
                    </button>
                    <button onclick="stopQRCode()"
                        class="flex items-center gap-2 px-5 py-2.5 bg-[#EF4444] hover:bg-[#DC2626] text-white rounded-lg font-medium shadow-sm text-sm transition-colors">
                        <i class='bx bx-stop'></i>
                        Stop QR
                    </button>
                </div>

                <div class="flex flex-col lg:flex-row gap-6 items-start">
                    <div class="bg-[#F8FAFC] rounded-lg p-6 flex items-center justify-center border border-gray-100">
                        <canvas id="qrCodeCanvas" class="border border-gray-200 rounded-lg"></canvas>
                    </div>
                    <div class="bg-[#F8FAFC] rounded-lg p-5 flex-1 w-full border border-gray-100">
                        <h3 class="font-semibold text-[#0F172A] mb-4 flex items-center gap-2 text-sm">
                            <i class='bx bx-info-circle text-[#22C55E]'></i>Token Details
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-100">
                                <i class='bx bxs-key text-[#22C55E] text-lg'></i>
                                <div class="flex-1">
                                    <p class="text-xs text-[#94A3B8]">Token</p>
                                    <p id="qrToken" class="font-mono text-xs text-[#0F172A] break-all">--</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-100">
                                <i class='bx bx-time text-[#3B82F6] text-lg'></i>
                                <div class="flex-1">
                                    <p class="text-xs text-[#94A3B8]">Created At</p>
                                    <p id="qrCreated" class="text-sm text-[#0F172A]">--</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-100">
                                <i class='bx bx-timer text-[#F59E0B] text-lg'></i>
                                <div class="flex-1">
                                    <p class="text-xs text-[#94A3B8]">Expires In</p>
                                    <p id="qrExpires" class="text-sm text-[#0F172A]">--</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-100">
                                <i class='bx bxs-map-pin text-[#A855F7] text-lg'></i>
                                <div class="flex-1">
                                    <p class="text-xs text-[#94A3B8]">Location</p>
                                    <p id="qrLocationText" class="text-sm text-[#0F172A]">--</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p id="qrMsg" class="mt-4 font-medium text-[#EF4444] flex items-center gap-2 text-sm">
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
