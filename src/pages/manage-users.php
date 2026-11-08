<?php
/**
 * File: manage-users.php
 * Module: User & Authentication (Administration)
 * Description: Hardened administrator screen with defensive parameter validation,
 *              whitelist guard clauses, self-lockout prevention, and HTTP status handling.
 * Module author: Lu'lu'
 */

require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/user-service.php';

// 1. Guard Clause: Strict Role & Authentication Assertion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
    http_response_code(401); // 401 Unauthorized
    header('Location: login.php?error=unauthorized');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // 403 Forbidden
    die('<h1>403 Forbidden</h1><p>Access denied. Administrative role required.</p>');
}

$currentAdminId = (int)$_SESSION['userId'];
$message = '';
$error   = '';

// 2. Input Pathway: Process Query Parameter Actions Defensively
if (isset($_GET['action'])) {
    $rawAction = str_replace("\0", '', $_GET['action'] ?? '');
    $action    = trim($rawAction);
    $allowedActions = ['toggle_role', 'delete'];

    // Guard Clause: Whitelist action parameter
    if (!in_array($action, $allowedActions, true)) {
        http_response_code(400); // 400 Bad Request
        $error = 'Invalid administrative action requested.';
    } else {
        // Guard Clause: Validate ID (Must be a positive integer >= 1)
        $targetId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 2147483647]
        ]);

        if ($targetId === false || $targetId === null) {
            http_response_code(400); // 400 Bad Request
            $error = 'Invalid or non-numeric target User ID provided.';
        } 
        // Guard Clause: Delete self-action edge case
        elseif ($action === 'delete' && $targetId === $currentAdminId) {
            http_response_code(400);
            $error = 'Security restriction: You cannot delete your own active administrator account.';
        } 
        // Guard Clause: Demote self-action edge case (prevents admin lockout)
        elseif ($action === 'toggle_role' && $targetId === $currentAdminId) {
            http_response_code(400);
            $error = 'Security restriction: You cannot demote your own administrator privileges.';
        } 
        // Execution
        else {
            if ($action === 'delete') {
                if (deleteUser($targetId)) {
                    $message = "User account #{$targetId} was permanently deleted.";
                } else {
                    http_response_code(500); // 500 Internal Server Error
                    $error = "User account #{$targetId} could not be deleted or does not exist.";
                }
            } elseif ($action === 'toggle_role') {
                $rawCurrentRole = trim($_GET['current_role'] ?? '');
                $newRole = ($rawCurrentRole === 'admin') ? 'customer' : 'admin';

                if (updateUserRole($targetId, $newRole)) {
                    $message = "User #{$targetId} role successfully changed to " . htmlspecialchars($newRole, ENT_QUOTES, 'UTF-8') . ".";
                } else {
                    http_response_code(500);
                    $error = "Failed to update role for user #{$targetId}.";
                }
            }
        }
    }
}

// 3. Defensive Fetch: Retrieve all users and assert data type
$rawUsers = getAllUsers();
$users = is_array($rawUsers) ? $rawUsers : [];

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">User Administration</h1>
<p class="page-subtitle">
    <?= count($users) ?> registered account<?= count($users) === 1 ? '' : 's' ?>
</p>

<!-- Output Pathway: Strictly Escaped System Feedback -->
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

<?php if (empty($users)): ?>
    <div class="no-results" style="padding:20px; text-align:center; color:#64748b;">
        No registered accounts found in the database.
    </div>
<?php else: ?>
    <table class="data-table" style="width:100%; border-collapse:collapse; margin-top:10px;">
        <thead>
            <tr style="background:#f1f5f9; text-align:left;">
                <th style="padding:10px; border:1px solid #cbd5e1;">ID</th>
                <th style="padding:10px; border:1px solid #cbd5e1;">Name</th>
                <th style="padding:10px; border:1px solid #cbd5e1;">Email</th>
                <th style="padding:10px; border:1px solid #cbd5e1;">Phone</th>
                <th style="padding:10px; border:1px solid #cbd5e1;">Role</th>
                <th style="padding:10px; border:1px solid #cbd5e1;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <?php
                    $uid    = (int)($u['user_id'] ?? 0);
                    $name   = (string)($u['name'] ?? '');
                    $email  = (string)($u['email'] ?? '');
                    $phone  = (string)($u['phone_number'] ?? '');
                    $role   = ($u['role'] === 'admin') ? 'admin' : 'customer';
                    $isSelf = ($uid === $currentAdminId);
                ?>
                <tr>
                    <td style="padding:10px; border:1px solid #cbd5e1;"><?= $uid ?></td>
                    <td style="padding:10px; border:1px solid #cbd5e1;"><?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td style="padding:10px; border:1px solid #cbd5e1;"><?= htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td style="padding:10px; border:1px solid #cbd5e1;"><?= htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td style="padding:10px; border:1px solid #cbd5e1;">
                        <span class="badge <?= $role === 'admin' ? 'badge-shipped' : 'badge-completed' ?>">
                            <?= htmlspecialchars($role, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </span>
                    </td>
                    <td style="padding:10px; border:1px solid #cbd5e1;">
                        <?php if (!$isSelf): ?>
                            <a class="btn btn-secondary btn-small"
                               href="manage-users.php?action=toggle_role&id=<?= $uid ?>&current_role=<?= urlencode($role) ?>">
                                Change Role
                            </a>
                            <a class="btn btn-danger btn-small"
                               href="manage-users.php?action=delete&id=<?= $uid ?>"
                               onclick="return confirm('Permanently delete account #<?= $uid ?>?');">
                                Delete
                            </a>
                        <?php else: ?>
                            <span class="text-muted" style="color:#64748b; font-style:italic;">Your account (Protected)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>