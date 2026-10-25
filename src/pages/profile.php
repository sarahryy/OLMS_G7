<?php
session_start();
require_once __DIR__ . '/../services/user-service.php';

if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['userId'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');

    if (empty($name) || empty($phone)) {
        $error = "Name and phone number cannot be empty.";
    } else {
        if (updateUserProfile($userId, $name, $phone)) {
            $_SESSION['name'] = $name;
            $message = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile.";
        }
    }
}

$user = getUserById($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - StationeryMart</title>
    <style>
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 25px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); font-family: sans-serif; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px 18px; background: #2b6cb0; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .alert-success { background: #c6f6d5; color: #22543d; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-danger { background: #fed7d7; color: #9b2c2c; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Customer Profile</h2>
    <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> (<?= htmlspecialchars($_SESSION['role']) ?>) | <a href="logout.php">Logout</a></p>
    <?php if ($message): ?><div class="alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" action="">
        <div class="form-group"><label>Email (Account Identifier)</label><input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
        <div class="form-group"><label>Full Name</label><input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required></div>
        <div class="form-group"><label>Phone Number</label><input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number']) ?>" required></div>
        <?php if (isset($user['loyalty_points'])): ?>
            <div class="form-group"><label>Loyalty Points</label><input type="text" value="<?= htmlspecialchars($user['loyalty_points']) ?> pts" disabled></div>
        <?php endif; ?>
        <button type="submit">Save Changes</button>
    </form>
</div>
</body>
</html>