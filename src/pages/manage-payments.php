<?php
session_start();

require_once __DIR__ . '/../config/db-config.php';

if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: order-history.php');
    exit;
}

$conn = getConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $status = $_POST['payment_status'] ?? '';

    $allowedStatuses = ['Pending', 'Paid', 'Failed', 'Refunded'];

    if ($paymentId > 0 && in_array($status, $allowedStatuses, true)) {
        $stmt = $conn->prepare(
            "UPDATE payments SET payment_status = ? WHERE payment_id = ?"
        );
        $stmt->bind_param('si', $status, $paymentId);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $message = 'Payment status updated.';
        } else {
            $message = 'Unable to update payment status.';
        }

        $stmt->close();
    }
}

$sql = "SELECT p.payment_id, p.order_id, u.name AS customer_name,
               p.amount, p.payment_method, p.payment_status, p.created_at
        FROM payments p
        JOIN orders o ON p.order_id = o.order_id
        JOIN users u ON o.user_id = u.user_id
        ORDER BY p.payment_id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Payments - StationeryMart</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <style>
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        select, button { padding: 7px; }
    </style>
</head>
<body>
<div class="page-container">
    <h1>Manage Payments</h1>

    <p><a href="manage-orders.php">Back to Manage Orders</a></p>

    <?php if ($message !== ''): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Payment ID</th>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Update</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$row['payment_id'] ?></td>
                <td><?= (int)$row['order_id'] ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td>RM <?= number_format((float)$row['amount'], 2) ?></td>
                <td><?= htmlspecialchars($row['payment_method']) ?></td>
                <td><?= htmlspecialchars($row['payment_status']) ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="payment_id"
                               value="<?= (int)$row['payment_id'] ?>">
                        <select name="payment_status">
                            <?php foreach (['Pending', 'Paid', 'Failed', 'Refunded'] as $status): ?>
                                <option value="<?= $status ?>"
                                    <?= $row['payment_status'] === $status ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Save</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No payment records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>