const SUPABASE_URL = "https://sdqnovlgutbvoxsctirk.supabase.co/rest/v1";
const SUPABASE_KEY = "sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey";

const qrTableBody = document.querySelector("#qrTable tbody");
let selectedTokenId = null;
let tokenScansMap = {}; // Global storage for token scans

/* ================= CHECK AND UPDATE EXPIRED TOKENS ================= */
async function checkAndUpdateExpiredTokens() {
    try {
        // Fetch all active tokens
        const res = await fetch(
            `${SUPABASE_URL}/qr_tokens?is_active=eq.true`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );
        
        if (!res.ok) throw new Error("Failed to fetch active tokens");
        
        const data = await res.json();
        const now = new Date();
        
        // Check each token and update expired ones
        for (const token of data) {
            const expiresAt = new Date(token.expires_at);
            
            if (expiresAt < now) {
                // Token is expired, update in database
                await fetch(`${SUPABASE_URL}/qr_tokens?id=eq.${token.id}`, {
                    method: "PATCH",
                    headers: {
                        "apikey": SUPABASE_KEY,
                        "Authorization": `Bearer ${SUPABASE_KEY}`,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ is_active: false })
                });
                
                console.log(`Token ${token.id} marked as expired`);
            }
        }
    } catch (error) {
        console.error("Error checking expired tokens:", error);
    }
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

/* ================= TIME FORMAT ================= */
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

/* ================= GET ACTION BADGE ================= */
function getActionBadge(action) {
    const actionStyles = {
        'time_in': { bg: 'bg-green-100', text: 'text-green-700', icon: 'bx-log-in', label: 'Time In' },
        'time_out': { bg: 'bg-red-100', text: 'text-red-700', icon: 'bx-log-out', label: 'Time Out' },
        'lunch_in': { bg: 'bg-yellow-100', text: 'text-yellow-700', icon: 'bxs-bowl-rice', label: 'Lunch In' },
        'lunch_out': { bg: 'bg-orange-100', text: 'text-orange-700', icon: 'bx bx-food', label: 'Lunch Out' }
    };
    
    const style = actionStyles[action] || { bg: 'bg-gray-100', text: 'text-gray-700', icon: 'bx-question', label: action };
    
    return `<span class="inline-flex items-center gap-1 px-3 py-1.5 ${style.bg} ${style.text} rounded-full text-sm font-medium">
        <i class='bx ${style.icon}'></i>${style.label}
    </span>`;
}

/* ================= LOAD STATS ================= */
async function loadStats() {
    try {
        // All tokens
        const allRes = await fetch(`${SUPABASE_URL}/qr_tokens?select=id`, {
            headers: { apikey: SUPABASE_KEY, Authorization: `Bearer ${SUPABASE_KEY}` }
        });
        const allData = await allRes.json();
        
        // Active tokens
        const activeRes = await fetch(`${SUPABASE_URL}/qr_tokens?is_active=eq.true&select=id`, {
            headers: { apikey: SUPABASE_KEY, Authorization: `Bearer ${SUPABASE_KEY}` }
        });
        const activeData = await activeRes.json();
        
        const now = new Date();
        const expiredData = allData.filter(t => !t.is_active || new Date(t.expires_at) < now);
        
        document.getElementById("totalCount").textContent = allData.length || 0;
        document.getElementById("activeCount").textContent = activeData.length || 0;
        document.getElementById("expiredCount").textContent = expiredData.length || 0;
    } catch (error) {
        console.error("Error loading stats:", error);
    }
}

