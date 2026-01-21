<?php
require 'db.php';
$error = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user by email
    $users = supabase_get($table, "email=eq.$email");

    if (isset($users[0])) {
        $user = $users[0];

        // Verify hashed password
        if (password_verify($password, $user['password_hash'])) {
            // Save session
            $_SESSION['user'] = [
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
                exit();
            } else {
                header("Location: employee_dashboard.php");
                exit();
            }
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="container">
    <h2>Login</h2>
    <?php if($error) echo "<div class='error'>$error</div>"; ?>
    <form method="POST" action="">
        <div class="form-group"><input type="email" name="email" placeholder="Email" required></div>
        <div class="form-group"><input type="password" name="password" placeholder="Password" required></div>
        <button type="submit" name="login">Login</button>
        <p style="text-align:center;margin-top:10px;">No account? <a href="register.php">Register here</a></p>
    </form>
</div>

</body>
</html>
