// Get DOM elements
const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirm_password");
const message = document.getElementById("passwordMessage");
const registerBtn = document.querySelector("button[name='register']");

// Check password match
function checkPasswordMatch() {
    // Only show message if confirm password is not empty
    if (confirmPassword.value.length === 0) {
        message.textContent = "";
                message.style.fontSize = "15px";
                message.style.marginBottom = "5px";
        registerBtn.disabled = true;
        return;
    }

    if (password.value === confirmPassword.value) {
        message.textContent = "Passwords match ";
        message.style.color = "green";
        registerBtn.disabled = false;
    } else {
        message.textContent = "Passwords do not match ";
        message.style.color = "red";
        registerBtn.disabled = true;
    }
}

// Toggle show/hide password
function togglePassword() {
    const type = password.type === "password" ? "text" : "password";
    password.type = type;
    confirmPassword.type = type;
}

// Attach events
password.addEventListener("input", checkPasswordMatch);
confirmPassword.addEventListener("input", checkPasswordMatch);
