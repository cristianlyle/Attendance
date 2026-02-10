// ================= LEAVE MANAGEMENT JS =================

let currentFilter = 'pending';
let allRequests = [];

// Update current time
function updateTime() {
    const now = new Date();
    const timeEl = document.getElementById('currentTime');
    if (timeEl) {
        timeEl.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }
}
updateTime();
setInterval(updateTime, 1000);

// Load leave requests on page load
document.addEventListener('DOMContentLoaded', function() {
    loadLeaveRequests();
    loadStats();
});

function loadLeaveRequests() {
    const container = document.getElementById('leaveRequestsContainer');
    if (!container) return;
    
    container.innerHTML = '<div class="text-center py-8 text-[#94A3B8]">Loading leave requests...</div>';
    
    const formData = new FormData();
    formData.append('action', 'get_all_requests');
    
    fetch('leave-request-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && Array.isArray(data.data)) {
            allRequests = data.data;
            renderRequests(currentFilter);
        } else {
            container.innerHTML = '<div class="text-center py-8 text-[#94A3B8]">No leave requests found</div>';
            allRequests = [];
        }
    })
    .catch(error => {
        container.innerHTML = '<div class="text-center py-8 text-red-500">Failed to load leave requests</div>';
        console.error('Error:', error);
        allRequests = [];
    });
}

function loadStats() {
    const formData = new FormData();
    formData.append('action', 'get_all_requests');
    
    fetch('leave-request-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && Array.isArray(data.data)) {
            const requests = data.data;
            const pending = requests.filter(r => r.status === 'pending').length;
            const approved = requests.filter(r => r.status === 'approved').length;
            const rejected = requests.filter(r => r.status === 'rejected').length;
            
            const pendingEl = document.getElementById('pendingCount');
            const approvedEl = document.getElementById('approvedCount');
            const rejectedEl = document.getElementById('rejectedCount');
            
            if (pendingEl) pendingEl.textContent = pending;
            if (approvedEl) approvedEl.textContent = approved;
            if (rejectedEl) rejectedEl.textContent = rejected;
        }
    });
}

function filterRequests(filter) {
    currentFilter = filter;
    
    // Update button styles
    document.querySelectorAll('.filter-btn').forEach(btn => {
        if (btn.dataset.filter === filter) {
            btn.classList.remove('bg-[#F1F5F9]', 'text-[#475569]', 'hover:bg-[#E2E8F0]');
            btn.classList.add('bg-[#166534]', 'text-white');
        } else {
            btn.classList.add('bg-[#F1F5F9]', 'text-[#475569]', 'hover:bg-[#E2E8F0]');
            btn.classList.remove('bg-[#166534]', 'text-white');
        }
    });
    
    renderRequests(filter);
}

