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

$allowedStatuses = [
    'Pending', 'Preparing', 'Shipped', 'Completed', 'Cancelled'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingId = (int)($_POST['shipping_id'] ?? 0);
    $status = $_POST['shipping_status'] ?? '';
    $tracking = trim($_POST['tracking_number'] ?? '');

    if (
        $shippingId > 0 &&
        in_array($status, $allowedStatuses, true) &&
        strlen($tracking) <= 100
    ) {
        $stmt = $conn->prepare(
            "UPDATE shipping
             SET shipping_status = ?, tracking_number = ?
             WHERE shipping_id = ?"
        );
        $stmt->bind_param('ssi', $status, $tracking, $shippingId);

        if ($stmt->execute()) {
            $message = 'Shipping details updated.';
        } else {
            $message = 'Unable to update shipping details.';
        }

        $stmt->close();
    } else {
        $message = 'Please check the status and tracking number.';
    }
}

$sql = "SELECT s.shipping_id, s.order_id, u.name AS customer_name,
               s.shipping_address, s.shipping_status, s.tracking_number
        FROM shipping s
        JOIN orders o ON s.order_id = o.order_id
        JOIN users u ON o.user_id = u.user_id
        ORDER BY s.shipping_id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Shipping - StationeryMart</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <style>
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        input, select, button { padding: 7px; }
        input[name="tracking_number"] { width: 130px; }
    </style>
</head>
<body>
<div class="page-container">
    <h1>Manage Shipping</h1>

    <p><a href="manage-orders.php">Back to Manage Orders</a></p>

    <?php if ($message !== ''): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Shipping ID</th>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Address</th>
            <th>Status</th>
            <th>Tracking Number</th>
            <th>Update</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$row['shipping_id'] ?></td>
                <td><?= (int)$row['order_id'] ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['shipping_address'])) ?></td>
                <td><?= htmlspecialchars($row['shipping_status']) ?></td>
                <td><?= htmlspecialchars($row['tracking_number'] ?? '') ?: 'Not available' ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="shipping_id"
                               value="<?= (int)$row['shipping_id'] ?>">

                        <select name="shipping_status">
                            <?php foreach ($allowedStatuses as $status): ?>
                                <option value="<?= $status ?>"
                                    <?= $row['shipping_status'] === $status ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="text" name="tracking_number"
                               maxlength="100"
                               value="<?= htmlspecialchars($row['tracking_number'] ?? '') ?>"
                               placeholder="Tracking number">

                        <button type="submit">Save</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No shipping records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>