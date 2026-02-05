<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}

require 'db.php';
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
        <div class="flex items-center gap-2">
            <span class="font-bold text-lg" id="mobileHeaderTitle">Employee Dashboard</span>
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
                    <a href="#" onclick="showSection('scan', this)" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                        <i class='bx bx-qr-scan text-xl'></i>
                        <span>Scan</span>
                    </a>
                    <a href="#" onclick="showSection('my-attendance', this)" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                        <i class='bx bx-calendar-check text-xl'></i>
                        <span>My Attendance</span>
                    </a>
                    <a href="#" onclick="showSection('history', this)" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                        <i class='bx bx-history text-xl'></i>
                        <span>History</span>
                    </a>
                </nav>
            </div>
            
            <div class="absolute bottom-0 left-0 right-0 p-6 border-t border-green-600">
                <div class="flex items-center gap-3 mb-4">
                    <?php
                    $profileImage = get_profile_image($_SESSION['user']['profile_image'] ?? null);
                    ?>
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
        <main class="lg:ml-52 p-4 lg:p-8 mt-16 lg:mt-0 w-full min-h-screen">
            
            <!-- Scan Section -->
            <div id="section-scan" class="dashboard-section active">
                <!-- Welcome Section -->
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 mb-6 text-white shadow-lg">
                    <div class="flex items-center gap-3">
                        <?php
                        $profileImage = get_profile_image($_SESSION['user']['profile_image'] ?? null);
                        ?>
                        <div>
                            <h2 class="text-lg font-bold"><?= htmlspecialchars($_SESSION['user']['name']) ?>!</h2>
                            <p class="text-green-100 text-sm">Scan QR to mark attendance</p>
                        </div>
                    </div>
                </div>

                <!-- QR Scanner Section -->
                <div class="bg-white rounded-xl shadow-lg p-4 mb-6 relative">
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
            </div>

            <!-- My Attendance Section -->
            <div id="section-my-attendance" class="dashboard-section">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="bg-blue-100 p-1.5 rounded-lg">
                            <i class='bx bx-calendar-check text-lg text-blue-600'></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">My Attendance</h3>
                    </div>

                    <!-- Today's Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-green-400 to-green-500 rounded-xl p-4 text-white">
                            <i class='bx bxs-log-in-circle text-2xl mb-2'></i>
                            <p class="text-xs text-green-100">Time In</p>
                            <p id="ma-timeIn" class="text-xl font-bold">--:--</p>
                        </div>
                        <div class="bg-gradient-to-br from-orange-400 to-orange-500 rounded-xl p-4 text-white">
                            <i class='bx bxs-log-out-circle text-2xl mb-2'></i>
                            <p class="text-xs text-orange-100">Time Out</p>
                            <p id="ma-timeOut" class="text-xl font-bold">--:--</p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-400 to-blue-500 rounded-xl p-4 text-white">
                            <i class='bx bx-restaurant text-2xl mb-2'></i>
                            <p class="text-xs text-blue-100">Break In</p>
                            <p id="ma-lunchIn" class="text-xl font-bold">--:--</p>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-xl p-4 text-white">
                            <i class='bx bx-coffee text-2xl mb-2'></i>
                            <p class="text-xs text-yellow-100">Break Out</p>
                            <p id="ma-lunchOut" class="text-xl font-bold">--:--</p>
                        </div>
                    </div>

                    <!-- Today's Details -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h4 class="font-bold text-gray-800 mb-3">Today's Details</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-b border-gray-200">
                                <span class="text-gray-600">Date</span>
                                <span id="ma-date" class="font-medium text-gray-800">--</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-200">
                                <span class="text-gray-600">Location</span>
                                <span id="ma-location" class="font-medium text-gray-800">Not marked</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-200">
                                <span class="text-gray-600">Status</span>
                                <span id="ma-status" class="px-3 py-1 rounded-full text-sm font-medium">Not Started</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-gray-600">Total Hours</span>
                                <span id="ma-totalHours" class="font-medium text-gray-800">--</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div id="section-history" class="dashboard-section">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="flex items-center justify-between p-4 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="bg-purple-100 p-1.5 rounded-lg">
                                <i class='bx bx-history text-lg text-purple-600'></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800">Attendance History</h3>
                        </div>
                        <button onclick="exportCSV()" class="py-1.5 px-3 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition-colors">
                            <i class='bx bx-download'></i> Export
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
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Hours</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Overlay for mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <!-- Page Indicators -->
    <div class="fixed bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2 z-40 lg:hidden">
        <div id="indicator-scan" class="w-3 h-3 rounded-full bg-green-600 transition-all"></div>
        <div id="indicator-my-attendance" class="w-3 h-3 rounded-full bg-gray-300 transition-all"></div>
        <div id="indicator-history" class="w-3 h-3 rounded-full bg-gray-300 transition-all"></div>
    </div>

    <!-- Toast Notification (positioned relative to QR Scanner section) -->
    <div id="toast" class="fixed px-4 py-3 rounded-xl 
    shadow-lg transform -translate-y-20 opacity-0 transition-all 
    duration-300 z-50 text-sm" style="top: 100px; right: calc(50% - 20rem); max-width: 400px;">
    </div>

    <!-- HTTPS Warning -->
    <div id="httpsWarning" class="fixed top-20 left-0 right-0 bg-yellow-100 border-b border-yellow-400 px-4 py-2 text-center text-yellow-800 text-sm" style="display: none;">
        <i class='bx bx-error-circle mr-1'></i>
        Camera requires HTTPS. Use a secure connection or localhost.
    </div>

    <!-- PASS EMPLOYEE ID TO JS -->
    <script>
        const EMPLOYEE_ID = "<?= $_SESSION['user']['id'] ?>";
        
        // Section titles for mobile header
        const sectionTitles = {
            'scan': 'QR Scanner',
            'my-attendance': 'My Attendance',
            'history': 'History'
        };
        
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
        
        // Close sidebar when clicking on overlay
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
        
        // Prevent sidebar from closing when clicking inside sidebar
        document.getElementById('sidebar').addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Show specific section with sliding animation
        function showSection(sectionName, element, fromSwipe = false) {
            // Update sidebar active state
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.remove('active', 'bg-green-800');
            });
            if (element) {
                element.classList.add('active', 'bg-green-800');
            }
            
            // Update page indicators
            updateIndicators(sectionName);
            
            // Find current section index
            const currentIndex = sections.indexOf(getCurrentSection());
            const newIndex = sections.indexOf(sectionName);
            
            // Determine animation direction
            const isNext = newIndex > currentIndex;
            
            // Hide all sections
            document.querySelectorAll('.dashboard-section').forEach(section => {
                section.classList.remove('active');
                section.style.animation = 'none';
            });
            
            // Show selected section with animation
            const targetSection = document.getElementById('section-' + sectionName);
            if (targetSection) {
                targetSection.classList.add('active');
                targetSection.style.animation = isNext ? 'slideInRight 0.3s ease-out' : 'slideInLeft 0.3s ease-out';
            }
            
            // Update current section index
            currentSectionIndex = newIndex;
            
            // Update mobile header title
            const mobileHeaderTitle = document.getElementById('mobileHeaderTitle');
            if (mobileHeaderTitle && sectionTitles[sectionName]) {
                mobileHeaderTitle.textContent = sectionTitles[sectionName];
            }
            
            // Don't close sidebar when clicking links - keep it visible
        }
        
        function updateIndicators(sectionName) {
            sections.forEach(section => {
                const indicator = document.getElementById('indicator-' + section);
                if (indicator) {
                    if (section === sectionName) {
                        indicator.classList.remove('bg-gray-300');
                        indicator.classList.add('bg-green-600', 'w-6');
                    } else {
                        indicator.classList.remove('bg-green-600', 'w-6');
                        indicator.classList.add('bg-gray-300', 'w-3');
                    }
                }
            });
        }
        
        function getCurrentSection() {
            const activeSection = document.querySelector('.dashboard-section.active');
            if (activeSection) {
                return activeSection.id.replace('section-', '');
            }
            return 'scan';
        }
        
        /* ================= SWIPE NAVIGATION ================= */
        let touchStartX = 0;
        let touchEndX = 0;
        const minSwipeDistance = 50;
        
        const sections = ['scan', 'my-attendance', 'history'];
        let currentSectionIndex = 0;
        
        document.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, false);
        
        document.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);
        
        function handleSwipe() {
            const distance = touchEndX - touchStartX;
            
            // Swipe left - go to next section
            if (distance < -minSwipeDistance) {
                if (currentSectionIndex < sections.length - 1) {
                    currentSectionIndex++;
                    navigateToSection(sections[currentSectionIndex]);
                }
            }
            
            // Swipe right - go to previous section
            if (distance > minSwipeDistance) {
                if (currentSectionIndex > 0) {
                    currentSectionIndex--;
                    navigateToSection(sections[currentSectionIndex]);
                }
            }
        }
        
        function navigateToSection(sectionName) {
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            let targetLink = null;
            
            // Find the corresponding sidebar link
            sidebarLinks.forEach(link => {
                if (link.getAttribute('onclick').includes(sectionName)) {
                    targetLink = link;
                }
            });
            
            if (targetLink) {
                showSection(sectionName, targetLink, true); // true = fromSwipe
            }
        }
        
        // Initialize indicators on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateIndicators('scan');
        });
        
        // Check if HTTPS
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            document.getElementById('httpsWarning').style.display = 'block';
        }
    </script>
    <style>
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .dashboard-section {
            display: none;
            animation: slideInRight 0.3s ease-out;
        }
        
        .dashboard-section.active {
            display: block;
        }
        
        .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .sidebar-link:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Hide scrollbar for cleaner swipe experience */
        .dashboard-section.active {
            overflow-x: hidden;
        }
    </style>
    <script src="js/employee-dashboard.js" defer></script>
</body>
</html>


