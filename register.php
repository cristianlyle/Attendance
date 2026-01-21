<?php
require 'db.php';

$error = '';
$success = '';

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? 'employee';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (isset($_POST['register'])) {

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {

        // Check if email exists
        $existing = supabase_get($table, "email=eq.$email&limit=1");

        if (!empty($existing)) {
            $error = "Email already exists. Please use another email or login.";
        } else {

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            supabase_post($table, [
                "name" => trim($name),
                "email" => trim($email),
                "role" => $role, // 🔹 NEW
                "password_hash" => $password_hash,
                "status" => "active"
            ]);

            $success = "Account created successfully!";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>
<link rel="stylesheet" href="css/register.css"></head>

</head>
<body>

<div class="container">
    <h2>Create Account</h2>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
        <button onclick="goToLogin()">Continue</button>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">

        <div class="form-group">
            <input type="text" name="name" placeholder="Full Name"
                   value="<?= htmlspecialchars($name) ?>" required>
        </div>

        <div class="form-group">
            <input type="email" name="email" placeholder="Email"
                   value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="form-group">
            <input type="password" id="password" name="password" placeholder="Password" required>
        </div>
        <div id="passwordMessage" class="password-message"></div>
        <div class="form-group">
            <input type="password" id="confirm_password" name="confirm_password"
                   placeholder="Confirm Password" required>
        </div>

       <label class="checkbox-label">
    <input type="checkbox" onclick="togglePassword()">
    Show Password
</label>

        <br><br><div class="select-role">
    <select name="role" required
        style="width:100%;padding:0.75rem;border:1px solid #ccc;border-radius:5px;">
        <option value="employee" <?= $role === 'employee' ? 'selected' : '' ?>>Employee</option>
        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>
</div>


        <button type="submit" name="register">Register</button>

        <p style="text-align:center;margin-top:10px;">
            Already have an account? <a href="login.php">Login</a>
        </p>

    </form>
    <?php endif; ?>
</div>

<script src="js/password-match.js"></script>
<script>
function goToLogin() {
    window.location.href = "login.php";
}
</script>

</body>
</html>
