
<?php
session_start();

require_once __DIR__ . '/../services/order-service.php';
require_once __DIR__ . '/../config/db-config.php';

if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: order-history.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($orderId <= 0) {
        $error = 'Invalid order ID.';
    } elseif ($action === 'update_order') {
        $newStatus = $_POST['order_status'] ?? '';
        $result = updateOrderStatus($orderId, $newStatus);

        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'update_shipping') {
        $shippingStatus = $_POST['shipping_status'] ?? '';
        $trackingNumber = trim($_POST['tracking_number'] ?? '');

        $result = updateShippingStatus(
            $orderId,
            $shippingStatus,
            $trackingNumber
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
$conn = getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Orders - StationeryMart</title>
    <link rel="stylesheet" href="../css/main-style.css">

    <style>
        .admin-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .order-card {
            background: white;
            padding: 22px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 6px #ddd;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .order-table th,
        .order-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .form-row {
            margin: 15px 0;
        }

        .form-row label {
            display: block;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .form-row select,
        .form-row input {
            padding: 9px;
            max-width: 100%;
            width: 320px;
            box-sizing: border-box;
        }

        .btn {
            padding: 10px 15px;
            background: #2563eb;
            color: white;
            border: 0;
            border-radius: 5px;
            cursor: pointer;
        }

        .success {
            padding: 12px;
            background: #dcfce7;
            margin-bottom: 15px;
        }

        .error {
            padding: 12px;
            background: #fee2e2;
            margin-bottom: 15px;
        }

        .section-title {
            margin-top: 25px;
        }
    </style>
</head>

<body>
<div class="admin-container">
    <h1>Manage Orders</h1>
    <p>View customer orders, payments and shipping information.</p>

    <?php if ($message !== ''): ?>
        <p class="success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="order-card">
            <p>No orders have been placed yet.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <?php
        $orderId = (int) $order['order_id'];

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

        <div class="order-card">
            <h2>Order #<?= $orderId ?></h2>

            <p><strong>Customer:</strong>
                <?= htmlspecialchars($order['customer_name']) ?>
                (User ID: <?= (int) $order['user_id'] ?>)
            </p>

            <p><strong>Date:</strong>
                <?= htmlspecialchars($order['created_at']) ?>
            </p>

            <p><strong>Order status:</strong>
                <?= htmlspecialchars($order['status']) ?>
            </p>

            <p><strong>Shipping address:</strong><br>
                <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
            </p>

            <h3 class="section-title">Order Items</h3>

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

            <h3>Total: RM<?= number_format($order['total_amount'], 2) ?></h3>

            <h3 class="section-title">Payment Information</h3>

            <p><strong>Payment method:</strong>
                <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?>
            </p>

            <p><strong>Payment status:</strong>
                <?= htmlspecialchars($order['payment_status'] ?? 'N/A') ?>
            </p>

            <h3 class="section-title">Update Order Status</h3>

            <?php if (!in_array($order['status'], ['Completed', 'Cancelled'], true)): ?>
                <form method="POST">
                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                    <input type="hidden" name="action" value="update_order">

                    <div class="form-row">
                        <label for="order_status_<?= $orderId ?>">
                            Next status
                        </label>

                        <select
                            id="order_status_<?= $orderId ?>"
                            name="order_status"
                            required
                        >
                            <?php if ($order['status'] === 'Pending'): ?>
                                <option value="Preparing">Preparing</option>
                            <?php elseif ($order['status'] === 'Preparing'): ?>
                                <option value="Shipped">Shipped</option>
                            <?php elseif ($order['status'] === 'Shipped'): ?>
                                <option value="Completed">Completed</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <button class="btn" type="submit">
                        Update Order Status
                    </button>
                </form>
            <?php else: ?>
                <p>This order is <?= htmlspecialchars($order['status']) ?>.</p>
            <?php endif; ?>

            <h3 class="section-title">Shipping Information</h3>

            <p><strong>Current shipping status:</strong>
                <?= htmlspecialchars($order['shipping_status'] ?? 'N/A') ?>
            </p>

            <p><strong>Tracking number:</strong>
                <?= htmlspecialchars($order['tracking_number'] ?? 'Not available') ?>
            </p>

            <form method="POST">
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <input type="hidden" name="action" value="update_shipping">

                <div class="form-row">
                    <label for="shipping_status_<?= $orderId ?>">
                        Shipping Status
                    </label>

                    <select
                        id="shipping_status_<?= $orderId ?>"
                        name="shipping_status"
                        required
                    >
                        <?php
                        $shippingOptions = [
                            'Pending',
                            'Preparing',
                            'Shipped',
                            'Completed',
                            'Cancelled'
                        ];

                        foreach ($shippingOptions as $option):
                        ?>
                            <option
                                value="<?= $option ?>"
                                <?= ($order['shipping_status'] === $option) ? 'selected' : '' ?>
                            >
                                <?= $option ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="tracking_number_<?= $orderId ?>">
                        Tracking Number
                    </label>

                    <input
                        type="text"
                        id="tracking_number_<?= $orderId ?>"
                        name="tracking_number"
                        value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>"
                        placeholder="Enter tracking number"
                    >
                </div>

                <button class="btn" type="submit">
                    Update Shipping
                </button>
            </form>
        </div>
    <?php endforeach; ?>

    <p><a href="profile.php">Back to Profile</a></p>
</div>
</body>
</html>
<?php $conn->close(); ?>