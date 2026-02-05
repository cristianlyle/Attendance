const SUPABASE_URL = "https://sdqnovlgutbvoxsctirk.supabase.co/rest/v1";
const SUPABASE_KEY = "sb_publishable__N_eKBbedJtTPW9AHefR5Q_wRFiUXey";

const tbody = document.querySelector("#attendanceTable tbody");

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

/* ================= CALCULATE TOTAL HOURS ================= */
function calculateTotalHours(timeIn, timeOut) {
    if (!timeIn || !timeOut) return null;
    
    const inTime = new Date(timeIn);
    const outTime = new Date(timeOut);
    const diffMs = outTime - inTime;
    const diffHours = diffMs / (1000 * 60 * 60);
    
    if (diffHours <= 0) return null;
    
    const hours = Math.floor(diffHours);
    const minutes = Math.round((diffHours - hours) * 60);
    
    return `${hours}h ${minutes}m`;
}

/* ================= CALCULATE TOTAL HOURS IN DECIMAL ================= */
function calculateTotalHoursDecimal(timeIn, timeOut) {
    if (!timeIn || !timeOut) return 0;
    
    const inTime = new Date(timeIn);
    const outTime = new Date(timeOut);
    const diffMs = outTime - inTime;
    const diffHours = diffMs / (1000 * 60 * 60);
    
    return diffHours > 0 ? diffHours : 0;
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
        // Total Records
        const totalRes = await fetch(`${SUPABASE_URL}/attendance?select=id`, {
            headers: { apikey: SUPABASE_KEY, Authorization: `Bearer ${SUPABASE_KEY}` }
        });
        const totalData = await totalRes.json();
        document.getElementById("totalRecords").textContent = totalData.length || 0;

        // Today's Data
        const today = new Date().toISOString().split("T")[0];
        const todayRes = await fetch(`${SUPABASE_URL}/attendance?date=eq.${today}`, {
            headers: { apikey: SUPABASE_KEY, Authorization: `Bearer ${SUPABASE_KEY}` }
        });
        const todayData = await todayRes.json();
        
        let checkins = 0;
        let checkouts = 0;
        todayData.forEach(r => {
            if (r.time_in) checkins++;
            if (r.time_out) checkouts++;
        });
        
        document.getElementById("todayCheckins").textContent = checkins;
        document.getElementById("todayCheckouts").textContent = checkouts;
    } catch (error) {
        console.error("Error loading stats:", error);
    }
}

/* ================= FETCH USER NAME AND PROFILE IMAGE ================= */
async function getUserProfile(userId) {
    try {
        const res = await fetch(`${SUPABASE_URL}/users?id=eq.${userId}&select=name,profile_image`, {
            headers: { apikey: SUPABASE_KEY, Authorization: `Bearer ${SUPABASE_KEY}` }
        });
        const users = await res.json();
        if (users.length > 0) {
            return {
                name: users[0].name,
                profile_image: users[0].profile_image || null
            };
        }
        return { name: "Unknown", profile_image: null };
    } catch (error) {
        return { name: "Unknown", profile_image: null };
    }
}

/* ================= GROUP DATA BY DATE ================= */
function groupByDate(data) {
    const groups = {};
    
    data.forEach(record => {
        const date = record.date;
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(record);
    });
    
    return groups;
}

/* ================= FORMAT TOTAL HOURS ================= */
function formatTotalHours(decimalHours) {
    const hours = Math.floor(decimalHours);
    const minutes = Math.round((decimalHours - hours) * 60);
    return `${hours}h ${minutes}m`;
}

/* ================= TOGGLE DATE GROUP ================= */
function toggleDateGroup(date) {
    const groupRow = document.querySelector(`[data-date="${date}"]`);
    const employeeRows = document.querySelectorAll(`[data-parent-date="${date}"]`);
    const icon = groupRow.querySelector('.toggle-icon');
    
    if (groupRow.classList.contains('expanded')) {
        // Collapse
        groupRow.classList.remove('expanded');
        employeeRows.forEach(row => row.classList.add('hidden'));
        icon.classList.remove('bxs-chevron-down');
        icon.classList.add('bxs-chevron-right');
    } else {
        // Expand
        groupRow.classList.add('expanded');
        employeeRows.forEach(row => row.classList.remove('hidden'));
        icon.classList.remove('bxs-chevron-right');
        icon.classList.add('bxs-chevron-down');
    }
}

