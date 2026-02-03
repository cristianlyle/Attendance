const SUPABASE_URL = "https://sdqnovlgutbvoxsctirk.supabase.co/rest/v1";
const SUPABASE_KEY = "sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey";

const video = document.getElementById("qrVideo");
const msg = document.getElementById("attendanceMsg");
const startBtn = document.getElementById("startScanBtn");
const scanBtnText = document.getElementById("scanBtnText");
const scannerOverlay = document.getElementById("scannerOverlay");
const scanMessage = document.getElementById("scanMessage");
const scannerContainer = document.getElementById("scannerContainer");

let scanning = false;
let videoStream = null;

/* ================= TIME FORMAT - PHILIPPINE TIME ================= */
function toPHTime(dateString) {
    return new Date(dateString).toLocaleString("en-PH", {
        timeZone: "Asia/Manila",
        year: "numeric",
        month: "short",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: true
    });
}

function toPHTimeOnly(dateString) {
    return new Date(dateString).toLocaleString("en-PH", {
        timeZone: "Asia/Manila",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: true
    });
}

/* ================= FETCH ATTENDANCE ================= */

async function fetchAttendance() {
    const tbody = document.querySelector("#attendanceTable tbody");
    tbody.innerHTML = "";

    try {
        const res = await fetch(
            `${SUPABASE_URL}/attendance?user_id=eq.${EMPLOYEE_ID}&order=date.desc`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );

        const data = await res.json();

        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class='bx bx-calendar-x text-3xl mb-1 text-gray-300'></i>
                            <p class="text-sm">No attendance records</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        data.forEach(record => {
            const tr = document.createElement("tr");
            tr.className = "hover:bg-gray-50";
            tr.innerHTML = `
                <td class="px-4 py-3 whitespace-nowrap text-gray-700">${record.date}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                    ${record.time_in ? 
                        `<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">${toPHTimeOnly(record.time_in)}</span>` : 
                        '--:--'}
                </td><td class="px-4 py-3 whitespace-nowrap">
                    ${record.lunch_in ? 
                        `<span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">${toPHTimeOnly(record.lunch_in)}</span>` : 
                        '--:--'}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    ${record.lunch_out ? 
                        `<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">${toPHTimeOnly(record.lunch_out)}</span>` : 
                        '--:--'}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    ${record.time_out ? 
                        `<span class="px-2 py-1 bg-orange-100 text-orange-700 rounded-full text-xs">${toPHTimeOnly(record.time_out)}</span>` : 
                        '--:--'}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-600">${record.location || '--'}</td>
            `;
            tbody.appendChild(tr);
        });

        // Update today's status
        const today = new Date().toISOString().split('T')[0];
        const todayRecord = data.find(r => r.date === today);
        if (todayRecord) {
            document.getElementById('timeIn').textContent = todayRecord.time_in ? toPHTimeOnly(todayRecord.time_in) : '--:--';
            document.getElementById('lunchOut').textContent = todayRecord.lunch_out ? toPHTimeOnly(todayRecord.lunch_out) : '--:--';
            document.getElementById('lunchIn').textContent = todayRecord.lunch_in ? toPHTimeOnly(todayRecord.lunch_in) : '--:--';
            document.getElementById('timeOut').textContent = todayRecord.time_out ? toPHTimeOnly(todayRecord.time_out) : '--:--';
            document.getElementById('todayLocation').textContent = todayRecord.location || 'Not marked';
        }
    } catch (error) {
        console.error('Error fetching attendance:', error);
        showToast('Error loading attendance', 'error');
    }
}

fetchAttendance();

/* ================= SCANNER CONTROLS ================= */

async function toggleScanner() {
    if (scanning) {
        stopScanner();
    } else {
        await startScanner();
    }
}

async function startScanner() {
    if (scanning) return;

    // Request camera permission
    try {
        videoStream = await navigator.mediaDevices.getUserMedia({
            video: { 
                facingMode: "environment",
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        });

        video.srcObject = videoStream;
        video.setAttribute("playsinline", true);
        video.muted = true;
        
        await video.play();

        scanning = true;
        startBtn.classList.add("bg-red-500", "hover:bg-red-600");
        startBtn.classList.remove("bg-green-500", "hover:bg-green-600");
        scanBtnText.textContent = "Stop";

        // Show overlay
        if (scannerOverlay) scannerOverlay.style.display = 'block';
        if (scanMessage) scanMessage.style.display = 'block';
        if (scanMessage) scanMessage.innerHTML = '<i class="bx bx-loader-alt bx-spin mr-1"></i>Scanning for QR code...';

        // Update message
        msg.className = "mt-4 p-3 rounded-lg text-center text-sm bg-blue-100 text-blue-700";
        msg.innerHTML = '<i class="bx bx-camera mr-1"></i>Camera active - point at QR code';

        requestAnimationFrame(scanFrame);

    } catch (err) {
        console.error("Camera error:", err);
        let errorMsg = "Camera access denied";
        
        if (err.name === 'NotAllowedError') {
            errorMsg = "Camera permission denied. Please allow camera access in your browser settings.";
        } else if (err.name === 'NotFoundError') {
            errorMsg = "No camera found on this device.";
        } else if (err.name === 'NotSecureError') {
            errorMsg = "Camera requires HTTPS. Please use a secure connection.";
        }
        
        showToast(errorMsg, "error");
    }
}

function stopScanner() {
    scanning = false;

    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        videoStream = null;
    }

    // Reset video
    video.srcObject = null;

    // Reset button
    startBtn.classList.remove("bg-red-500", "hover:bg-red-600");
    startBtn.classList.add("bg-green-500", "hover:bg-green-600");
    scanBtnText.textContent = "Scan";

    // Hide overlay
    if (scannerOverlay) scannerOverlay.style.display = 'none';
    if (scanMessage) scanMessage.style.display = 'none';

    // Clear message
    msg.textContent = "";
}

/* ================= SCAN LOOP ================= */

async function scanFrame() {
    if (!scanning || !video) return;

    try {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            const canvas = document.createElement("canvas");
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            const ctx = canvas.getContext("2d");
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "dontInvert"
            });

            if (code) {
                // QR found!
                scanning = false;
                stopScanner();
                await markAttendance(code.data);
                return;
            }
        }
    } catch (err) {
        console.error("Scan error:", err);
    }

    // Continue scanning
    if (scanning) {
        requestAnimationFrame(scanFrame);
    }
}

/* ================= ATTENDANCE LOGIC ================= */

// Function to auto-generate a new QR token for the employee
async function autoGenerateQRToken(location) {
    try {
        const newToken = generateToken();
        const expiresAt = new Date();
        expiresAt.setHours(expiresAt.getHours() + 8); // Token expires in 8 hours

        const res = await fetch(`${SUPABASE_URL}/qr_tokens`, {
            method: "POST",
            headers: {
                apikey: SUPABASE_KEY,
                Authorization: `Bearer ${SUPABASE_KEY}`,
                "Content-Type": "application/json",
                Prefer: "return=minimal"
            },
            body: JSON.stringify({
                token: newToken,
                location_id: location,
                is_active: true,
                created_at: new Date().toISOString(),
                expires_at: expiresAt.toISOString()
            })
        });

        if (res.ok) {
            return { token: newToken, location_id: location };
        }
        return null;
    } catch (error) {
        console.error('Error generating QR token:', error);
        return null;
    }
}

// Function to mark expired QR tokens as inactive
async function markTokenAsExpired(token) {
    try {
        await fetch(
            `${SUPABASE_URL}/qr_tokens?token=eq.${token}`,
            {
                method: "PATCH",
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ is_active: false })
            }
        );
    } catch (error) {
        console.error('Error marking token as expired:', error);
    }
}

// Generate random token
function generateToken() {
    return 'QR' + Date.now().toString(36) + Math.random().toString(36).substring(2, 8).toUpperCase();
}

async function markAttendance(token) {
    showToast("Processing QR code...", "info");

    try {
        // Check if token is valid, active, AND not expired (using UTC for consistency)
        const now = new Date();
        const nowUTC = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
        
        const tokenRes = await fetch(
            `${SUPABASE_URL}/qr_tokens?token=eq.${token}&is_active=eq.true`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );

        const tokenData = await tokenRes.json();
        
        // If token not found or not active, it might be expired or already used
        if (tokenData.length === 0) {
            // Check if token exists but is expired
            const expiredCheck = await fetch(
                `${SUPABASE_URL}/qr_tokens?token=eq.${token}`,
                {
                    headers: {
                        apikey: SUPABASE_KEY,
                        Authorization: `Bearer ${SUPABASE_KEY}`
                    }
                }
            );
            const expiredData = await expiredCheck.json();
            
            if (expiredData.length > 0) {
                const tokenExpiry = new Date(expiredData[0].expires_at);
                if (tokenExpiry <= nowUTC) {
                    // Token is expired
                    await markTokenAsExpired(token);
                    showToast("QR code expired. Generating new QR...", "info");
                  if (newQR) {
                        showToast("Failed to generate new QR code", "error");
                    }
                } else {
                    showToast("QR code already used.", "info");
                }
            } else {
                showToast("Invalid QR code.", "error");
            }
            return;
        }
        
        // Verify token is not expired
        const tokenExpiry = new Date(tokenData[0].expires_at);
        if (tokenExpiry <= nowUTC) {
            // Token expired - mark as inactive and generate new one
            await markTokenAsExpired(token);
            showToast("QR code expired. Generating new QR...", "info");
            const newQR = await autoGenerateQRToken(tokenData[0].location_id);
            if (newQR) {
                showToast("New QR generated! Please scan again.", "success");
            } else {
                showToast("Failed to generate new QR code", "error");
            }
            return;
        }

        const today = new Date().toISOString().split("T")[0];

        // Check today's attendance
        const attendanceRes = await fetch(
            `${SUPABASE_URL}/attendance?user_id=eq.${EMPLOYEE_ID}&date=eq.${today}`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );

        const attendanceData = await attendanceRes.json();

        // Token is valid and not expired - proceed with attendance
        const location = tokenData[0].location_id;
        const record = attendanceData.length > 0 ? attendanceData[0] : null;

        // Attendance flow: Time In -> Lunch Out -> Lunch In -> Time Out
        if (!record) {
            // First scan: Time In (Morning)
            await fetch(`${SUPABASE_URL}/attendance`, {
                method: "POST",
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`,
                    "Content-Type": "application/json",
                    Prefer: "return=minimal"
                },
                body: JSON.stringify({
                    user_id: EMPLOYEE_ID,
                    date: today,
                    time_in: new Date().toISOString(),
                    location: location,
                    token: token
                })
            });

            showToast("✓ Morning Time In recorded!", "success");
            updateStatusAfterCheckIn(today, location);

        } else if (!record.time_in) {
            // Should not happen, but handle edge case
            await fetch(
                `${SUPABASE_URL}/attendance?id=eq.${record.id}`,
                {
                    method: "PATCH",
                    headers: {
                        apikey: SUPABASE_KEY,
                        Authorization: `Bearer ${SUPABASE_KEY}`,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        time_in: new Date().toISOString()
                    })
                }
            );
            showToast("✓ Time In recorded!", "success");
            updateStatusAfterCheckIn(today, location);

        } else if (!record.lunch_in) {
            // Second scan: Lunch Out
            await fetch(
                `${SUPABASE_URL}/attendance?id=eq.${record.id}`,
                {
                    method: "PATCH",
                    headers: {
                        apikey: SUPABASE_KEY,
                        Authorization: `Bearer ${SUPABASE_KEY}`,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        lunch_in: new Date().toISOString()
                    })
                }
            );
            showToast("✓ Lunch In recorded! Enjoy your break.", "success");
            updateStatusAfterLunchIn();

        } else if (!record.lunch_out) {
            // Third scan: Lunch Out (back from break)
            await fetch(
                `${SUPABASE_URL}/attendance?id=eq.${record.id}`,
                {
                    method: "PATCH",
                    headers: {
                        apikey: SUPABASE_KEY,
                        Authorization: `Bearer ${SUPABASE_KEY}`,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        lunch_out: new Date().toISOString()
                    })
                }
            );
            showToast("✓ Back from lunch! Continue working.", "success");
            updateStatusAfterLunchOut();

        } else if (!record.time_out) {
            // Fourth scan: Time Out (end of work day)
            await fetch(
                `${SUPABASE_URL}/attendance?id=eq.${record.id}`,
                {
                    method: "PATCH",
                    headers: {
                        apikey: SUPABASE_KEY,
                        Authorization: `Bearer ${SUPABASE_KEY}`,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        time_out: new Date().toISOString()
                    })
                }
            );
            showToast("✓ Time Out recorded! Have a great day!", "success");
            updateStatusAfterCheckOut();
        } else {
            // Already completed all attendance for the day
            showToast("Already completed attendance for today", "info");
        }

        // Deactivate QR token after use
        await markTokenAsExpired(token);

        fetchAttendance();

    } catch (error) {
        console.error('Error marking attendance:', error);
        showToast("An error occurred. Please try again.", "error");
    }
}

