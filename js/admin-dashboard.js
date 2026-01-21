const SUPABASE_URL = "https://sdqnovlgutbvoxsctirk.supabase.co/rest/v1";
const SUPABASE_KEY = "sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey";

const qrCanvas = document.getElementById("qrCodeCanvas");
const qrMsg = document.getElementById("qrMsg");
const qrTableBody = document.querySelector("#qrTable tbody");

let countdownInterval = null;

// ================= GENERATE QR =================
async function generateQRCode() {
    if (!qrCanvas) return;

    qrMsg.textContent = "";

    const locationSelect = document.getElementById("qrLocation");
    if (!locationSelect) {
        console.error("Location select missing");
        return;
    }

    const location = locationSelect.value;
    const token = crypto.randomUUID();

    const createdAt = new Date();
    const expiresAt = new Date(createdAt.getTime() + 5 * 60 * 1000);

    // ================= SAVE TO SUPABASE =================
    try {
        const res = await fetch(`${SUPABASE_URL}/qr_tokens`, {
            method: "POST",
            headers: {
                "apikey": SUPABASE_KEY,
                "Authorization": `Bearer ${SUPABASE_KEY}`,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                token,
                location_id: location,
                expires_at: expiresAt.toISOString(),
                is_active: true
            })
        });

        if (!res.ok) throw new Error("Insert failed");

    } catch (err) {
        console.error(err);
        qrMsg.textContent = "Failed to generate QR code";
        qrMsg.style.color = "red";
        return;
    }

    // ================= DRAW QR =================
    QRCode.toCanvas(qrCanvas, token, { width: 180 });

    document.getElementById("qrToken").textContent = token;
    document.getElementById("qrCreated").textContent = createdAt.toLocaleString();
    document.getElementById("qrLocationText").textContent = location;

    startCountdown(expiresAt);

    qrMsg.textContent = "QR Code generated successfully ✔";
    qrMsg.style.color = "green";

    loadQRCodes();
}

// ================= COUNTDOWN =================
function startCountdown(expiresAt) {
    const expiresEl = document.getElementById("qrExpires");
    if (countdownInterval) clearInterval(countdownInterval);

    countdownInterval = setInterval(() => {
        const diff = expiresAt - new Date();
        if (diff <= 0) {
            expiresEl.textContent = "Expired";
            clearInterval(countdownInterval);
            return;
        }
        const mins = Math.floor(diff / 60000);
        const secs = Math.floor((diff % 60000) / 1000);
        expiresEl.textContent = `${mins}m ${secs}s`;
    }, 1000);
}

// ================= LOAD ACTIVE QRS =================
async function loadQRCodes() {
    qrTableBody.innerHTML = "";

    const res = await fetch(
        `${SUPABASE_URL}/qr_tokens?is_active=eq.true&order=created_at.desc`,
        {
            headers: {
                "apikey": SUPABASE_KEY,
                "Authorization": `Bearer ${SUPABASE_KEY}`
            }
        }
    );

    const data = await res.json();
    data.forEach(row => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${row.token}</td>
            <td>${row.location_id}</td>
            <td>${new Date(row.expires_at).toLocaleString()}</td>
            <td><button onclick="deactivateQRCode('${row.id}')">Deactivate</button></td>
        `;
        qrTableBody.appendChild(tr);
    });
}

// ================= DEACTIVATE =================
async function deactivateQRCode(id) {
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
}

// ================= INIT =================
loadQRCodes();
