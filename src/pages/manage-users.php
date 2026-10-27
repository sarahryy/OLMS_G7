<?php
/**
 * File: manage-users.php
 * Module: User & Authentication (Administration)
 * Description: Administrator screen listing every registered account, with
 *              the ability to switch a user between the customer and admin
 *              roles or remove an account. An administrator cannot delete
 *              their own account.
 * Module author: Lu'lu'
 * Layout migrated to the shared header/footer on 27/10/2026.
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/user-service.php';
 
requireAdmin();
 
$message = '';
$error   = '';
 
if (isset($_GET['action'], $_GET['id'])) {
    $targetId = (int)$_GET['id'];
 
    if ($_GET['action'] === 'delete') {
        if ($targetId === (int)$_SESSION['userId']) {
            $error = 'You cannot delete your own account.';
        } elseif (deleteUser($targetId)) {
            $message = "User #{$targetId} was deleted.";
        } else {
            $error = "User #{$targetId} could not be deleted.";
        }
    } elseif ($_GET['action'] === 'toggle_role') {
        $newRole = (($_GET['current_role'] ?? '') === 'admin') ? 'customer' : 'admin';
        if (updateUserRole($targetId, $newRole)) {
            $message = "User #{$targetId} is now a {$newRole}.";
        } else {
            $error = "Role for user #{$targetId} could not be changed.";
        }
    }
}
 
$users = getAllUsers();
 
$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">User Administration</h1>
<p class="page-subtitle">
    <?= count($users) ?> registered account<?= count($users) === 1 ? '' : 's' ?>
</p>
 
<?php if ($message !== ''): ?>
    <div class="message-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
 
<?php if ($error !== ''): ?>
    <div class="message-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
 
<?php if (empty($users)): ?>
 
    <div class="no-results">No registered accounts yet.</div>
 
<?php else: ?>
 
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['user_id']) ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone_number']) ?></td>
                    <td>
                        <span class="badge <?= $u['role'] === 'admin' ? 'badge-shipped' : 'badge-completed' ?>">
                            <?= htmlspecialchars($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <a class="btn btn-secondary btn-small"
                           href="manage-users.php?action=toggle_role&id=<?= (int)$u['user_id'] ?>&current_role=<?= htmlspecialchars($u['role']) ?>">
                            Change Role
                        </a>
                        <?php if ((int)$u['user_id'] !== (int)$_SESSION['userId']): ?>
                            <a class="btn btn-danger btn-small"
                               href="manage-users.php?action=delete&id=<?= (int)$u['user_id'] ?>"
                               onclick="return confirm('Delete this account permanently?');">
                                Delete
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Your account</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
 
<?php endif; ?>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 