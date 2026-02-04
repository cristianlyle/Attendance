const SUPABASE_URL = "https://sdqnovlgutbvoxsctirk.supabase.co/rest/v1";
const SUPABASE_KEY = "sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey";

const qrCanvas = document.getElementById("qrCodeCanvas");
const qrMsg = document.getElementById("qrMsg");
const qrTableBody = document.querySelector("#qrTable tbody");

let countdownInterval = null;
let currentToken = null;
let isGenerating = false;

/* ================= TIME HELPERS ================= */
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

function nowUTC() {
    return new Date();
}

/* ================= TOAST NOTIFICATIONS ================= */
function showToast(message, type = "info") {
    const toast = document.createElement("div");
    toast.className = `fixed bottom-6 right-6 px-6 py-4 rounded-xl shadow-2xl transform translate-y-20 opacity-0 transition-all duration-300 z-50 flex items-center gap-3 ${
        type === "success" ? "bg-green-500 text-white" : 
        type === "error" ? "bg-red-500 text-white" : 
        "bg-blue-500 text-white"
    }`;
    toast.innerHTML = `<i class='bx ${type === "success" ? "bxs-check-circle" : type === "error" ? "bxs-x-circle" : "bxs-info-circle"} text-xl'></i><span>${message}</span>`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove("translate-y-20", "opacity-0");
    }, 10);
    
    setTimeout(() => {
        toast.classList.add("translate-y-20", "opacity-0");
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/* ================= LOAD STATS ================= */
async function loadStats() {
    try {
        // Total Users
        const usersRes = await fetch(`${SUPABASE_URL}/users?select=id`, {
            headers: { apikey: SUPABASE_KEY, Authorization: `Bearer ${SUPABASE_KEY}` }
        });
        const users = await usersRes.json();
        document.getElementById("totalUsers").textContent = users.length || 0;

        // Today's Attendance
        const today = new Date().toISOString().split("T")[0];
        const attendanceRes = await fetch(`${SUPABASE_URL}/attendance?date=eq.${today}&select=id`, {
            headers: { apikey: SUPABASE_KEY, Authorization: `Bearer ${SUPABASE_KEY}` }
        });
        const attendance = await attendanceRes.json();
        document.getElementById("todayAttendance").textContent = attendance.length || 0;

        // Active QR Tokens
          const allRes = await fetch(`${SUPABASE_URL}/qr_tokens?select=id`, {
            headers: { apikey: SUPABASE_KEY, Authorization: `Bearer ${SUPABASE_KEY}` }
        });
        const allData = await allRes.json();
        document.getElementById("totalCount").textContent = allData.length || 0;
    } catch (error) {
        console.error("Error loading stats:", error);
    }
}

/* ================= GENERATE QR ================= */
async function generateQRCode() {
    if (isGenerating) return;
    isGenerating = true;

    if (!qrCanvas) {
        isGenerating = false;
        return;
    }

    const qrMsgSpan = qrMsg.querySelector("span");
    qrMsgSpan.textContent = "";
    qrMsg.classList.remove("text-green-500");
    qrMsg.classList.add("text-red-500");

    const locationSelect = document.getElementById("qrLocation");
    if (!locationSelect) {
        isGenerating = false;
        return;
    }

    const location = locationSelect.value;
    const token = crypto.randomUUID();

    const createdAt = nowUTC();
    const expiresAt = new Date(createdAt.getTime() + 13 * 1000);

    try {
        const res = await fetch(`${SUPABASE_URL}/qr_tokens`, {
            method: "POST",
            headers: {
                "apikey": SUPABASE_KEY,
                "Authorization": `Bearer ${SUPABASE_KEY}`,
                "Content-Type": "application/json",
                "Prefer": "return=minimal"
            },
            body: JSON.stringify({
                token,
                location_id: location,
                expires_at: expiresAt.toISOString(),
                is_active: true
            })
        });

        if (!res.ok) {
            const err = await res.text();
            throw new Error(err);
        }

        // DRAW QR
        QRCode.toCanvas(qrCanvas, token, { 
            width: 180,
            color: {
                dark: "#15803d",
                light: "#ffffff"
            }
        });

        // UPDATE DISPLAY
        document.getElementById("qrToken").textContent = token;
        document.getElementById("qrCreated").textContent = toPHTime(createdAt.toISOString());
        document.getElementById("qrExpires").textContent = toPHTime(expiresAt);
        document.getElementById("qrLocationText").textContent = location;

        qrMsg.classList.remove("hidden", "text-red-500");
        qrMsg.classList.add("text-green-500");
        qrMsgSpan.innerHTML = '<i class="bx bxs-check-circle mr-1"></i>QR Code generated successfully!';

        startCountdown(expiresAt, token);
        currentToken = token;
        loadStats();

    } catch (err) {
        console.error(err);
        qrMsgSpan.innerHTML = '<i class="bx bxs-error-circle mr-1"></i>Failed to generate QR code';
    }

    isGenerating = false;
}

/* ================= COUNTDOWN + AUTO-REGENERATE ================= */
function startCountdown(expiresAt, token) {
    const expiresEl = document.getElementById("qrExpires");
    const qrMsgSpan = qrMsg.querySelector("span");

    if (countdownInterval) clearInterval(countdownInterval);

    countdownInterval = setInterval(async () => {
        const diff = expiresAt - nowUTC();

        if (diff <= 0) {
            clearInterval(countdownInterval);
            expiresEl.textContent = "Expired";
            
            // Show expired message
            qrMsg.classList.remove("text-green-500");
            qrMsg.classList.add("text-red-500");
            qrMsgSpan.innerHTML = '<i class="bx bxs-timer mr-1"></i>QR Code expired';

            // AUTO DEACTIVATE CURRENT QR
            await deactivateByToken(token);
            loadQRCodes();
            loadStats();

            // AUTO GENERATE NEW QR after 2 seconds
            setTimeout(() => {
                qrMsgSpan.innerHTML = '<i class="bx bx-loader-alt bx-spin mr-1"></i>Generating new QR code...';
                generateQRCode();
            }, 2000);
            return;
        }

        const mins = Math.floor(diff / 60000);
        const secs = Math.floor((diff % 60000) / 1000);
        expiresEl.textContent = `${mins}m ${secs}s`;
        
        // Change color when close to expiry
        if (diff < 10000) {
            expiresEl.classList.add("text-red-500");
        } else {
            expiresEl.classList.remove("text-red-500");
        }
    }, 1000);
}

/* ================= LOAD QR CODES ================= */
async function loadQRCodes() {
    if (!qrTableBody) return;

    qrTableBody.innerHTML = "";

    try {
        const res = await fetch(
            `${SUPABASE_URL}/qr_tokens?is_active=eq.true&order=created_at.desc`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`,
                },
            }
        );

        const data = await res.json();

        if (data.length === 0) {
            qrTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class='bx bx-qr-code text-4xl mb-2 text-gray-300'></i>
                            <p>No active QR codes</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        for (const row of data) {
            const tr = document.createElement("tr");
            tr.className = "hover:bg-gray-50 transition-colors";
            tr.innerHTML = `
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded font-mono text-xs break-all">
                        ${row.token.substring(0, 8)}...
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-sm">
                        <i class='bx bxs-map-pin mr-1'></i>${row.location_id}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    ${toPHTime(row.expires_at)}
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-green-500 text-white rounded-full text-xs">
                        <i class='bx bxs-check-circle mr-1'></i>Active
                    </span>
                </td>
                <td class="px-6 py-4">
                    <button onclick="deactivateQRCode('${row.id}')" 
                        class="flex items-center gap-1 px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg text-sm transition-colors">
                        <i class='bx bx-block'></i>
                        Deactivate
                    </button>
                </td>
            `;
            qrTableBody.appendChild(tr);
        }
    } catch (error) {
        console.error("Error loading QR codes:", error);
        showToast("Failed to load QR codes", "error");
    }
}

/* ================= LOAD QR CODES WITH SCAN STATUS ================= */
async function loadQRCodesWithStatus() {
    const qrTokensTableBody = document.querySelector("#qrTokensTable tbody");
    if (!qrTokensTableBody) return;

    qrTokensTableBody.innerHTML = `
        <tr>
            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                <div class="flex flex-col items-center">
                    <i class='bx bx-loader-alt bx-spin text-4xl mb-2 text-gray-300'></i>
                    <p>Loading QR tokens...</p>
                </div>
            </td>
        </tr>
    `;

    try {
        const res = await fetch(
            `${SUPABASE_URL}/qr_tokens?is_active=eq.true&order=created_at.desc`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`,
                },
            }
        );

        const data = await res.json();
        qrTokensTableBody.innerHTML = "";

        if (data.length === 0) {
            qrTokensTableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <i class='bx bx-qr-code text-4xl mb-2 text-gray-300'></i>
                            <p>No active QR tokens</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        const now = new Date();
        for (const row of data) {
            const isExpired = new Date(row.expires_at) < now;
            
            // Get scan status
            const timeInTaken = row.time_in_taken ? '<i class="bx bx-check text-green-500"></i>' : '<i class="bx bx-minus text-gray-400"></i>';
            const lunchInTaken = row.lunch_in_taken ? '<i class="bx bx-check text-green-500"></i>' : '<i class="bx bx-minus text-gray-400"></i>';
            const lunchOutTaken = row.lunch_out_taken ? '<i class="bx bx-check text-green-500"></i>' : '<i class="bx bx-minus text-gray-400"></i>';
            const timeOutTaken = row.time_out_taken ? '<i class="bx bx-check text-green-500"></i>' : '<i class="bx bx-minus text-gray-400"></i>';

            const tr = document.createElement("tr");
            tr.className = "hover:bg-gray-50 transition-colors";
            
            tr.innerHTML = `
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded font-mono text-xs break-all">
                        ${row.token.substring(0, 12)}...
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-sm">
                        <i class='bx bxs-map-pin mr-1'></i>${row.location_id}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    ${toPHTime(row.expires_at)}
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full ${row.time_in_taken ? 'bg-green-100' : 'bg-gray-100'}">
                        ${timeInTaken}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full ${row.lunch_in_taken ? 'bg-blue-100' : 'bg-gray-100'}">
                        ${lunchInTaken}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full ${row.lunch_out_taken ? 'bg-yellow-100' : 'bg-gray-100'}">
                        ${lunchOutTaken}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full ${row.time_out_taken ? 'bg-orange-100' : 'bg-gray-100'}">
                        ${timeOutTaken}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <button onclick="deactivateQRCode('${row.id}')" 
                        class="flex items-center gap-1 px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg text-sm transition-colors">
                        <i class='bx bx-block'></i>
                        Deactivate
                    </button>
                </td>
            `;
            qrTokensTableBody.appendChild(tr);
        }
    } catch (error) {
        console.error("Error loading QR tokens with status:", error);
        qrTokensTableBody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-8 text-center text-red-500">
                    <div class="flex flex-col items-center">
                        <i class='bx bxs-error-circle text-4xl mb-2'></i>
                        <p>Failed to load QR tokens</p>
                    </div>
                </td>
            </tr>
        `;
    }
}

/* ================= DEACTIVATE ================= */
async function deactivateQRCode(id) {
    try {
        await fetch(`${SUPABASE_URL}/qr_tokens?id=eq.${id}`, {
            method: "PATCH",
            headers: {
                "apikey": SUPABASE_KEY,
                "Authorization": `Bearer ${SUPABASE_KEY}`,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ is_active: false })
        });
        loadQRCodes();
        loadQRCodesWithStatus();
        loadStats();
        showToast("QR Code deactivated", "success");
    } catch (error) {
        console.error("Error deactivating QR:", error);
        showToast("Failed to deactivate QR code", "error");
    }
}

async function deactivateByToken(token) {
    try {
        await fetch(`${SUPABASE_URL}/qr_tokens?token=eq.${token}`, {
            method: "PATCH",
            headers: {
                "apikey": SUPABASE_KEY,
                "Authorization": `Bearer ${SUPABASE_KEY}`,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ is_active: false })
        });
    } catch (error) {
        console.error("Error deactivating token:", error);
    }
}

/* ================= STOP QR CODE ================= */
function stopQRCode() {
    // Stop the countdown
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }

    // Clear QR canvas
    if (qrCanvas) {
        const ctx = qrCanvas.getContext("2d");
        ctx.clearRect(0, 0, qrCanvas.width, qrCanvas.height);
    }

    // Clear info displays
    document.getElementById("qrToken").textContent = "--";
    document.getElementById("qrCreated").textContent = "--";
    document.getElementById("qrExpires").textContent = "--";
    document.getElementById("qrLocationText").textContent = "--";

    // Update message
    const qrMsgSpan = qrMsg.querySelector("span");
    qrMsg.classList.remove("text-green-500");
    qrMsg.classList.add("text-red-500");
    qrMsgSpan.innerHTML = '<i class="bx bxs-stop-circle mr-1"></i>QR code generation stopped';

    // Deactivate the current token
    if (currentToken) {
        deactivateByToken(currentToken);
        currentToken = null;
    }
}

/* ================= INITIALIZE ================= */
document.addEventListener("DOMContentLoaded", () => {
    loadQRCodes();
    loadStats();
    startMonitoringForUsedTokens();
});

// Auto-generate QR on page load if none exists
(async () => {
    try {
        const res = await fetch(
            `${SUPABASE_URL}/qr_tokens?is_active=eq.true&order=created_at.desc&limit=1`,
            {
                headers: {
                    "apikey": SUPABASE_KEY,
                    "Authorization": `Bearer ${SUPABASE_KEY}`
                }
            }
        );
        const data = await res.json();
        if (!data || data.length === 0) {
            generateQRCode();
        }
    } catch (error) {
        console.error("Error checking for existing QR:", error);
    }
})();

/* ================= MONITOR FOR USED TOKENS ================= */
let lastKnownTokenId = null;

async function startMonitoringForUsedTokens() {
    // Get the last known active token ID
    try {
        const res = await fetch(
            `${SUPABASE_URL}/qr_tokens?is_active=eq.true&order=created_at.desc&limit=1`,
            {
                headers: {
                    "apikey": SUPABASE_KEY,
                    "Authorization": `Bearer ${SUPABASE_KEY}`
                }
            }
        );
        const data = await res.json();
        if (data && data.length > 0) {
            lastKnownTokenId = data[0].id;
        }
    } catch (error) {
        console.error("Error getting initial token:", error);
    }

    // Poll every 2 seconds to check if the current token was used
    setInterval(async () => {
        try {
            // Get the current active token
            const res = await fetch(
                `${SUPABASE_URL}/qr_tokens?is_active=eq.true&order=created_at.desc&limit=1`,
                {
                    headers: {
                        "apikey": SUPABASE_KEY,
                        "Authorization": `Bearer ${SUPABASE_KEY}`
                    }
                }
            );
            const data = await res.json();

            // If no active token found, generate a new one
            if (!data || data.length === 0) {
                // Check if we should auto-generate (only if not stopped)
                if (currentToken === null && countdownInterval === null) {
                    // QR was stopped by admin, don't auto-generate
                    return;
                }
                
                // Token was used by employee, generate new one
                const qrMsgSpan = qrMsg.querySelector("span");
                qrMsgSpan.innerHTML = '<i class="bx bx-loader-alt bx-spin mr-1"></i>Employee scanned! Generating new QR...';
                generateQRCode();
            } else {
                // Check if the token has changed (was deactivated and new one created)
                if (lastKnownTokenId && data[0].id !== lastKnownTokenId) {
                    // New token was generated, update our reference
                    lastKnownTokenId = data[0].id;
                    loadQRCodes();
                    loadQRCodesWithStatus();
                    loadStats();
                } else {
                    // Token status might have changed (scanned), refresh the status table
                    loadQRCodesWithStatus();
                }
            }
        } catch (error) {
            console.error("Error monitoring tokens:", error);
        }
    }, 2000);
}
