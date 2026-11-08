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
 
require_once __DIR__ . '/../services/validator.php';
require_once __DIR__ . '/../services/user-service.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize & Assert Inputs
    $name = Validator::sanitizeString($_POST['name'] ?? '');
    $email = Validator::validateEmail($_POST['email'] ?? null);
    $phone = Validator::validatePhone($_POST['phone_number'] ?? null);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // 2. Guard Clause: Empty string & out-of-bounds name length
    if (empty($name) || strlen($name) < 2 || strlen($name) > 150) {
        $errors[] = "Full name must be between 2 and 150 characters.";
    }

    // 3. Guard Clause: Malformed email syntax
    if ($email === null) {
        $errors[] = "Please provide a valid email address (e.g., user@example.com).";
    }

    // 4. Guard Clause: Phone format
    if ($phone === null) {
        $errors[] = "Phone number must contain between 9 and 15 digits.";
    }

    // 5. Guard Clause: Password strength and match
    if (!Validator::validatePassword($password)) {
        $errors[] = "Password must be between 8 and 72 characters.";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // 6. Execution & HTTP Status Handling
    if (!empty($errors)) {
        http_response_code(422); // 422 Unprocessable Entity
    } else {
        $registered = registerCustomer($name, $email, $password, $phone);
        if (!$registered) {
            http_response_code(409); // 409 Conflict: Duplicate record or DB constraint failure
            $errors[] = "Email is already registered or a system error occurred.";
        } else {
            header("Location: login.php?registered=1");
            exit();
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
 