/* ================= FETCH ATTENDANCE ================= */
async function fetchAttendance() {
    if (!tbody) return;
    
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <i class='bx bx-loader-alt bx-spin text-4xl text-gray-300 mb-2'></i>
                    <p class="text-gray-500">Loading attendance records...</p>
                </div>
            </td>
        </tr>
    `;

    try {
        const res = await fetch(
            `${SUPABASE_URL}/attendance?order=created_at.desc`,
            {
                headers: {
                    apikey: SUPABASE_KEY,
                    Authorization: `Bearer ${SUPABASE_KEY}`
                }
            }
        );

        const data = await res.json();
        tbody.innerHTML = "";

        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class='bx bx-calendar-x text-5xl text-gray-300 mb-3'></i>
                            <p class="text-gray-500 text-lg">No attendance records found</p>
                            <p class="text-gray-400 text-sm">QR codes need to be scanned by employees</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        // Group data by date
        const groups = groupByDate(data);
        const sortedDates = Object.keys(groups).sort().reverse(); // Most recent first

        for (const date of sortedDates) {
            const records = groups[date];
            const totalHoursDecimal = records.reduce((sum, r) => {
                return sum + calculateTotalHoursDecimal(r.time_in, r.time_out);
            }, 0);
            const totalEmployees = records.length;
            const totalHoursFormatted = formatTotalHours(totalHoursDecimal);

            // Create date group row (summary)
            const groupRow = document.createElement("tr");
            groupRow.className = "bg-indigo-50 cursor-pointer hover:bg-indigo-100 transition-colors date-group";
            groupRow.setAttribute("data-date", date);
            groupRow.onclick = () => toggleDateGroup(date);
            
            groupRow.innerHTML = `
                <td colspan="8" class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <i class='bx bxs-chevron-right toggle-icon text-xl text-indigo-600 transition-transform'></i>
                            <div class="flex items-center gap-2">
                                <i class='bx bx-calendar text-indigo-600 text-xl'></i>
                                <span class="font-bold text-gray-800 text-lg">${date}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="bg-indigo-200 text-indigo-700 px-3 py-1 rounded-full text-sm font-medium">
                                    ${totalEmployees} Employee${totalEmployees > 1 ? 's' : ''}
                                </span>
                            </div>
                        </div>
                    </div>
                </td>
            `;
            tbody.appendChild(groupRow);

            // Create employee rows (hidden by default)
            for (const record of records) {
                const userProfile = await getUserProfile(record.user_id);
                const userName = userProfile.name;
                const profileImage = userProfile.profile_image;
                const userInitial = userName.charAt(0).toUpperCase();
                
                // Default placeholder image (using a data URL for a simple avatar)
                const placeholderImage = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2310B981'%3E%3Cpath d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E`;
                
                const employeeRow = document.createElement("tr");
                employeeRow.className = "hover:bg-gray-50 transition-colors employee-row hidden";
                employeeRow.setAttribute("data-parent-date", date);
                
                employeeRow.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center overflow-hidden">
                                ${profileImage ? 
                                    `<img src="${profileImage}" alt="Profile" class="w-full h-full object-cover" onerror="this.src='${placeholderImage}'">` :
                                    `<span class="text-green-600 font-semibold">${userInitial}</span>`
                                }
                            </div>
                            <span class="font-medium text-gray-800">${userName}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <i class='bx bx-calendar text-gray-400'></i>
                            <span class="text-gray-700">${record.date}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${record.time_in ? `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                                <i class='bx bxs-log-in-circle'></i>${toPHTimeOnly(record.time_in)}
                            </span>
                        ` : `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-sm">
                                <i class='bx bx-minus'></i>--
                            </span>
                        `}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${record.lunch_in ? `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                                <i class='bx bx-restaurant'></i>${toPHTimeOnly(record.lunch_in)}
                            </span>
                        ` : `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-sm">
                                <i class='bx bx-minus'></i>--
                            </span>
                        `}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${record.lunch_out ? `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">
                                <i class='bx bx-coffee'></i>${toPHTimeOnly(record.lunch_out)}
                            </span>
                        ` : `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-sm">
                                <i class='bx bx-minus'></i>--
                            </span>
                        `}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${record.time_out ? `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm font-medium">
                                <i class='bx bxs-log-out-circle'></i>${toPHTimeOnly(record.time_out)}
                            </span>
                        ` : `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-sm">
                                <i class='bx bx-minus'></i>--
                            </span>
                        `}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${calculateTotalHours(record.time_in, record.time_out) ? `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium">
                                <i class='bx bxs-time-five'></i>${calculateTotalHours(record.time_in, record.time_out)}
                            </span>
                        ` : `
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-sm">
                                <i class='bx bx-minus'></i>--
                            </span>
                        `}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm">
                            <i class='bx bxs-map-pin'></i>${record.location || "Unknown"}
                        </span>
                    </td>
                `;
                tbody.appendChild(employeeRow);
            }
        }
    } catch (error) {
        console.error("Error fetching attendance:", error);
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <i class='bx bxs-error-circle text-5xl text-red-300 mb-3'></i>
                        <p class="text-red-500 text-lg">Failed to load attendance records</p>
                        <p class="text-gray-400 text-sm">Please check your connection and try again</p>
                    </div>
                </td>
            </tr>
        `;
    }
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
    link.download = "attendance_report.csv";
    link.click();
    
    showToast("CSV exported successfully!", "success");
}

/* ================= INITIALIZE ================= */
document.addEventListener("DOMContentLoaded", () => {
    fetchAttendance();
    loadStats();
});
