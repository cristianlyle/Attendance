 function togglePassword() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const showPassword = document.getElementById('showPassword');
            
            if (showPassword.checked) {
                password.type = 'text';
                confirmPassword.type = 'text';
            } else {
                password.type = 'password';
                confirmPassword.type = 'password';
            }
        }

        // Password matching validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const message = document.getElementById('passwordMessage');
        const registerBtn = document.getElementById('registerBtn');

        function checkPasswordMatch() {
            if (!confirmPassword.value) {
                message.textContent = '';
                registerBtn.disabled = true;
                return;
            }

            if (password.value === confirmPassword.value) {
                message.textContent = '✓ Passwords match';
                message.className = 'text-sm mt-1 text-green-600';
                registerBtn.disabled = false;
            } else {
                message.textContent = '✗ Passwords do not match';
                message.className = 'text-sm mt-1 text-red-500';
                registerBtn.disabled = true;
            }
        }

        password.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);