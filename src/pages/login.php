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
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../services/user-service.php';

// Defensive Guard 1: Redirect already authenticated users (prevent redundant session state)
if (isset($_SESSION['userId']) && !empty($_SESSION['userId'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: manage-users.php');
    } else {
        header('Location: product-list.php');
    }
    exit;
}

$error = '';
$submittedEmail = '';

// Process Form Submission defensively
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Data Sanitization (Strip hidden null bytes and whitespace)
    $rawEmail = str_replace("\0", '', $_POST['email'] ?? '');
    $submittedEmail = trim($rawEmail);
    $password = (string)($_POST['password'] ?? '');

    // 2. Guard Clause: Empty string edge case
    if ($submittedEmail === '' || $password === '') {
        http_response_code(400); // 400 Bad Request
        $error = 'Please fill in both email address and password.';
    }
    // 3. Guard Clause: Email format & boundary checks (RFC validation and DB VARCHAR bound)
    elseif (!filter_var($submittedEmail, FILTER_VALIDATE_EMAIL) || strlen($submittedEmail) > 150) {
        http_response_code(400); // 400 Bad Request
        $error = 'Invalid email address format or length exceeded.';
    }
    // 4. Guard Clause: Out-of-bounds password length (Max 72 bytes due to bcrypt limitation)
    elseif (strlen($password) > 72) {
        http_response_code(400); // 400 Bad Request
        $error = 'Password exceeds maximum acceptable character limit.';
    }
    // 5. Authentication Execution & Type Assertions
    else {
        $user = loginUser($submittedEmail, $password);

        // Guard Clause: Failed credential assertion
        if (!$user) {
            http_response_code(401); // 401 Unauthorized
            $error = 'Invalid email or password.';
        } 
        // Defensive check: Verify object/array structure before writing session
        else {
            // Safe key extraction supporting both associative array and User object
            $userId = is_object($user) ? $user->getUserId() : ($user['userId'] ?? $user['user_id'] ?? null);
            $role   = is_object($user) ? $user->getRole()   : ($user['role'] ?? 'customer');
            $name   = is_object($user) ? $user->getName()   : ($user['name'] ?? '');
            $email  = is_object($user) ? $user->getEmail()  : ($user['email'] ?? $submittedEmail);

            if ($userId === null) {
                http_response_code(500); // 500 Internal Server Error
                $error = 'Corrupted user record returned by service.';
            } else {
                session_regenerate_id(true); // Mitigate session fixation attacks
                $_SESSION['userId'] = (int)$userId;
                $_SESSION['name']   = (string)$name;
                $_SESSION['email']  = (string)$email;
                $_SESSION['role']   = (string)$role;

                if ($role === 'admin') {
                    header('Location: manage-users.php');
                } else {
                    header('Location: product-list.php');
                }
                exit;
            }
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Login to StationeryMart</h1>
<p class="page-subtitle">Sign in to shop, track your orders and manage your profile</p>

<!-- Output Pathway: Strictly escaped error message to mitigate Reflected XSS -->
<?php if ($error !== ''): ?>
    <div class="message-error" style="background:#fee2e2; border:1px solid #ef4444; color:#991b1b; padding:10px 14px; border-radius:4px; margin-bottom:16px;">
        <?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="post" action="login.php" novalidate>
    <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" for="email">Email Address</label>
        <!-- Escaped value persistence so user doesn't retype email on error -->
        <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-input" 
            value="<?= htmlspecialchars($submittedEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" 
            required 
            maxlength="150"
        >
    </div>

    <div class="form-group" style="margin-bottom: 18px;">
        <label class="form-label" for="password">Password</label>
        <input 
            type="password" 
            id="password" 
            name="password" 
            class="form-input" 
            required 
            maxlength="72"
        >
    </div>

    <button type="submit" class="btn btn-primary btn-block">Login</button>
</form>

<p class="page-subtitle" style="margin-top:18px; text-align:center;">
    Don't have an account? <a class="link-back" href="register.php">Register here</a>
</p>

<?php include __DIR__ . '/../includes/footer.php'; ?>