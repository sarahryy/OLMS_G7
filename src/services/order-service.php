<?php
/**
 * File: order-history.php
 * Module: Order Module
 * Description: Lists every order the signed-in customer has placed, with the
 *              items purchased, payment and shipping status, and the option
 *              to cancel an order that is still pending.
 * Module author: Nad
 * Layout migrated to the shared header/footer on 27/10/2026.
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/order-service.php';
require_once __DIR__ . '/../config/db-config.php';
 
requireCustomer();
 
$userId  = (int)$_SESSION['userId'];
$message = $_SESSION['orderSuccess'] ?? '';
unset($_SESSION['orderSuccess']);
 
$error = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $orderId = (int)$_POST['cancel_order_id'];
    $result  = cancelCustomerOrder($orderId, $userId);
 
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}
 
$orders = getOrdersByUserId($userId);
$conn   = getConnection();
 
/**
 * Converts an order, payment or shipping status into a badge CSS class.
 *
 * @param string $status Status value stored in the database
 * @return string CSS class name for the badge
 */
function statusBadgeClass($status)
{
    return 'badge badge-' . strtolower(trim((string)$status));
}
 
$pageTitle = 'My Orders';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">My Order History</h1>
<p class="page-subtitle">
    <?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?> placed
</p>
 
<?php if ($message !== ''): ?>
    <div class="message-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
 
<?php if ($error !== ''): ?>
    <div class="message-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
 
<?php if (empty($orders)): ?>
 
    <div class="no-results">You have not placed any orders yet.</div>
 
    <div class="form-actions">
        <a class="btn btn-primary" href="product-list.php">Browse Products</a>
    </div>
 
<?php else: ?>
 
    <?php foreach ($orders as $order): ?>
        <?php
        $orderId = (int)$order->getOrderId();
 
        // Items belonging to this order
        $stmt = $conn->prepare(
            "SELECT oi.product_name, oi.quantity, oi.unit_price, oi.subtotal
             FROM order_items oi WHERE oi.order_id = ?"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
 
        // Payment and shipping records linked to this order
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
 
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Order #<?= $orderId ?></h2>
                <span class="<?= statusBadgeClass($order->getStatus()) ?>">
                    <?= htmlspecialchars($order->getStatus()) ?>
                </span>
            </div>
 
            <dl class="detail-grid">
                <dt>Date placed</dt>
                <dd><?= htmlspecialchars((string)$order->getCreatedAt()) ?></dd>
 
                <dt>Order total</dt>
                <dd>RM <?= number_format($order->getTotalAmount(), 2) ?></dd>
 
                <dt>Shipping address</dt>
                <dd><?= nl2br(htmlspecialchars($order->getShippingAddress())) ?></dd>
 
                <dt>Payment method</dt>
                <dd><?= htmlspecialchars($extra['payment_method'] ?? 'Not recorded') ?></dd>
 
                <dt>Payment status</dt>
                <dd>
                    <?php if (!empty($extra['payment_status'])): ?>
                        <span class="<?= statusBadgeClass($extra['payment_status']) ?>">
                            <?= htmlspecialchars($extra['payment_status']) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">Not recorded</span>
                    <?php endif; ?>
                </dd>
 
                <dt>Shipping status</dt>
                <dd>
                    <?php if (!empty($extra['shipping_status'])): ?>
                        <span class="<?= statusBadgeClass($extra['shipping_status']) ?>">
                            <?= htmlspecialchars($extra['shipping_status']) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">Not recorded</span>
                    <?php endif; ?>
                </dd>
 
                <dt>Tracking number</dt>
                <dd><?= htmlspecialchars($extra['tracking_number'] ?? 'Not available') ?></dd>
            </dl>
 
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Unit Price (RM)</th>
                        <th class="text-right">Subtotal (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td class="text-right"><?= (int)$item['quantity'] ?></td>
                            <td class="text-right"><?= number_format($item['unit_price'], 2) ?></td>
                            <td class="text-right"><?= number_format($item['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
 
            <?php if ($order->getStatus() === 'Pending'): ?>
                <div class="form-actions">
                    <form method="post" action="order-history.php" class="inline-form"
                          onsubmit="return confirm('Cancel this order?');">
                        <input type="hidden" name="cancel_order_id" value="<?= $orderId ?>">
                        <button type="submit" class="btn btn-danger btn-small">Cancel Order</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
 
    <?php endforeach; ?>
 
    <div class="form-actions">
        <a class="btn btn-secondary" href="product-list.php">Continue Shopping</a>
    </div>
 
<?php endif; ?>
 
<?php
$conn->close();
include __DIR__ . '/../includes/footer.php';
?>
 