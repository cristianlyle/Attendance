<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Employee Dashboard - Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/employee-dashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-green-100 font-sans min-h-screen">

    <!-- Mobile Header -->
    <div class="lg:hidden bg-green-600 text-white p-4 flex justify-between items-center fixed top-0 left-0 right-0 z-50">
              <button onclick="toggleSidebar()" class="text-white">
            <i class='bx bx-menu text-2xl'></i>
        </button>
    <div class=" items-center gap-2">
            <span class="font-bold text-lg">Employee Dashboard</span>
        </div>
    </div>

    <div class="flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar fixed inset-y-0 left-0 w-52 bg-green-700 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50">
            <div class="p-6 pt-16 lg:pt-6">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                        <i class='bx bxs-user-badge text-green-600 text-xl'></i>
                    </div>
                    <div>
                        <p class="text-xs text-green-200">Employee Panel</p>
                    </div>
                </div>
                
                <nav class="space-y-2">
                    <a href="#" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                        <i class='bx bx-qr-scan text-xl'></i>
                        <span>Scan</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                        <i class='bx bx-calendar-check text-xl'></i>
                        <span>My Attendance</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                        <i class='bx bx-history text-xl'></i>
                        <span>History</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                        <i class='bx bx-user text-xl'></i>
                        <span>Profile</span>
                    </a>
                </nav>
            </div>
            
            <div class="absolute bottom-0 left-0 right-0 p-6 border-t border-green-600">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                        <i class='bx bxs-user text-xl'></i>
                    </div>
                    <div>
                        <p class="font-medium text-sm"><?= htmlspecialchars($_SESSION['user']['name']) ?></p>
                        <p class="text-xs text-green-200">Employee</p>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center gap-2 text-green-200 hover:text-white transition-colors">
                    <i class='bx bx-log-out'></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="lg:ml-50 p-4 lg:p-8 mt-16 lg:mt-0 w-full">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 mb-6 text-white shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 p-3 rounded-full">
                        <i class='bx bxs-user-circle text-2xl'></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold">Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?>!</h2>
                        <p class="text-green-100 text-sm">Scan QR to mark attendance</p>
                    </div>
                </div>
            </div>

            <!-- QR Scanner Section -->
            <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="bg-green-100 p-1.5 rounded-lg">
                        <i class='bx bx-qr-scan text-lg text-green-600'></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">QR Scanner</h3>
                </div>

        

                <!-- Video Preview -->
                <div class="scanner-container" id="scannerContainer">
                    <video id="qrVideo" autoplay playsinline muted></video>
                    <div id="scannerOverlay" class="scanner-overlay" style="display: none;"></div>
                    <div id="scanMessage" class="absolute bottom-2 left-0 right-0 text-center text-white text-sm bg-black/50 py-1" style="display: none;">
                        Scanning...
                    </div>
                </div>
        <!-- Scanner Controls -->
                <div class="justify-center gap-2 mt-4 flex">
                    <button id="startScanBtn" onclick="toggleScanner()"
                        class="w-32 flex items-center justify-center gap-2 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-all shadow-md">
                        <i class='bx bx-qr-scan'></i>
                        <span id="scanBtnText">Scan</span>
                    </button>
                </div>
                <!-- Status Message -->
                <div id="attendanceMsg" class="mt-4 p-3 rounded-lg text-center text-sm font-medium"></div>
            </div>

            <!-- Today's Status -->
            <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="bg-blue-100 p-1.5 rounded-lg">
                        <i class='bx bx-time-five text-lg text-blue-600'></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Today's Status</h3>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <i class='bx bxs-log-in-circle text-2xl text-green-600 mb-1'></i>
                        <p class="text-xs text-gray-600">Time In</p>
                        <p id="timeIn" class="text-lg font-bold text-gray-800">--:--</p>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-3 text-center">
                        <i class='bx bxs-log-out-circle text-2xl text-orange-600 mb-1'></i>
                        <p class="text-xs text-gray-600">Time Out</p>
                        <p id="timeOut" class="text-lg font-bold text-gray-800">--:--</p>
                    </div>
                </div>

                <!-- Break Times -->
                <div class="flex items-center gap-2 mt-3">
                    <div class="bg-yellow-100 text-2xl p-1.5 rounded-lg">
                        <i class='bx bx-pause-circle text-lg text-yellow-600 mb-1'></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Break Time</h3>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div class="bg-blue-50 rounded-lg text-center">
                        <i class='bx bx-restaurant text-2xl text-blue-600 mb-1'></i>
                        <p class="text-xs text-gray-600">Break In</p>
                        <p id="lunchIn" class="text-lg font-bold text-gray-800">--:--</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg text-center">
                        <i class='bx bx-coffee text-2xl text-yellow-600 mb-1'></i>
                        <p class="text-xs text-gray-600">Break Out</p>
                        <p id="lunchOut" class="text-lg font-bold text-gray-800">--:--</p>
                    </div>
                </div>

                <div class="mt-3 bg-gray-50 rounded-lg p-3">
                    <div class="flex items-center gap-2">
                        <i class='bx bxs-map-pin text-green-600'></i>
                        <span class="text-sm text-gray-600">Location:</span>
                        <span id="todayLocation" class="font-medium text-gray-800">Not marked</span>
                    </div>
                </div>
            </div>

            <!-- Attendance History -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="bg-purple-100 p-1.5 rounded-lg">
                            <i class='bx bx-history text-lg text-purple-600'></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">History</h3>
                    </div>
                    <button onclick="exportCSV()" class="py-1.5 px-3 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition-colors">
                        <i class='bx bx-download'></i>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table id="attendanceTable" class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Time In</th> 
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Break In</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Break Out</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Time Out</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Location</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Overlay for mobile -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 px-4 py-3 rounded-xl shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 z-50 text-sm">
    </div>

    <!-- HTTPS Warning -->
    <div id="httpsWarning" class="fixed top-20 left-0 right-0 bg-yellow-100 border-b border-yellow-400 px-4 py-2 text-center text-yellow-800 text-sm" style="display: none;">
        <i class='bx bx-error-circle mr-1'></i>
        Camera requires HTTPS. Use a secure connection or localhost.
    </div>

    <!-- PASS EMPLOYEE ID TO JS -->
    <script>
        const EMPLOYEE_ID = "<?= $_SESSION['user']['id'] ?>";
        
        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
        
        // Check if HTTPS
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            document.getElementById('httpsWarning').style.display = 'block';
        }
    </script>
    <script src="js/employee-dashboard.js" defer></script>
</body>
</html>
