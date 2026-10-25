<?php
session_start();
require_once __DIR__ . '/../services/user-service.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = loginUser($email, $password);
    if ($user) {
        session_regenerate_id(true);
        $_SESSION['userId'] = $user['userId'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header('Location: manage-users.php');
        } else {
            header('Location: profile.php');
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - StationeryMart</title>
    <style>
        .container { max-width: 420px; margin: 50px auto; background: #fff; padding: 25px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); font-family: sans-serif; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 12px; background: #1a365d; color: #fff; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        .alert-danger { background: #fed7d7; color: #9b2c2c; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Login to StationeryMart</h2>
    <?php if ($error): ?><div class="alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" action="">
        <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
        <button type="submit">Login</button>
    </form>
    <p style="margin-top:15px; text-align:center;">Don't have an account? <a href="register.php">Register</a></p>
</div>
</body>
</html>