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
    toast.style.boxShadow = "0 4px 12px rgba(0,0,0,0.2)";
    toast.style.fontWeight = "500";
    toast.style.fontFamily = "system-ui, sans-serif";
    toast.style.zIndex = "9999";
    
    // Position at top-right corner
    toast.style.position = "fixed";
    toast.style.top = "80px";
    toast.style.right = "20px";
    toast.style.maxWidth = "350px";
    
    // Show toast with slide-in animation
    toast.style.opacity = "0";
    toast.style.transform = "translateX(100px)";
    toast.style.transition = "all 0.3s ease-out";
    
    setTimeout(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateX(0)";
    }, 10);

    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateX(100px)";
        setTimeout(() => toast.remove(), 300);
    }, 1500);
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
                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class='bx bx-calendar-x text-3xl mb-1 text-gray-300'></i>
                            <p class="text-sm">No attendance records</p>
                        </div>
                    </td>
                </tr>
            `;
            // Reset My Attendance section
            resetMyAttendanceSection();
            return;
        }

        // Populate History Table
        data.forEach(record => {
            const tr = document.createElement("tr");
            tr.className = "hover:bg-gray-50";
            
            // Calculate total hours
            let hoursWorked = '--';
            if (record.time_in && record.time_out) {
                const timeIn = new Date(record.time_in);
                const timeOut = new Date(record.time_out);
                const diffMs = timeOut - timeIn;
                const hours = (diffMs / (1000 * 60 * 60)).toFixed(2);
                hoursWorked = `${hours} hrs`;
            }
            
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
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">${hoursWorked}</span>
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Update today's status
        const today = new Date().toISOString().split('T')[0];
        const todayRecord = data.find(r => r.date === today);
        
        // Update My Attendance section
        updateMyAttendanceSection(todayRecord);
    } catch (error) {
        console.error('Error fetching attendance:', error);
        showToast('Error loading attendance', 'error');
    }
}

/* ================= UPDATE MY ATTENDANCE SECTION ================= */

function updateMyAttendanceSection(record) {
    const today = new Date().toISOString().split('T')[0];
    
    if (!record || record.date !== today) {
        resetMyAttendanceSection();
        return;
    }
    
    // Update cards
    document.getElementById('ma-timeIn').textContent = record.time_in ? toPHTimeOnly(record.time_in) : '--:--';
    document.getElementById('ma-lunchIn').textContent = record.lunch_in ? toPHTimeOnly(record.lunch_in) : '--:--';
    document.getElementById('ma-lunchOut').textContent = record.lunch_out ? toPHTimeOnly(record.lunch_out) : '--:--';
    document.getElementById('ma-timeOut').textContent = record.time_out ? toPHTimeOnly(record.time_out) : '--:--';
    
    // Update details
    document.getElementById('ma-date').textContent = record.date || '--';
    document.getElementById('ma-location').textContent = record.location || 'Not marked';
    
    // Determine status
    let status = 'Not Started';
    let statusClass = 'bg-gray-100 text-gray-700';
    
    if (record.time_in && record.time_out) {
        status = 'Completed';
        statusClass = 'bg-green-100 text-green-700';
    } else if (record.time_in && record.lunch_in && !record.lunch_out) {
        status = 'On Break';
        statusClass = 'bg-yellow-100 text-yellow-700';
    } else if (record.lunch_out && !record.time_out) {
        status = 'Working';
        statusClass = 'bg-blue-100 text-blue-700';
    } else if (record.time_in && !record.lunch_in) {
        status = 'Time In';
        statusClass = 'bg-green-100 text-green-700';
    }
    
    const statusEl = document.getElementById('ma-status');
    statusEl.textContent = status;
    statusEl.className = `px-3 py-1 rounded-full text-sm font-medium ${statusClass}`;
    
    // Calculate total hours if time_out exists
    if (record.time_in && record.time_out) {
        const timeIn = new Date(record.time_in);
        const timeOut = new Date(record.time_out);
        const diffMs = timeOut - timeIn;
        const diffHours = (diffMs / (1000 * 60 * 60)).toFixed(2);
        document.getElementById('ma-totalHours').textContent = `${diffHours} hrs`;
    } else {
        document.getElementById('ma-totalHours').textContent = '--';
    }
}

function resetMyAttendanceSection() {
    document.getElementById('ma-timeIn').textContent = '--:--';
    document.getElementById('ma-lunchIn').textContent = '--:--';
    document.getElementById('ma-lunchOut').textContent = '--:--';
    document.getElementById('ma-timeOut').textContent = '--:--';
    document.getElementById('ma-date').textContent = '--';
    document.getElementById('ma-location').textContent = 'Not marked';
    document.getElementById('ma-status').textContent = 'Not Started';
    document.getElementById('ma-status').className = 'px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700';
    document.getElementById('ma-totalHours').textContent = '--';
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
                showToast("Invalid! QR code is already used", "error");
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

        // Also insert into token_scans table for tracking
        try {
            // Determine the action type
            let action = 'time_in';
            if (existingRecord && existingRecord.time_in && !existingRecord.lunch_in) {
                action = 'lunch_in';
            } else if (existingRecord && existingRecord.lunch_in && !existingRecord.lunch_out) {
                action = 'lunch_out';
            } else if (existingRecord && existingRecord.lunch_out && !existingRecord.time_out) {
                action = 'time_out';
            }
            
            
            // Use PHP endpoint instead of direct Supabase call (to bypass RLS)
            const scanRes = await fetch('token-scan-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token_id: tokenRecord.id,
                    action: action
                })
            });
            
            console.log('Token scan response:', scanRes.status, scanRes.statusText);
            if (!scanRes.ok) {
                const errorText = await scanRes.text();
                console.error('Token scan error:', errorText);
            }
        } catch (scanError) {
            console.error('Error recording token scan:', scanError);
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
