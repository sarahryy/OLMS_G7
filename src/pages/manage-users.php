<?php
session_start();
require_once __DIR__ . '/../services/user-service.php';

// RBAC Guard: Admin-only access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("<div style='font-family:sans-serif; text-align:center; margin-top:50px;'><h2>Access Denied</h2><p>Administrator privileges are required to view this module.</p><a href='login.php'>Return to Login</a></div>");
}

$message = '';
if (isset($_GET['action']) && isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    if ($_GET['action'] === 'delete' && $targetId !== (int)$_SESSION['userId']) {
        if (deleteUser($targetId)) $message = "User ID #{$targetId} was successfully deleted.";
    } elseif ($_GET['action'] === 'toggle_role') {
        $newRole = ($_GET['current_role'] === 'admin') ? 'customer' : 'admin';
        if (updateUserRole($targetId, $newRole)) $message = "User ID #{$targetId} role updated to {$newRole}.";
    }
}

$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin</title>
    <style>
        body { font-family: sans-serif; background: #f7fafc; margin: 30px; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #e2e8f0; text-align: left; }
        th { background: #2d3748; color: #fff; }
        .btn { padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 13px; color: #fff; }
        .btn-role { background: #3182ce; }
        .btn-del { background: #e53e3e; }
        .alert-success { background: #c6f6d5; color: #22543d; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h2>User Administration</h2>
    <p>Admin Session: <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> | <a href="logout.php">Logout</a></p>
    <?php if ($message): ?><div class="alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['user_id']) ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone_number']) ?></td>
                    <td><strong><?= htmlspecialchars($u['role']) ?></strong></td>
                    <td>
                        <a class="btn btn-role" href="?action=toggle_role&id=<?= $u['user_id'] ?>&current_role=<?= $u['role'] ?>">Toggle Role</a>
                        <?php if ($u['user_id'] !== (int)$_SESSION['userId']): ?>
                            <a class="btn btn-del" href="?action=delete&id=<?= $u['user_id'] ?>" onclick="return confirm('Confirm deletion?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>