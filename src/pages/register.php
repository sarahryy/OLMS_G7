<?php
require_once __DIR__ . '/../services/user-service.php';

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name)) $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please provide a valid email address.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters long.";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $customer = new Customer($name, $email, $password, $phone);
        if (registerCustomer($customer)) {
            $successMessage = "Account registered successfully! You can now log in.";
        } else {
            $errors[] = "Email is already registered or database error occurred.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - StationeryMart</title>
    <link rel="stylesheet" href="../../public/assets/css/main-style.css">
    <style>
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 25px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); font-family: sans-serif; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 12px; background: #1a365d; color: #fff; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        .alert-danger { background: #fed7d7; color: #9b2c2c; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-success { background: #c6f6d5; color: #22543d; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Customer Registration</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert-danger">
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <?php if ($successMessage): ?>
        <div class="alert-success"><?= htmlspecialchars($successMessage) ?> <a href="login.php">Login here</a>.</div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="form-group"><label>Full Name *</label><input type="text" name="name" required></div>
        <div class="form-group"><label>Email Address *</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Phone Number *</label><input type="text" name="phone_number" required></div>
        <div class="form-group"><label>Password (min. 8 characters) *</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm_password" required></div>
        <button type="submit">Register Account</button>
    </form>
</div>
</body>
</html>