function updateStatusAfterCheckIn(today, location) {
    document.getElementById('timeIn').textContent = toPHTimeOnly(new Date().toISOString());
    document.getElementById('todayLocation').textContent = location;
}

function updateStatusAfterCheckOut() {
    document.getElementById('timeOut').textContent = toPHTimeOnly(new Date().toISOString());
}

function updateStatusAfterLunchOut() {
    document.getElementById('lunchOut').textContent = toPHTimeOnly(new Date().toISOString());
}

function updateStatusAfterLunchIn() {
    document.getElementById('lunchIn').textContent = toPHTimeOnly(new Date().toISOString());
}

/* ================= CSV EXPORT ================= */

function exportCSV() {
    const rows = Array.from(document.querySelectorAll("#attendanceTable tr"));
    let csv = rows.map(r =>
        Array.from(r.querySelectorAll("th,td"))
            .map(c => `"${c.textContent.replace(/"/g, '""').replace(/\n/g, ' ')}"`)
            .join(",")
    ).join("\n");

    const link = document.createElement("a");
    link.href = "data:text/csv;charset=utf-8," + encodeURIComponent(csv);
    link.download = "my_attendance.csv";
    link.click();
    
    showToast("CSV exported!", "success");
}

/* ================= TOAST ================= */

function showToast(message, type = "info") {
    const toast = document.getElementById("toast");
    const iconMap = {
        success: { icon: 'bxs-check-circle', bg: 'bg-green-500', text: 'text-white' },
        error: { icon: 'bxs-x-circle', bg: 'bg-red-500', text: 'text-white' },
        info: { icon: 'bxs-info-circle', bg: 'bg-blue-500', text: 'text-white' }
    };

    const config = iconMap[type] || iconMap.info;

    toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded-xl shadow-lg transform transition-all duration-300 z-50 flex items-center gap-2 ${config.bg} ${config.text}`;
    toast.innerHTML = `<i class='bx ${config.icon} text-xl'></i><span>${message}</span>`;

    // Show toast
    setTimeout(() => {
        toast.classList.remove('translate-y-20', 'opacity-0');
    }, 10);

    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
    }, 3000);
}
