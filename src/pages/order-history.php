
<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/order-service.php';
require_once __DIR__ . '/../config/db-config.php';

requireCustomer();

$userId = (int) $_SESSION['userId'];

$message = $_SESSION['orderSuccess'] ?? '';
unset($_SESSION['orderSuccess']);

$error = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['cancel_order_id'])
) {
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

$pageTitle = 'My Orders';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-container">
    <h1 class="page-title">My Order History</h1>

    <p class="page-subtitle">
        <?= count($orders) ?>
        order<?= count($orders) === 1 ? '' : 's' ?> placed
    </p>

    <?php if ($message !== ''): ?>
        <div class="message-success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="message-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="no-results">
            You have not placed any orders yet.
        </div>

        <div class="form-actions">
            <a class="btn btn-primary" href="search-product.php">
                Browse Products
            </a>
        </div>
    <?php else: ?>

        <?php foreach ($orders as $order): ?>
            <?php
            $orderId = (int) $order->getOrderId();

            $stmt = $conn->prepare(
                "SELECT product_name, quantity, unit_price, subtotal
                 FROM order_items
                 WHERE order_id = ?"
            );

            $stmt->bind_param('i', $orderId);
            $stmt->execute();

            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            
$stmt = $conn->prepare(
    "SELECT
        (SELECT p.payment_method
         FROM payments p
         WHERE p.order_id = o.order_id
         ORDER BY p.payment_id DESC
         LIMIT 1) AS payment_method,

        (SELECT p.payment_status
         FROM payments p
         WHERE p.order_id = o.order_id
         ORDER BY p.payment_id DESC
         LIMIT 1) AS payment_status,

        (SELECT s.shipping_status
         FROM shipping s
         WHERE s.order_id = o.order_id
         ORDER BY s.shipping_id DESC
         LIMIT 1) AS shipping_status,

        (SELECT s.tracking_number
         FROM shipping s
         WHERE s.order_id = o.order_id
         ORDER BY s.shipping_id DESC
         LIMIT 1) AS tracking_number

     FROM orders o
     WHERE o.order_id = ? AND o.user_id = ?"
);

$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();

$extra = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();
            ?>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        Order #<?= $orderId ?>
                    </h2>

                    <span class="badge badge-<?= strtolower(
                        htmlspecialchars($order->getStatus())
                    ) ?>">
                        <?= htmlspecialchars($order->getStatus()) ?>
                    </span>
                </div>

                <p>
                    <strong>Date placed:</strong>
                    <?= htmlspecialchars((string) $order->getCreatedAt()) ?>
                </p>

                <p>
                    <strong>Total:</strong>
                    RM <?= number_format(
                        (float) $order->getTotalAmount(),
                        2
                    ) ?>
                </p>

                <p>
                    <strong>Shipping address:</strong><br>
                    <?= nl2br(
                        htmlspecialchars($order->getShippingAddress())
                    ) ?>
                </p>

                <p>
                    <strong>Payment method:</strong>
                    <?= htmlspecialchars(
                        $extra['payment_method'] ?? 'Not recorded'
                    ) ?>
                </p>

                <p>
                    <strong>Payment status:</strong>
                    <?= htmlspecialchars(
                        $extra['payment_status'] ?? 'Not recorded'
                    ) ?>
                </p>

                <p>
                    <strong>Shipping status:</strong>
                    <?= htmlspecialchars(
                        $extra['shipping_status'] ?? 'Not recorded'
                    ) ?>
                </p>

                <p>
                    <strong>Tracking number:</strong>
                    <?= htmlspecialchars(
                        $extra['tracking_number'] ?? 'Not available'
                    ) ?>
                </p>

                <h3>Order Items</h3>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price (RM)</th>
                            <th>Subtotal (RM)</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </td>
                                <td>
                                    <?= (int) $item['quantity'] ?>
                                </td>
                                <td>
                                    <?= number_format(
                                        (float) $item['unit_price'],
                                        2
                                    ) ?>
                                </td>
                                <td>
                                    <?= number_format(
                                        (float) $item['subtotal'],
                                        2
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($order->getStatus() === 'Pending'): ?>
                    <div class="form-actions">
                        <form
                            method="post"
                            action="order-history.php"
                            onsubmit="return confirm('Cancel this order?');"
                        >
                            <input
                                type="hidden"
                                name="cancel_order_id"
                                value="<?= $orderId ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-danger btn-small"
                            >
                                Cancel Order
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <a class="btn btn-secondary" href="search-product.php">
                Continue Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include __DIR__ . '/../includes/footer.php';
?>