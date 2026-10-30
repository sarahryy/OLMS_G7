<?php
/**
 * File: login.php
 * Module: User & Authentication
 * Description: Authenticates a registered user and starts their session.
 *              Administrators are sent to the management screens and
 *              customers to the product catalogue.
 * Module author: Lu'lu'
 * Layout migrated to the shared header/footer on 27/10/2026.
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/user-service.php';
 
$error = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
 
    $user = loginUser($email, $password);
 
    if ($user) {
        session_regenerate_id(true);
        $_SESSION['userId'] = $user['userId'];
        $_SESSION['name']   = $user['name'];
        $_SESSION['email']  = $user['email'];
        $_SESSION['role']   = $user['role'];
 
        if ($user['role'] === 'admin') {
            header('Location: manage-users.php');
        } else {
            header('Location: product-list.php');
        }
        exit;
    }
 
    $error = 'Invalid email or password.';
}
 
$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">Login to StationeryMart</h1>
<p class="page-subtitle">Sign in to shop, track your orders and manage your profile</p>
 
<?php if ($error !== ''): ?>
    <div class="message-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
 
<form method="post" action="login.php">
    <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-input" required>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-input" required>
    </div>
 
    <button type="submit" class="btn btn-primary btn-block">Login</button>
</form>
 
<p class="page-subtitle" style="margin-top:18px; text-align:center;">
    Don't have an account? <a class="link-back" href="register.php">Register here</a>
</p>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>