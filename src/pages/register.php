<?php
/**
 * File: register.php
 * Module: User & Authentication
 * Description: Registration form for new customer accounts. Input is
 *              validated before the account is created, and each failed
 *              rule is reported back to the user.
 * Module author: Lu'lu'
 * Layout migrated to the shared header/footer on 27/10/2026.
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/user-service.php';
 
$errors         = [];
$successMessage = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone_number'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
 
    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
 
    if (empty($errors)) {
        $customer = new Customer($name, $email, $password, $phone);
        if (registerCustomer($customer)) {
            $successMessage = 'Account registered successfully.';
        } else {
            $errors[] = 'Email is already registered, or a database error occurred.';
        }
    }
}
 
$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">Customer Registration</h1>
<p class="page-subtitle">Create an account to start shopping at StationeryMart</p>
 
<?php if (!empty($errors)): ?>
    <div class="message-error">
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
 
<?php if ($successMessage !== ''): ?>
    <div class="message-success">
        <?= htmlspecialchars($successMessage) ?>
        <a class="link-back" href="login.php">Login here</a>.
    </div>
<?php endif; ?>
 
<form method="post" action="register.php">
    <div class="form-group">
        <label class="form-label" for="name">Full Name *</label>
        <input type="text" id="name" name="name" class="form-input" required>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="email">Email Address *</label>
        <input type="email" id="email" name="email" class="form-input" required>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="phone_number">Phone Number *</label>
        <input type="text" id="phone_number" name="phone_number" class="form-input" required>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="password">Password *</label>
        <input type="password" id="password" name="password" class="form-input" required>
        <span class="form-hint">Minimum 8 characters.</span>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm Password *</label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
    </div>
 
    <button type="submit" class="btn btn-primary btn-block">Register Account</button>
</form>
 
<p class="page-subtitle" style="margin-top:18px; text-align:center;">
    Already have an account? <a class="link-back" href="login.php">Login here</a>
</p>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 