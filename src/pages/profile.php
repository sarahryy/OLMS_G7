<?php
/**
 * File: profile.php
 * Module: User & Authentication
 * Description: Profile inspection and update interface hardened with 
 *              defensive input sanitization, regex format checks, 
 *              edge-case assertions, and explicit HTTP status codes.
 * Module Author: Lu'lu'
 */

require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/user-service.php';

requireLogin();

// 1. Defensive Guard: Assert session identifier integrity (Positive Integer Check)
$rawUserId = $_SESSION['userId'] ?? null;
$userId = filter_var($rawUserId, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if ($userId === false || $userId === null) {
    http_response_code(400); // 400 Bad Request
    session_destroy();
    die('<h1>400 Bad Request</h1><p>Invalid or corrupted session identity. Please sign in again.</p>');
}

$message = '';
$error   = '';

// 2. Input Pathway: Process Form Submission Defensively
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Data Sanitization: Strip null-byte characters and whitespace padding
    $rawName  = str_replace("\0", '', $_POST['name'] ?? '');
    $rawPhone = str_replace("\0", '', $_POST['phone_number'] ?? '');

    $name  = trim(strip_tags($rawName));
    $phone = trim($rawPhone);

    // B. Guard Clause: Empty string edge case
    if ($name === '' || $phone === '') {
        http_response_code(422); // 422 Unprocessable Entity
        $error = 'All fields are required. Name and phone number cannot be blank.';
    }
    // C. Guard Clause: Name length boundaries (VARCHAR(150) DB compatibility)
    elseif (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 150) {
        http_response_code(422);
        $error = 'Full Name must be between 2 and 150 characters long.';
    }
    // D. Guard Clause: Phone Number format validation via Regex (9-20 chars: digits, spaces, -, +)
    elseif (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) {
        http_response_code(422);
        $error = 'Invalid phone number format. Please provide 9 to 20 valid numeric digits.';
    }
    // E. Execution & State Persistence
    else {
        if (updateUserProfile($userId, $name, $phone)) {
            // Update session cache to maintain synchronization
            $_SESSION['name'] = $name;
            $message = 'Profile updated successfully.';
        } else {
            http_response_code(500); // 500 Internal Server Error
            $error = 'Database error: Failed to update profile details.';
        }
    }
}

// 3. Defensive Fetch: Retrieve current user data and assert record existence
$user = getUserById($userId);

// Guard Clause: Handle dangling session / deleted account edge case
if (!$user || !is_array($user)) {
    http_response_code(404); // 404 Not Found
    session_destroy();
    die('<h1>404 Not Found</h1><p>User account not found or has been deactivated. Please contact support.</p>');
}

// Ensure expected keys exist defensively to avoid undefined array key notices
$email         = $user['email'] ?? '';
$currentName   = $user['name'] ?? '';
$currentPhone  = $user['phone_number'] ?? '';
$loyaltyPoints = isset($user['loyalty_points']) ? (int)$user['loyalty_points'] : null;

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">My Profile</h1>
<p class="page-subtitle">
    Signed in as <?= htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    (<?= htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)
</p>

<!-- Output Pathway: Escaped system feedback messages -->
<?php if ($message !== ''): ?>
    <div class="message-success" style="background:#dcfce7; border:1px solid #22c55e; color:#15803d; padding:10px 14px; border-radius:4px; margin-bottom:16px;">
        <?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="message-error" style="background:#fee2e2; border:1px solid #ef4444; color:#991b1b; padding:10px 14px; border-radius:4px; margin-bottom:16px;">
        <?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="post" action="profile.php" novalidate>
    <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" for="email">Email (Account Identifier)</label>
        <!-- Read-only email: Prevents identity tampering -->
        <input 
            type="email" 
            id="email" 
            class="form-input"
            value="<?= htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" 
            disabled
        >
        <span class="form-hint" style="font-size:0.85rem; color:#64748b;">Your email is locked to protect relational order integrity.</span>
    </div>

    <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" for="name">Full Name</label>
        <input 
            type="text" 
            id="name" 
            name="name" 
            class="form-input"
            value="<?= htmlspecialchars($currentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" 
            required 
            minlength="2" 
            maxlength="150"
        >
    </div>

    <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" for="phone_number">Phone Number</label>
        <input 
            type="text" 
            id="phone_number" 
            name="phone_number" 
            class="form-input"
            value="<?= htmlspecialchars($currentPhone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" 
            required 
            maxlength="20"
        >
    </div>

    <!-- Defensive Output Assertion: Display loyalty points only when valid non-negative integer -->
    <?php if ($loyaltyPoints !== null && $loyaltyPoints >= 0): ?>
        <div class="form-group" style="margin-bottom: 18px;">
            <label class="form-label" for="loyalty">Loyalty Points</label>
            <input 
                type="text" 
                id="loyalty" 
                class="form-input"
                value="<?= $loyaltyPoints ?> pts" 
                disabled
            >
        </div>
    <?php endif; ?>

    <div class="form-actions" style="display:flex; gap:12px; align-items:center;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a class="link-back" href="product-list.php" style="text-decoration:none; color:#2563eb;">Back to Catalogue</a>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>