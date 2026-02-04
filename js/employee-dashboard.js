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

/* ================= TOAST NOTIFICATIONS ================= */
function showToast(message, type = "info") {
    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    // Get styles from CSS or use defaults
    const colors = {
        success: "#28a745",
        error: "#dc3545",
        warning: "#ffc107",
        info: "#17a2b8"
    };
    toast.style.backgroundColor = colors[type] || colors.info;
    toast.style.color = "white";
    toast.style.padding = "12px 24px";
    toast.style.borderRadius = "8px";
    toast.style.marginTop = "10px";
    toast.style.marginLeft = "auto";
    toast.style.marginRight = "auto";
    toast.style.maxWidth = "400px";
    toast.style.textAlign = "center";
    toast.style.boxShadow = "0 4px 6px rgba(0,0,0,0.1)";
    toast.style.fontWeight = "500";
    toast.style.fontFamily = "system-ui, sans-serif";
    toast.style.position = "relative";
    toast.style.zIndex = "9999";

    // Show toast
    setTimeout(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateY(0)";
    }, 10);

    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateY(-20px)";
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

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

        if (!res.ok) {
            throw new Error('Failed to fetch attendance');
        }

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
            document.getElementById('lunchIn').textContent = todayRecord.lunch_in ? toPHTimeOnly(todayRecord.lunch_in) : '--:--';
            document.getElementById('lunchOut').textContent = todayRecord.lunch_out ? toPHTimeOnly(todayRecord.lunch_out) : '--:--';
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

async function markAttendance(token) {
    showToast("Processing QR code...", "info");

    try {
        const now = new Date();
        const today = new Date().toISOString().split("T")[0];
        
        // Check if token is valid
        const tokenRes = await fetch(
            `${SUPABASE_URL}/qr_tokens?token=eq.${token}`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );

        if (!tokenRes.ok) {
            throw new Error('Failed to fetch token');
        }

        const tokenData = await tokenRes.json();
        
        // If token not found
        if (tokenData.length === 0) {
            showToast("Invalid QR code.", "error");
            // Restart scanner after error
            setTimeout(() => startScanner(), 2000);
            return;
        }
        
        const tokenRecord = tokenData[0];
        
        // Check if token is expired
        const tokenExpiry = new Date(tokenRecord.expires_at).getTime();
        const currentTime = now.getTime();
        
        if (tokenExpiry < currentTime) {
            showToast("QR code expired.", "error");
            // Restart scanner after error
            setTimeout(() => startScanner(), 2000);
            return;
        }
        
        // Check if token has already been used today
        const checkUsedRes = await fetch(
            `${SUPABASE_URL}/attendance?user_id=eq.${EMPLOYEE_ID}&token=eq.${tokenRecord.id}&date=eq.${today}`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );

        if (checkUsedRes.ok) {
            const usedData = await checkUsedRes.json();
            if (usedData.length > 0) {
                showToast("Invalid! QR Code Already Scanned", "error");
                // Restart scanner after error
                setTimeout(() => startScanner(), 2000);
                return;
            }
        }

        const location = tokenRecord.location_id;

        // Count how many attendance records user has today
        const countRes = await fetch(
            `${SUPABASE_URL}/attendance?user_id=eq.${EMPLOYEE_ID}&date=eq.${today}`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );

        if (!countRes.ok) {
            throw new Error('Failed to count attendance');
        }

        const attendanceData = await countRes.json();
        let existingRecordId = null;
        let existingRecord = null;

        // If record already exists, get its ID and data
        if (attendanceData.length > 0) {
            existingRecordId = attendanceData[0].id;
            existingRecord = attendanceData[0];
        }

        // Determine which scan this is based on existing fields
        // Check which field is still null and needs to be filled
        let insertData = {
            user_id: EMPLOYEE_ID,
            date: today,
            token: tokenRecord.id,
            location: location
        };

        if (!existingRecord) {
            // No record exists yet - 1st scan: Time In
            insertData.time_in = new Date().toISOString();
            showToast("✓ Time In recorded!", "success");
        } else if (existingRecord.time_in && !existingRecord.lunch_in) {
            // 2nd scan: Lunch In
            insertData.lunch_in = new Date().toISOString();
            showToast("✓ Lunch In recorded! Enjoy your break.", "success");
        } else if (existingRecord.lunch_in && !existingRecord.lunch_out) {
            // 3rd scan: Lunch Out
            insertData.lunch_out = new Date().toISOString();
            showToast("✓ Back from lunch! Continue working.", "success");
        } else if (existingRecord.lunch_out && !existingRecord.time_out) {
            // 4th scan: Time Out
            insertData.time_out = new Date().toISOString();
            showToast("✓ Time Out recorded! Have a great day!", "success");
        } else {
            showToast("All attendance scans completed for today!", "info");
            // Restart scanner
            setTimeout(() => startScanner(), 2000);
            fetchAttendance();
            return;
        }

        // Insert or Update attendance record
        let insertRes;
        if (existingRecordId) {
            // Update existing record using PATCH
            insertRes = await fetch(`${SUPABASE_URL}/attendance?id=eq.${existingRecordId}`, {
                method: "PATCH",
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`,
                    "Content-Type": "application/json",
                    Prefer: "return=minimal"
                },
                body: JSON.stringify(insertData)
            });
        } else {
            // Insert new record
            insertRes = await fetch(`${SUPABASE_URL}/attendance`, {
                method: "POST",
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`,
                    "Content-Type": "application/json",
                    Prefer: "return=minimal"
                },
                body: JSON.stringify(insertData)
            });
        }

        if (!insertRes.ok) {
            const errorText = await insertRes.text();
            console.error('Insert error:', errorText);
            showToast("Error recording attendance. Check console.", "error");
        }

        // Refresh attendance data
        fetchAttendance();

        // Restart scanner after 2 seconds
        setTimeout(() => startScanner(), 2000);

    } catch (error) {
        console.error('Error marking attendance:', error);
        showToast("An error occurred: " + error.message, "error");
        // Restart scanner after error
        setTimeout(() => startScanner(), 2000);
    }
}
