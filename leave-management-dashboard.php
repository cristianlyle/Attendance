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
    <title>Leave Management - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
        <!-- Sidebar -->
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
                        <a href="admin-dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 text-green-100 hover:text-white">
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
                        <a href="leave-management-dashboard.php" class="sidebar-link active flex items-center gap-3 px-4 py-2.5 rounded-lg text-white">
                            <i class='bx bx-calendar-minus text-lg nav-icon'></i>
                            <span class="text-sm">Leave Management</span>
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

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#0F172A] flex items-center gap-3">
                        Leave Management
                    </h1>
                    <div class="flex items-center gap-3 mt-2">
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
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[#94A3B8] text-sm font-medium uppercase tracking-wide">Pending</p>
                            <p class="text-3xl font-bold text-[#0F172A] mt-1" id="pendingCount">--</p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-xl flex items-center justify-center">
                            <i class='bx bx-time-five text-2xl text-[#F59E0B]'></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[#94A3B8] text-sm font-medium uppercase tracking-wide">Approved</p>
                            <p class="text-3xl font-bold text-[#0F172A] mt-1" id="approvedCount">--</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-xl flex items-center justify-center">
                            <i class='bx bx-check-circle text-2xl text-[#22C55E]'></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[#94A3B8] text-sm font-medium uppercase tracking-wide">Rejected</p>
                            <p class="text-3xl font-bold text-[#0F172A] mt-1" id="rejectedCount">--</p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-xl flex items-center justify-center">
                            <i class='bx bx-x-circle text-2xl text-[#EF4444]'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <button onclick="filterRequests('pending')" class="filter-btn active px-4 py-2 rounded-lg text-sm font-medium transition-colors bg-[#166534] text-white" data-filter="pending">
                        <i class='bx bx-time-five mr-1'></i> Pending
                    </button>
                    <button onclick="filterRequests('approved')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition-colors bg-[#F1F5F9] text-[#475569] hover:bg-[#E2E8F0]" data-filter="approved">
                        <i class='bx bx-check-circle mr-1'></i> Approved
                    </button>
                    <button onclick="filterRequests('rejected')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition-colors bg-[#F1F5F9] text-[#475569] hover:bg-[#E2E8F0]" data-filter="rejected">
                        <i class='bx bx-x-circle mr-1'></i> Rejected
                    </button>
                    <button onclick="filterRequests('all')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition-colors bg-[#F1F5F9] text-[#475569] hover:bg-[#E2E8F0]" data-filter="all">
                        <i class='bx bx-list-ul mr-1'></i> All
                    </button>
                </div>
            </div>

            <!-- Leave Requests List -->
            <div id="leaveRequestsContainer" class="space-y-4">
                <div class="text-center py-8 text-[#94A3B8]">Loading leave requests...</div>
            </div>
        </main>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-[#0F172A]" id="modalTitle">Review Leave Request</h3>
                <button onclick="closeModal()" class="text-[#94A3B8] hover:text-[#475569]">
                    <i class='bx bx-x text-xl'></i>
                </button>
            </div>
            <div id="modalContent">
                <!-- Modal content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed px-4 py-2.5 rounded-lg shadow-lg transform -translate-y-20 opacity-0 transition-all duration-300 z-50 text-sm" style="top: 100px; right: 20px; max-width: 320px;">
    </div>

    <script src="js/leave-management.js" defer></script>
</body>
</html>
