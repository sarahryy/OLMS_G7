<?php
/**
 * File: manage-orders.php
 * Module: Order Module (Administration)
 * Description: Administrator screen listing every customer order with its
 *              items, payment record and shipping record, and the controls
 *              to advance the order status and update shipping details.
 * Module author: Nad
 * Layout migrated to the shared header/footer on 27/10/2026.
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/order-service.php';
require_once __DIR__ . '/../config/db-config.php';
 
requireAdmin();
 
$message = '';
$error   = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action  = $_POST['action'] ?? '';
 
    if ($orderId <= 0) {
        $error = 'Invalid order ID.';
    } elseif ($action === 'update_order') {
        $result = updateOrderStatus($orderId, $_POST['order_status'] ?? '');
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'update_shipping') {
        $result = updateShippingStatus(
            $orderId,
            $_POST['shipping_status'] ?? '',
            trim($_POST['tracking_number'] ?? '')
        );
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Invalid action.';
    }
}
 
$orders = getAllOrdersForAdmin();
$conn   = getConnection();
 
/**
 * Converts an order, payment or shipping status into a badge CSS class.
 *
 * @param string $status Status value stored in the database
 * @return string CSS class name for the badge
 */
function adminStatusBadge($status)
{
    return 'badge badge-' . strtolower(trim((string)$status));
}
 
$pageTitle = 'Manage Orders';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">Manage Orders</h1>
<p class="page-subtitle">
    <?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?>
    placed across all customers
</p>
 
<?php if ($message !== ''): ?>
    <div class="message-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
 
<?php if ($error !== ''): ?>
    <div class="message-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
 
<?php if (empty($orders)): ?>
 
    <div class="no-results">No orders have been placed yet.</div>
 
<?php else: ?>
 
    <?php foreach ($orders as $order): ?>
        <?php
        $orderId = (int)$order['order_id'];
 
        $stmt = $conn->prepare(
            "SELECT product_name, quantity, unit_price, subtotal
             FROM order_items
             WHERE order_id = ?"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        ?>
 
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Order #<?= $orderId ?></h2>
                <span class="<?= adminStatusBadge($order['status']) ?>">
                    <?= htmlspecialchars($order['status']) ?>
                </span>
            </div>
 
            <dl class="detail-grid">
                <dt>Customer</dt>
                <dd>
                    <?= htmlspecialchars($order['customer_name']) ?>
                    (User #<?= (int)$order['user_id'] ?>)
                </dd>
 
                <dt>Date placed</dt>
                <dd><?= htmlspecialchars($order['created_at']) ?></dd>
 
                <dt>Order total</dt>
                <dd>RM <?= number_format($order['total_amount'], 2) ?></dd>
 
                <dt>Shipping address</dt>
                <dd><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></dd>
 
                <dt>Payment method</dt>
                <dd><?= htmlspecialchars($order['payment_method'] ?? 'Not recorded') ?></dd>
 
                <dt>Payment status</dt>
                <dd>
                    <?php if (!empty($order['payment_status'])): ?>
                        <span class="<?= adminStatusBadge($order['payment_status']) ?>">
                            <?= htmlspecialchars($order['payment_status']) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">Not recorded</span>
                    <?php endif; ?>
                </dd>
 
                <dt>Shipping status</dt>
                <dd>
                    <?php if (!empty($order['shipping_status'])): ?>
                        <span class="<?= adminStatusBadge($order['shipping_status']) ?>">
                            <?= htmlspecialchars($order['shipping_status']) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">Not recorded</span>
                    <?php endif; ?>
                </dd>
 
                <dt>Tracking number</dt>
                <dd><?= htmlspecialchars($order['tracking_number'] ?? 'Not available') ?></dd>
            </dl>
 
            <h3 class="section-title">Order Items</h3>
 
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
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right">Order Total</td>
                        <td class="text-right">RM <?= number_format($order['total_amount'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>
 
            <h3 class="section-title">Update Order Status</h3>
 
            <?php if (!in_array($order['status'], ['Completed', 'Cancelled'], true)): ?>
                <form method="post" action="manage-orders.php">
                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                    <input type="hidden" name="action" value="update_order">
 
                    <div class="form-group">
                        <label class="form-label" for="order_status_<?= $orderId ?>">Next status</label>
                        <select id="order_status_<?= $orderId ?>" name="order_status"
                                class="form-select" required>
                            <?php if ($order['status'] === 'Pending'): ?>
                                <option value="Preparing">Preparing</option>
                            <?php elseif ($order['status'] === 'Preparing'): ?>
                                <option value="Shipped">Shipped</option>
                            <?php elseif ($order['status'] === 'Shipped'): ?>
                                <option value="Completed">Completed</option>
                            <?php endif; ?>
                        </select>
                    </div>
 
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Order Status</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="text-muted">
                    This order is <?= htmlspecialchars($order['status']) ?> and can no longer change.
                </p>
            <?php endif; ?>
 
            <h3 class="section-title">Update Shipping</h3>
 
            <form method="post" action="manage-orders.php">
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <input type="hidden" name="action" value="update_shipping">
 
                <div class="form-group">
                    <label class="form-label" for="shipping_status_<?= $orderId ?>">Shipping Status</label>
                    <select id="shipping_status_<?= $orderId ?>" name="shipping_status"
                            class="form-select" required>
                        <?php
                        $shippingOptions = ['Pending', 'Preparing', 'Shipped', 'Completed', 'Cancelled'];
                        foreach ($shippingOptions as $option):
                        ?>
                            <option value="<?= $option ?>"
                                <?= (($order['shipping_status'] ?? '') === $option) ? 'selected' : '' ?>>
                                <?= $option ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
 
                <div class="form-group">
                    <label class="form-label" for="tracking_number_<?= $orderId ?>">Tracking Number</label>
                    <input type="text" id="tracking_number_<?= $orderId ?>" name="tracking_number"
                           class="form-input" placeholder="Enter tracking number"
                           value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>">
                </div>
 
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Shipping</button>
                </div>
            </form>
        </div>
 
    <?php endforeach; ?>
 
<?php endif; ?>
 
<?php
$conn->close();
include __DIR__ . '/../includes/footer.php';
?>
 