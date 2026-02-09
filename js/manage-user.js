function openAddUserModal() {
    const modal = document.getElementById("addUserModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function closeAddUserModal() {
    const modal = document.getElementById("addUserModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}

function closeSuccessModal() {
    const modal = document.getElementById("successModal");
    if (modal) {
        modal.classList.add("hidden");
    }
}

function closeErrorModal() {
    const modal = document.getElementById("errorModal");
    if (modal) {
        modal.classList.add("hidden");
    }
}

// ================= USER DETAIL MODAL FUNCTIONS =================
let currentUserId = null;

function openUserDetailModal(userId) {
    currentUserId = userId;
    const modal = document.getElementById("userDetailModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    
    // Reset to view mode
    showUserDetailView();
}

function closeUserDetailModal() {
    const modal = document.getElementById("userDetailModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    currentUserId = null;
}

function showUserDetailView() {
    document.getElementById("userDetailView").classList.remove("hidden");
    document.getElementById("userDetailEdit").classList.add("hidden");
}

function showUserDetailEdit() {
    document.getElementById("userDetailView").classList.add("hidden");
    document.getElementById("userDetailEdit").classList.remove("hidden");
}

// ================= CONFIRM DELETE MODAL FUNCTIONS =================
function openConfirmDeleteModal() {
    const modal = document.getElementById("confirmDeleteModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
}

function closeConfirmDeleteModal() {
    const modal = document.getElementById("confirmDeleteModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}

// ================= USER ACTIONS =================
function editUser() {
    if (!currentUserId) return;
    
    // Get user data from the row
    const row = document.querySelector(`.user-row[data-user-id="${currentUserId}"]`);
    if (!row) return;
    
    const userName = row.dataset.userName;
    const userEmail = row.dataset.userEmail;
    const userRole = row.dataset.userRole;
    const userStatus = row.dataset.userStatus;
    const userProfileImage = row.dataset.userProfileImage;
    
    // Populate edit form
    document.getElementById("editUserId").value = currentUserId;
    document.getElementById("editName").value = userName;
    document.getElementById("editEmail").value = userEmail;
    document.getElementById("editRole").value = userRole;
    document.getElementById("editRoleValue").value = userRole;
    document.getElementById("editStatus").value = userStatus;
    document.getElementById("editNameLabel").textContent = userName;
    
    // Set initial for edit mode
    const editImagePreview = document.getElementById("editImagePreview");
    if (userProfileImage && userProfileImage.trim() !== '') {
        editImagePreview.innerHTML = '<img src="' + userProfileImage + '" alt="Profile" class="w-full h-full object-cover">';
    } else {
        editImagePreview.innerHTML = '<span class="text-3xl font-bold text-gray-500" id="editInitial">' + userName.charAt(0).toUpperCase() + '</span>';
    }
    
    // Switch to edit mode
    showUserDetailEdit();
}

function cancelEdit() {
    showUserDetailView();
}

function deleteUser() {
    if (!currentUserId) return;
    
    // Set the user ID in the delete form
    document.getElementById("deleteUserId").value = currentUserId;
    
    // Close user detail modal and open confirm delete modal
    closeUserDetailModal();
    openConfirmDeleteModal();
}

// ================= IMAGE PREVIEW =================
function previewImage(event, previewId) {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById(previewId);
        preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
    };
    reader.readAsDataURL(file);
}

// ================= PASSWORD VALIDATION =================
const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirm_password");
const message = document.getElementById("passwordMessage");
const registerBtn = document.querySelector("button[name='register']");

function checkPasswordMatch() {
    if (!confirmPassword.value) {
        message.textContent = "";
        message.className = "text-sm mt-1";
        registerBtn.disabled = true;
        return;
    }

    if (password.value === confirmPassword.value) {
        message.innerHTML = '<i class="bx bxs-check-circle text-green-500 mr-1"></i>Passwords match';
        message.className = "text-sm mt-1 text-green-600";
        registerBtn.disabled = false;
    } else {
        message.innerHTML = '<i class="bx bxs-x-circle text-red-500 mr-1"></i>Passwords do not match';
        message.className = "text-sm mt-1 text-red-500";
        registerBtn.disabled = true;
    }
}

// Toggle show/hide password
function togglePassword() {
    const showPassword = document.getElementById("showPassword");
    if (showPassword.checked) {
        password.type = "text";
        confirmPassword.type = "text";
    } else {
        password.type = "password";
        confirmPassword.type = "password";
    }
}

// Attach events (check if modal exists)
if (password && confirmPassword) {
    password.addEventListener("input", checkPasswordMatch);
    confirmPassword.addEventListener("input", checkPasswordMatch);
}

// Close modal when clicking outside
document.addEventListener("click", function(event) {
    const modal = document.getElementById("addUserModal");
    const userDetailModal = document.getElementById("userDetailModal");
    const successModal = document.getElementById("successModal");
    const errorModal = document.getElementById("errorModal");
    const confirmDeleteModal = document.getElementById("confirmDeleteModal");

    if (modal && !modal.classList.contains("hidden")) {
        if (event.target === modal) {
            closeAddUserModal();
        }
    }
    
    if (userDetailModal && !userDetailModal.classList.contains("hidden")) {
        if (event.target === userDetailModal) {
            closeUserDetailModal();
        }
    }
    
    if (successModal && !successModal.classList.contains("hidden")) {
        if (event.target === successModal) {
            closeSuccessModal();
        }
    }
    
    if (errorModal && !errorModal.classList.contains("hidden")) {
        if (event.target === errorModal) {
            closeErrorModal();
        }
    }

    if (confirmDeleteModal && !confirmDeleteModal.classList.contains("hidden")) {
        if (event.target === confirmDeleteModal) {
            closeConfirmDeleteModal();
        }
    }
});

// Close modals with Escape key
document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closeAddUserModal();
        closeUserDetailModal();
        closeSuccessModal();
        closeErrorModal();
        closeConfirmDeleteModal();
    }
});

// ================= CLICKABLE TABLE ROWS =================
document.addEventListener("DOMContentLoaded", function() {
    const userRows = document.querySelectorAll(".user-row");
    
    userRows.forEach(row => {
        row.addEventListener("click", function() {
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            const userEmail = this.dataset.userEmail;
            const userRole = this.dataset.userRole;
            const userStatus = this.dataset.userStatus;
            const userCreated = this.dataset.userCreated;
            const userProfileImage = this.dataset.userProfileImage;
            
            // Populate view mode
            document.getElementById("viewName").textContent = userName;
            document.getElementById("viewEmail").textContent = userEmail;
            
            // Profile image
            const viewProfileImage = document.getElementById("viewProfileImage");
            if (userProfileImage && userProfileImage.trim() !== '') {
                viewProfileImage.innerHTML = '<img src="' + userProfileImage + '" alt="Profile" class="w-full h-full object-cover">';
            } else {
                viewProfileImage.innerHTML = '<span class="text-3xl font-bold text-gray-500" id="viewInitial">' + userName.charAt(0).toUpperCase() + '</span>';
            }
            
            // Role badge
            const roleBadge = document.getElementById("viewRole");
            roleBadge.innerHTML = userRole === 'admin' 
                ? '<i class="bx bxs-crown text-purple-500"></i> Admin'
                : '<i class="bx bxs-user-badge text-green-500"></i> Employee';
            
            // Status badge
            const statusBadge = document.getElementById("viewStatus");
            if (userStatus === 'active') {
                statusBadge.innerHTML = ' Active';
                statusBadge.className = "inline-flex items-center gap-1 px-3 py-1 mt-2 rounded-full text-sm font-medium bg-green-100 text-green-700";
            } else {
                statusBadge.innerHTML = 'Inactive';
                statusBadge.className = "inline-flex items-center gap-1 px-3 py-1 mt-2 rounded-full text-sm font-medium bg-red-100 text-red-700";
            }
            
            document.getElementById("viewRoleDetail").textContent = userRole === 'Employee';
            document.getElementById("viewCreated").textContent = new Date(userCreated).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Open modal
            openUserDetailModal(userId);
        });
    });
});
