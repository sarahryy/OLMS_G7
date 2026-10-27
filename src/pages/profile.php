<?php
/**
 * File: profile.php
 * Module: User & Authentication
 * Description: Lets a signed-in user view their account details and update
 *              their name and phone number. The email address is the account
 *              identifier and is shown read-only.
 * Module author: Lu'lu'
 * Layout migrated to the shared header/footer on 27/10/2026.
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/user-service.php';
 
requireLogin();
 
$userId  = (int)$_SESSION['userId'];
$message = '';
$error   = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
 
    if ($name === '' || $phone === '') {
        $error = 'Name and phone number cannot be empty.';
    } elseif (updateUserProfile($userId, $name, $phone)) {
        $_SESSION['name'] = $name;
        $message = 'Profile updated successfully.';
    } else {
        $error = 'Failed to update profile.';
    }
}
 
$user = getUserById($userId);
 
$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">My Profile</h1>
<p class="page-subtitle">
    Signed in as <?= htmlspecialchars($_SESSION['name']) ?>
    (<?= htmlspecialchars($_SESSION['role']) ?>)
</p>
 
<?php if ($message !== ''): ?>
    <div class="message-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
 
<?php if ($error !== ''): ?>
    <div class="message-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
 
<form method="post" action="profile.php">
    <div class="form-group">
        <label class="form-label" for="email">Email (Account Identifier)</label>
        <input type="email" id="email" class="form-input"
               value="<?= htmlspecialchars($user['email']) ?>" disabled>
        <span class="form-hint">Your email cannot be changed.</span>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <input type="text" id="name" name="name" class="form-input"
               value="<?= htmlspecialchars($user['name']) ?>" required>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="phone_number">Phone Number</label>
        <input type="text" id="phone_number" name="phone_number" class="form-input"
               value="<?= htmlspecialchars($user['phone_number']) ?>" required>
    </div>
 
    <?php if (isset($user['loyalty_points'])): ?>
        <div class="form-group">
            <label class="form-label" for="loyalty">Loyalty Points</label>
            <input type="text" id="loyalty" class="form-input"
                   value="<?= htmlspecialchars($user['loyalty_points']) ?> pts" disabled>
        </div>
    <?php endif; ?>
 
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a class="link-back" href="product-list.php">Back to Catalogue</a>
    </div>
</form>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 