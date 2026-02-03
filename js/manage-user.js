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
    const successModal = document.getElementById("successModal");
    const errorModal = document.getElementById("errorModal");

    if (modal && !modal.classList.contains("hidden")) {
        if (event.target === modal) {
            closeAddUserModal();
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
});

// Close modals with Escape key
document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closeAddUserModal();
        closeSuccessModal();
        closeErrorModal();
    }
});