function renderRequests(filter) {
    const container = document.getElementById('leaveRequestsContainer');
    if (!container) return;
    
    let requests = allRequests;
    if (filter !== 'all') {
        requests = allRequests.filter(r => r.status === filter);
    }
    
    if (requests.length === 0) {
        container.innerHTML = `<div class="text-center py-8 text-[#94A3B8]">No ${filter !== 'all' ? filter : ''} leave requests</div>`;
        return;
    }
    
    container.innerHTML = `
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F8FAFC]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Leave Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Duration</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-[#475569] uppercase tracking-wider">Status</th>

                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        ${requests.map(request => {
                            const user = request.users || {};
                            const profileImage = user.profile_image || getProfileImage(user.name);
                            const initials = getInitials(user.name);
                            const statusClass = {
                                'pending': 'bg-[#FEF3C7] text-[#D97706]',
                                'approved': 'bg-[#DCFCE7] text-[#166534]',
                                'rejected': 'bg-[#FEE2E2] text-[#DC2626]'
                            }[request.status];
                            const statusIcon = {
                                'pending': '<i class="bx bx-time-five"></i>',
                                'approved': '<i class="bx bx-check-circle"></i>',
                                'rejected': '<i class="bx bx-x-circle"></i>'
                            }[request.status];
                            
                            return `
                                <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="viewRequestDetails('${request.id}')">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-[#F1F5F9] rounded-full flex items-center justify-center overflow-hidden flex-shrink-0">
                                                ${profileImage.includes('svg') 
                                                    ? `<span class="text-sm font-semibold text-[#475569]">${initials}</span>`
                                                    : `<img src="${profileImage}" alt="Profile" class="w-full h-full object-cover">`
                                                }
                                            </div>
                                            <div>
                                                <p class="font-medium text-[#0F172A]">${escapeHtml(user.name || 'Unknown')}</p>
                                                <p class="text-xs text-[#475569]">${escapeHtml(user.email || '')}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-[#0F172A]">${escapeHtml(request.leave_type)}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-[#475569]">${formatDate(request.start_date)} - ${formatDate(request.end_date)}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium ${statusClass} flex items-center gap-1 w-fit">
                                            ${statusIcon} ${ucfirst(request.status)}
                                        </span>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function viewRequestDetails(requestId) {
    const request = allRequests.find(r => r.id === requestId);
    if (!request) return;
    
    const user = request.users || {};
    const profileImage = user.profile_image || getProfileImage(user.name);
    const initials = getInitials(user.name);
    const statusClass = {
        'pending': 'bg-[#FEF3C7] text-[#D97706]',
        'approved': 'bg-[#DCFCE7] text-[#166534]',
        'rejected': 'bg-[#FEE2E2] text-[#DC2626]'
    }[request.status];
    
    const modal = document.getElementById('reviewModal');
    const modalContent = document.getElementById('modalContent');
    const modalTitle = document.getElementById('modalTitle');
    
    if (!modal || !modalContent || !modalTitle) return;
    
    modalTitle.textContent = 'Leave Request Details';
    
    modalContent.innerHTML = `
        <!-- Employee Info -->
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100">
            <div class="w-16 h-16 bg-[#F1F5F9] rounded-full flex items-center justify-center overflow-hidden flex-shrink-0">
                ${profileImage.includes('svg') 
                    ? `<span class="text-xl font-semibold text-[#475569]">${initials}</span>`
                    : `<img src="${profileImage}" alt="Profile" class="w-full h-full object-cover">`
                }
            </div>
            <div>
                <h4 class="font-semibold text-[#0F172A] text-lg">${escapeHtml(user.name || 'Unknown')}</h4>
                <p class="text-sm text-[#475569]">${escapeHtml(user.email || '')}</p>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ${statusClass} mt-2">
                    ${request.status === 'pending' ? '<i class="bx bx-time-five"></i>' : 
                      request.status === 'approved' ? '<i class="bx bx-check-circle"></i>' : 
                      '<i class="bx bx-x-circle"></i>'}
                    ${ucfirst(request.status)}
                </span>
            </div>
        </div>
        
        <!-- Leave Details -->
        <div class="space-y-4">
            <div class="bg-[#F8FAFC] rounded-lg p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[#94A3B8] text-xs uppercase tracking-wide">Leave Type</p>
                        <p class="font-medium text-[#0F172A]">${escapeHtml(request.leave_type)}</p>
                    </div>
                    <div>
                        <p class="text-[#94A3B8] text-xs uppercase tracking-wide">Duration</p>
                        <p class="font-medium text-[#0F172A]">${formatDate(request.start_date)} - ${formatDate(request.end_date)}</p>
                    </div>
                </div>
            </div>
            
            ${request.reason ? `
                <div>
                    <p class="text-[#94A3B8] text-xs uppercase tracking-wide mb-1">Reason</p>
                    <p class="text-sm text-[#475569] bg-[#F8FAFC] rounded-lg p-3">${escapeHtml(request.reason)}</p>
                </div>
            ` : ''}
            
            ${request.admin_notes ? `
                <div>
                    <p class="text-[#94A3B8] text-xs uppercase tracking-wide mb-1">Admin Notes</p>
                    <p class="text-sm text-[#475569] bg-[#F8FAFC] rounded-lg p-3">${escapeHtml(request.admin_notes)}</p>
                </div>
            ` : ''}
            
            <div class="flex items-center justify-between text-xs text-[#94A3B8] pt-2">
                <span>Submitted: ${formatDateTime(request.created_at)}</span>
                ${request.reviewed_at ? `<span>Reviewed: ${formatDateTime(request.reviewed_at)}</span>` : ''}
            </div>
        </div>
        
        ${request.status === 'pending' ? `
            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button onclick="openReviewModal('${request.id}', 'approved')" 
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-[#22C55E] hover:bg-[#16A34A] text-white rounded-lg font-medium transition-colors">
                    <i class='bx bx-check'></i> Approve
                </button>
                <button onclick="openReviewModal('${request.id}', 'rejected')" 
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-[#EF4444] hover:bg-[#DC2626] text-white rounded-lg font-medium transition-colors">
                    <i class='bx bx-x'></i> Reject
                </button>
            </div>
        ` : ''}
    `;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function openReviewModal(requestId, action) {
    const request = allRequests.find(r => r.id === requestId);
    if (!request) return;
    
    const modal = document.getElementById('reviewModal');
    const modalContent = document.getElementById('modalContent');
    const modalTitle = document.getElementById('modalTitle');
    
    if (!modal || !modalContent || !modalTitle) return;
    
    modalTitle.textContent = action === 'approved' ? 'Approve Leave Request' : 'Reject Leave Request';
    const user = request.users || {};
    
    modalContent.innerHTML = `
        <p class="text-sm text-[#475569] mb-4">
            You are about to <strong>${action}</strong> leave request for <strong>${escapeHtml(user.name || 'Unknown')}</strong>.
        </p>
        <div class="bg-[#F8FAFC] rounded-lg p-4 mb-4">
            <p class="text-sm"><strong>Type:</strong> ${escapeHtml(request.leave_type)}</p>
            <p class="text-sm"><strong>Duration:</strong> ${formatDate(request.start_date)} - ${formatDate(request.end_date)}</p>
            ${request.reason ? `<p class="text-sm mt-2"><strong>Reason:</strong> ${escapeHtml(request.reason)}</p>` : ''}
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-[#475569] mb-2">Notes (optional)</label>
            <textarea id="reviewNotes" rows="3" placeholder="Add a note for the employee..."
                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#166534] focus:border-transparent text-sm resize-none"></textarea>
        </div>
        <div class="flex gap-3">
            <button onclick="closeModal()" 
                class="flex-1 px-4 py-2.5 border border-gray-200 rounded-lg text-[#475569] hover:bg-[#F8FAFC] transition-colors">
                Cancel
            </button>
            <button onclick="submitReview('${requestId}', '${action}')" 
                class="flex-1 px-4 py-2.5 ${action === 'approved' ? 'bg-[#22C55E] hover:bg-[#16A34A]' : 'bg-[#EF4444] hover:bg-[#DC2626]'} text-white rounded-lg font-medium transition-colors">
                ${action === 'approved' ? 'Approve' : 'Reject'}
            </button>
        </div>
    `;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function submitReview(requestId, status) {
    const notesEl = document.getElementById('reviewNotes');
    const notes = notesEl ? notesEl.value : '';
    
    const formData = new FormData();
    formData.append('action', 'review');
    formData.append('request_id', requestId);
    formData.append('status', status);
    formData.append('admin_notes', notes);
    
    fetch('leave-request-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeModal();
            loadLeaveRequests();
            loadStats();
        } else {
            showToast(data.error || 'Failed to process request', 'error');
        }
    })
    .catch(error => {
        showToast('An error occurred', 'error');
        console.error('Error:', error);
    });
}

function getProfileImage(name) {
    return `data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23c0c0c0"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>`;
}

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function formatDate(dateStr) {
    if (!dateStr) return '--';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatDateTime(dateStr) {
    if (!dateStr) return '--';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = `fixed px-4 py-2.5 rounded-lg shadow-lg transform transition-all duration-300 z-50 text-sm ${
        type === 'success' ? 'bg-[#22C55E] text-white' : 'bg-[#EF4444] text-white'
    }`;
    toast.style.top = '100px';
    toast.style.right = '20px';
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
    }, 3000);
}

// Close modal on outside click
document.getElementById('reviewModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
