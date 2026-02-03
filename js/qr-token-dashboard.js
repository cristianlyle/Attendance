const SUPABASE_URL = "https://sdqnovlgutbvoxsctirk.supabase.co/rest/v1";
const SUPABASE_KEY = "sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey";

const qrTableBody = document.querySelector("#qrTable tbody");
let selectedTokenId = null;

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

/* ================= LOAD QR TOKENS ================= */
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
        const res = await fetch(
            `${SUPABASE_URL}/qr_tokens?order=created_at.desc`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );

        if (!res.ok) throw new Error("Failed to fetch QR tokens");

        const data = await res.json();
        const now = new Date();
        qrTableBody.innerHTML = "";

        if (data.length === 0) {
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

        data.forEach(row => {
            const tr = document.createElement("tr");
            tr.className = "hover:bg-gray-50 transition-colors cursor-pointer";
            tr.onclick = () => openModal(row);

            // Compute status dynamically
            let statusText = "Expired";
            let statusClass = "bg-red-100 text-red-700";
            let statusIcon = "bx-x-circle";

            if (row.is_active && new Date(row.expires_at) > now) {
                statusText = "Active";
                statusClass = "bg-green-100 text-green-700";
                statusIcon = "bx-check-circle";
            }

            tr.innerHTML = `
                <td class="px-6 py-4">
                    <span class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg font-mono text-sm break-all">
                        ${row.token.substring(0, 12)}...
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">
                        <i class='bx bxs-map-pin'></i>${row.location_id}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <i class='bx bx-time-five text-gray-400'></i>
                        ${toPHTime(row.created_at)}
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <i class='bx bx-timer text-gray-400'></i>
                        ${toPHTime(row.expires_at)}
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 ${statusClass} rounded-full text-sm font-medium">
                        <i class='bx ${statusIcon}'></i>${statusText}
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
    let statusText = "Expired";
    let statusClass = "text-red-600";
    let statusIcon = "bx-x-circle";

    if (row.is_active && new Date(row.expires_at) > now) {
        statusText = "Active";
        statusClass = "text-green-600";
        statusIcon = "bx-check-circle";
    }

    document.getElementById("modalToken").textContent = row.token;
    document.getElementById("modalLocation").textContent = row.location_id;
    document.getElementById("modalCreated").textContent = toPHTime(row.created_at);
    document.getElementById("modalExpires").textContent = toPHTime(row.expires_at);
    
    const statusEl = document.getElementById("modalStatus");
    statusEl.innerHTML = `<i class='bx ${statusIcon} ${statusClass} mr-1'></i>${statusText}`;
    statusEl.className = `font-medium ${statusClass}`;

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
setInterval(loadQRCodes, 15000);

/* ================= INITIAL LOAD ================= */
document.addEventListener("DOMContentLoaded", () => {
    loadQRCodes();
    loadStats();
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
