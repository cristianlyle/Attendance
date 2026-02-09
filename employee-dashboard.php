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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .bottom-nav-link {
            transition: all 0.2s ease;
        }
        
        .bottom-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .bottom-nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .scan-button {
            transition: all 0.2s ease;
        }
        
        .scan-button:hover {
            transform: scale(1.02);
        }
        
        .scan-button:active {
            transform: scale(0.98);
        }
        
        /* Scanner frame glow effect */
        .scanner-frame.active {
            box-shadow: 0 0 0 2px #22c55e, 0 0 20px rgba(34, 197, 94, 0.3);
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
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
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 4px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        
        /* Status badge styles */
        .badge-active {
            background-color: #DCFCE7;
            color: #166534;
        }
        
        .badge-inactive {
            background-color: #FEE2E2;
            color: #DC2626;
        }
        
        .badge-pending {
            background-color: #FEF3C7;
            color: #D97706;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] font-sans min-h-screen pb-20">

    <!-- Mobile Header -->
    <div class="bg-[#14532D] text-white p-4 flex justify-between items-center fixed top-0 left-0 right-0 z-50 shadow-md">
        <div class="flex items-center gap-2">
            <span class="font-semibold text-sm" id="mobileHeaderTitle">QR Scanner</span>
        </div>
        <div class="flex items-center gap-2">
            <?php
            $profileImage = get_profile_image($_SESSION['user']['profile_image'] ?? null);
            ?>
            <div class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center">
                <i class='bx bxs-user-badge text-lg text-white'></i>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="p-4 lg:p-6 mt-14 lg:mt-0 w-full min-h-screen">
        
        <!-- Scan Section -->
        <div id="section-scan" class="dashboard-section active">
            <!-- Welcome Banner -->
            <div class="bg-[#166534] rounded-xl p-4 mb-5 text-white shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center">
                        <i class='bx bxs-user text-xl'></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold"><?= htmlspecialchars($_SESSION['user']['name']) ?></h2>
                        <p class="text-green-100 text-sm">Scan QR code to mark your attendance</p>
                    </div>
                </div>
            </div>

            <!-- QR Scanner Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 max-w-md mx-auto">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-[#EFF6FF] rounded-lg flex items-center justify-center">
                        <i class='bx bx-qr-scan text-lg text-[#3B82F6]'></i>
                    </div>
                    <h3 class="text-base font-semibold text-[#0F172A]">QR Scanner</h3>
                </div>

                <!-- Video Preview -->
                <div class="scanner-container relative rounded-lg overflow-hidden bg-[#020617]" id="scannerContainer">
                    <video id="qrVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
                    <div id="scannerOverlay" class="scanner-overlay" style="display: none;"></div>
                    <!-- Scanner frame -->
                    <div class="scanner-frame absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-48 h-48 border-2 border-gray-500 rounded-lg relative">
                            <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-[#22C55E] rounded-tl-lg"></div>
                            <div class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-[#22C55E] rounded-tr-lg"></div>
                            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-[#22C55E] rounded-bl-lg"></div>
                            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-[#22C55E] rounded-br-lg"></div>
                        </div>
                    </div>
                    <div id="scanMessage" class="absolute bottom-3 left-0 right-0 text-center text-white text-xs bg-black/60 py-1.5 rounded mx-3" style="display: none;">
                        Scanning...
                    </div>
                </div>
                
                <!-- Scanner Controls -->
                <div class="mt-4 flex justify-center">
                    <button id="startScanBtn" onclick="toggleScanner()"
                        class="scan-button w-full max-w-[200px] flex items-center justify-center gap-2 py-3 bg-[#166534] hover:bg-[#15803D] text-white rounded-lg font-medium shadow-sm text-sm">
                        <i class='bx bx-qr-scan text-lg'></i>
                        <span id="scanBtnText">Start Scan</span>
                    </button>
                </div>
                
                <!-- Status Message -->
                <div id="attendanceMsg" class="mt-4 p-3 rounded-lg text-center text-sm font-medium"></div>
            </div>
        </div>

        <!-- My Attendance Section -->
        <div id="section-my-attendance" class="dashboard-section">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-5">
                    <div class="w-8 h-8 bg-[#EFF6FF] rounded-lg flex items-center justify-center">
                        <i class='bx bx-calendar-check text-lg text-[#3B82F6]'></i>
                    </div>
                    <h3 class="text-base font-semibold text-[#0F172A]">My Attendance</h3>
                </div>

                <!-- Today's Summary Cards -->
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div class="bg-[#DCFCE7] rounded-lg p-3">
                        <i class='bx bxs-log-in-circle text-xl text-[#166534] mb-1'></i>
                        <p class="text-xs text-[#166534]/70">Time In</p>
                        <p id="ma-timeIn" class="text-lg font-semibold text-[#0F172A]">--:--</p>
                    </div>
                    <div class="bg-[#FEF3C7] rounded-lg p-3">
                        <i class='bx bxs-log-out-circle text-xl text-[#D97706] mb-1'></i>
                        <p class="text-xs text-[#D97706]/70">Time Out</p>
                        <p id="ma-timeOut" class="text-lg font-semibold text-[#0F172A]">--:--</p>
                    </div>
                    <div class="bg-[#EFF6FF] rounded-lg p-3">
                        <i class='bx bx-restaurant text-xl text-[#3B82F6] mb-1'></i>
                        <p class="text-xs text-[#3B82F6]/70">Break In</p>
                        <p id="ma-lunchIn" class="text-lg font-semibold text-[#0F172A]">--:--</p>
                    </div>
                    <div class="bg-[#F3E8FF] rounded-lg p-3">
                        <i class='bx bx-coffee text-xl text-[#A855F7] mb-1'></i>
                        <p class="text-xs text-[#A855F6]/70">Break Out</p>
                        <p id="ma-lunchOut" class="text-lg font-semibold text-[#0F172A]">--:--</p>
                    </div>
                </div>

                <!-- Today's Details -->
                <div class="bg-[#F8FAFC] rounded-lg p-4 border border-gray-100">
                    <h4 class="font-medium text-[#0F172A] mb-3 text-sm">Today's Details</h4>
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-[#475569] text-sm">Date</span>
                            <span id="ma-date" class="font-medium text-[#0F172A] text-sm">--</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-[#475569] text-sm">Location</span>
                            <span id="ma-location" class="font-medium text-[#0F172A] text-sm">Not marked</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-[#475569] text-sm">Status</span>
                            <span id="ma-status" class="px-2.5 py-1 rounded-full text-xs font-medium badge-pending">Not Started</span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-[#475569] text-sm">Total Hours</span>
                            <span id="ma-totalHours" class="font-medium text-[#0F172A] text-sm">--</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div id="section-history" class="dashboard-section">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-[#F3E8FF] rounded-lg flex items-center justify-center">
                            <i class='bx bx-history text-lg text-[#A855F6]'></i>
                        </div>
                        <h3 class="text-base font-semibold text-[#0F172A]">Attendance History</h3>
                    </div>
                    <button onclick="exportCSV()" class="flex items-center gap-1.5 py-1.5 px-3 bg-[#166534] hover:bg-[#15803D] text-white rounded-lg text-xs font-medium transition-colors">
                        <i class='bx bx-download'></i> Export
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table id="attendanceTable" class="w-full text-sm">
                        <thead class="bg-[#F8FAFC]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Time In</th> 
                                <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Break In</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Break Out</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Time Out</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Location</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Hours</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-[#14532D] text-white shadow-lg z-50 border-t border-green-700/50">
        <div class="flex items-center justify-around py-2 px-4">
            <a href="#" onclick="showSection('scan', this)" class="bottom-nav-link active flex flex-col items-center gap-1 px-4 py-2 rounded-lg transition-colors">
                <i class='bx bx-qr-scan text-xl'></i>
                <span class="text-xs">Scan</span>
            </a>
            <a href="#" onclick="showSection('my-attendance', this)" class="bottom-nav-link flex flex-col items-center gap-1 px-4 py-2 rounded-lg transition-colors">
                <i class='bx bx-calendar-check text-xl'></i>
                <span class="text-xs">Attendance</span>
            </a>
            <a href="#" onclick="showSection('history', this)" class="bottom-nav-link flex flex-col items-center gap-1 px-4 py-2 rounded-lg transition-colors">
                <i class='bx bx-history text-xl'></i>
                <span class="text-xs">History</span>
            </a>
            <a href="logout.php" class="bottom-nav-link flex flex-col items-center gap-1 px-4 py-2 rounded-lg transition-colors">
                <i class='bx bx-log-out text-xl'></i>
                <span class="text-xs">Logout</span>
            </a>
        </div>
    </nav>

    <!-- Toast Notification -->
    <div id="toast" class="fixed px-4 py-2.5 rounded-lg shadow-lg transform -translate-y-20 opacity-0 transition-all duration-300 z-50 text-sm" style="top: 100px; right: calc(50% - 10rem); max-width: 320px;">
    </div>

    <!-- HTTPS Warning -->
    <div id="httpsWarning" class="fixed top-14 left-0 right-0 bg-[#FEF3C7] border-b border-[#F59E0B] px-4 py-2 text-center text-[#92400E] text-sm" style="display: none;">
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
        
        // Show specific section with sliding animation
        function showSection(sectionName, element, fromSwipe = false) {
            // Update bottom nav active state
            document.querySelectorAll('.bottom-nav-link').forEach(link => {
                link.classList.remove('active', 'bg-white/15');
            });
            if (element) {
                element.classList.add('active', 'bg-white/15');
            }
            
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
            const navLinks = document.querySelectorAll('.bottom-nav-link');
            let targetLink = null;
            
            // Find the corresponding nav link
            navLinks.forEach(link => {
                if (link.getAttribute('onclick') && link.getAttribute('onclick').includes(sectionName)) {
                    targetLink = link;
                }
            });
            
            if (targetLink) {
                showSection(sectionName, targetLink, true);
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
