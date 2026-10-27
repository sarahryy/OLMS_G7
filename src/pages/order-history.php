
<?php
session_start();

require_once __DIR__ . '/../services/order-service.php';
require_once __DIR__ . '/../config/db-config.php';

if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: manage-orders.php');
    exit;
}

$userId = (int) $_SESSION['userId'];
$message = $_SESSION['orderSuccess'] ?? '';
unset($_SESSION['orderSuccess']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['cancel_order_id'])) {

    $orderId = (int) $_POST['cancel_order_id'];
    $result = cancelCustomerOrder($orderId, $userId);

    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

$orders = getOrdersByUserId($userId);
$conn = getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders - StationeryMart</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <style>
        .order-container { max-width: 1000px; margin: 30px auto; padding: 20px; }
        .order-card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 6px #ddd; }
        .order-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .order-table th, .order-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .btn { padding: 8px 12px; border: 0; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .cancel { background: #dc2626; color: white; }
        .success { background: #dcfce7; padding: 12px; margin-bottom: 15px; }
        .error { background: #fee2e2; padding: 12px; margin-bottom: 15px; }
        .details { margin-top: 12px; }
    </style>
</head>
<body>
<div class="order-container">
    <h1>My Order History</h1>

    <?php if ($message !== ''): ?>
        <p class="success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="order-card">
            <p>You have not placed any orders yet.</p>
            <a href="search-product.php">Browse products</a>
        </div>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <?php
        $orderId = (int) $order->getOrderId();

        $stmt = $conn->prepare(
            "SELECT oi.product_name, oi.quantity, oi.unit_price, oi.subtotal
             FROM order_items oi WHERE oi.order_id = ?"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT p.payment_method, p.payment_status,
                    s.shipping_status, s.tracking_number
             FROM orders o
             LEFT JOIN payments p ON o.order_id = p.order_id
             LEFT JOIN shipping s ON o.order_id = s.order_id
             WHERE o.order_id = ? AND o.user_id = ?"
        );
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $extra = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        ?>

        <div class="order-card">
            <h2>Order #<?= $orderId ?></h2>
            <p><strong>Date:</strong>
                <?= htmlspecialchars((string) $order->getCreatedAt()) ?></p>
            <p><strong>Order status:</strong>
                <?= htmlspecialchars($order->getStatus()) ?></p>
            <p><strong>Total:</strong>
                RM<?= number_format($order->getTotalAmount(), 2) ?></p>
            <p><strong>Shipping address:</strong><br>
                <?= nl2br(htmlspecialchars($order->getShippingAddress())) ?></p>

            <h3>Items</h3>
            <table class="order-table">
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td>RM<?= number_format($item['unit_price'], 2) ?></td>
                        <td>RM<?= number_format($item['subtotal'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="details">
                <p><strong>Payment method:</strong>
                    <?= htmlspecialchars($extra['payment_method'] ?? 'N/A') ?></p>
                <p><strong>Payment status:</strong>
                    <?= htmlspecialchars($extra['payment_status'] ?? 'N/A') ?></p>
                <p><strong>Shipping status:</strong>
                    <?= htmlspecialchars($extra['shipping_status'] ?? 'N/A') ?></p>
                <p><strong>Tracking number:</strong>
                    <?= htmlspecialchars($extra['tracking_number'] ?? 'Not available') ?></p>
            </div>

            <?php if ($order->getStatus() === 'Pending'): ?>
                <form method="POST" onsubmit="return confirm('Cancel this order?');">
                    <input type="hidden" name="cancel_order_id" value="<?= $orderId ?>">
                    <button class="btn cancel" type="submit">Cancel Order</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <p><a href="search-product.php">Continue Shopping</a></p>
</div>
</body>
</html>
<?php $conn->close(); ?>