/* ================= LOAD QR TOKENS WITH SCAN DATA ================= */
async function loadQRCodes() {
    if (!qrTableBody) return;

    qrTableBody.innerHTML = `
        <tr>
            <td colspan="6" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <i class='bx bx-loader-alt bx-spin text-4xl text-gray-300 mb-2'></i>
                    <p class="text-gray-500">Loading QR tokens...</p>
                </div>
            </td>
        </tr>
    `;

    try {
        // Fetch all QR tokens
        const tokensRes = await fetch(
            `${SUPABASE_URL}/qr_tokens?order=created_at.desc&select=*`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );

        if (!tokensRes.ok) throw new Error("Failed to fetch QR tokens");

        const tokensData = await tokensRes.json();
        console.log('QR Tokens fetched:', tokensData.length, tokensData);
        
        // Fetch all token scans
        // Fetch token scans and users via PHP endpoint
        try {
            const scansRes = await fetch('admin-token-scans-handler.php');
            if (scansRes.ok) {
                const data = await scansRes.json();
                if (data.success) {
                    scansData = data.scans || [];
                    usersMap = data.users_map || {};
                    tokenScansMap = data.token_scans_map || {}; // Use global variable
                    console.log('Token scans fetched:', scansData.length, scansData);
                    console.log('Users map:', usersMap);
                    console.log('Token scans map:', tokenScansMap);
                }
            } else {
                console.log('Token scans fetch failed:', scansRes.status, scansRes.statusText);
            }
        } catch (e) {
            console.log("Token scans fetch error:", e);
        }

        const now = new Date();
        qrTableBody.innerHTML = "";

        if (tokensData.length === 0) {
            qrTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class='bx bx-qr text-5xl text-gray-300 mb-3'></i>
                            <p class="text-gray-500 text-lg">No QR tokens found</p>
                            <p class="text-gray-400 text-sm">Go to Dashboard to generate a new QR code</p>
                        </div>
                    </td>
                </tr>
            `;
            loadStats();
            return;
        }

        tokensData.forEach(row => {
            const tr = document.createElement("tr");
            tr.className = "hover:bg-gray-50 transition-colors cursor-pointer";
            tr.onclick = () => openModal(row);

            // Check scan status
            const latestScan = tokenScansMap[row.id];
            
            // Compute expired status dynamically
            let expiredText = "No";
            let expiredClass = "bg-green-100 text-green-700";
            let expiredIcon = "bx-check-circle";
            
            if (!row.is_active || new Date(row.expires_at) < now) {
                expiredText = "Yes";
                expiredClass = "bg-red-100 text-red-700";
                expiredIcon = "bx-x-circle";
            }

            // User and status display
            let userDisplay = '<span class="text-gray-400"><i class="bx bx-minus"></i></span>';
            let statusDisplay = '<span class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-500 rounded-full text-sm font-medium">Not Scanned</span>';
            
            if (latestScan && latestScan.action) {
                const userName = latestScan.users?.name || latestScan.user_name || 'Unknown User';
                userDisplay = `<span class="font-medium text-gray-800">${userName}</span>`;
                statusDisplay = getActionBadge(latestScan.action);
            }

            tr.innerHTML = `
                <td class="px-6 py-4">
                    <span class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg font-mono text-sm break-all">
                        ${row.token.substring(0, 12)}...
                    </span>
                </td>
                <td class="px-6 py-4">
                    ${userDisplay}
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">
                        <i class='bx bxs-map-pin'></i>${row.location_id}
                    </span>
                </td>
                <td class="px-6 py-4">
                    ${statusDisplay}
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 ${expiredClass} rounded-full text-sm font-medium">
                        <i class='bx ${expiredIcon}'></i>${expiredText}
                    </span>
                </td>
            `;

            qrTableBody.appendChild(tr);
        });

        loadStats();
    } catch (error) {
        console.error(error);
        qrTableBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <i class='bx bxs-error-circle text-5xl text-red-300 mb-3'></i>
                        <p class="text-red-500 text-lg">Failed to load QR tokens</p>
                        <p class="text-gray-400 text-sm">Please check your connection and try again</p>
                    </div>
                </td>
            </tr>
        `;
    }
}

/* ================= MODAL FUNCTIONS ================= */
function openModal(row) {
    selectedTokenId = row.id;

    const now = new Date();
    let expiredText = "No";
    let expiredClass = "text-green-600";
    let expiredIcon = "bx-check-circle";

    if (!row.is_active || new Date(row.expires_at) < now) {
        expiredText = "Yes";
        expiredClass = "text-red-600";
        expiredIcon = "bx-x-circle";
    }

    document.getElementById("modalToken").textContent = row.token;
    document.getElementById("modalLocation").textContent = row.location_id;
    
    // Format and set Created At
    const createdAt = row.created_at ? new Date(row.created_at).toLocaleString() : '-';
    document.getElementById("modalCreated").textContent = createdAt;
    
    // Format and set Expires At
    const expiresAt = row.expires_at ? new Date(row.expires_at).toLocaleString() : '-';
    document.getElementById("modalExpires").textContent = expiresAt;
    
    // Get scan data for this token
    const latestScan = tokenScansMap[row.id];
    
    // Set Status
    let statusText = "Not Scanned";
    let statusClass = "text-gray-500";
    if (latestScan && latestScan.action) {
        statusText = latestScan.action.replace('_', ' ').toUpperCase();
        statusClass = "text-green-600";
    }
    const modalStatus = document.getElementById("modalStatus");
    if (modalStatus) {
        modalStatus.textContent = statusText;
        modalStatus.className = `font-medium ${statusClass}`;
    }
    
    // Add user and status to modal
    const modalContent = document.querySelector("#tokenModal .bg-white.shadow-2xl");
    if (modalContent) {
        // Check if user/status elements exist, if not add them
        let userRow = modalContent.querySelector(".user-row");
        if (!userRow) {
            const expiresRow = modalContent.querySelector("#modalExpires")?.parentNode;
            if (expiresRow) {
                userRow = document.createElement("div");
                userRow.className = "bg-gray-50 rounded-xl p-4 user-row";
                userRow.innerHTML = `
                    <p class="text-xs text-gray-500 mb-1">Scanned By</p>
                    <p id="modalUser" class="font-medium text-gray-800">-</p>
                `;
                expiresRow.parentNode.insertBefore(userRow, expiresRow.nextSibling);
            }
        }
        
        // Set scanned user
        const modalUser = document.getElementById("modalUser");
        if (modalUser) {
            if (latestScan && latestScan.users) {
                modalUser.textContent = latestScan.users.name || latestScan.users.email || 'Unknown User';
            } else {
                modalUser.textContent = '-';
            }
        }
    }

    const modal = document.getElementById("tokenModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function closeModal() {
    const modal = document.getElementById("tokenModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}

/* ================= DELETE TOKEN ================= */
async function confirmDelete(id) {
    if (!confirm("Are you sure you want to delete this token?")) return;
    
    selectedTokenId = id;
    await deleteToken();
}

async function deleteToken() {
    if (!selectedTokenId) return;

    try {
        const res = await fetch(
            `${SUPABASE_URL}/qr_tokens?id=eq.${selectedTokenId}`,
            {
                method: "DELETE",
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );

        if (!res.ok) throw new Error("Failed to delete token");

        closeModal();
        await loadQRCodes();
        showToast("QR token deleted successfully", "success");
    } catch (error) {
        console.error(error);
        showToast("Failed to delete token", "error");
    }
}

/* ================= AUTO-REFRESH ================= */
// setInterval(loadQRCodes, 15000);

/* ================= INITIAL LOAD ================= */
document.addEventListener("DOMContentLoaded", () => {
    checkAndUpdateExpiredTokens(); // Check and update expired tokens first
    loadQRCodes();
    loadStats();
    // Check for expired tokens every 30 seconds
    setInterval(checkAndUpdateExpiredTokens, 30000);
});

/* ================= CLOSE MODAL EVENTS ================= */
document.addEventListener("click", function(event) {
    const modal = document.getElementById("tokenModal");
    if (modal && !modal.classList.contains("hidden") && event.target === modal) {
        closeModal();
    }
});

document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closeModal();
    }
});
