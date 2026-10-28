
<?php

require_once __DIR__ . '/../config/db-config.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/cart-service.php';

/**
 * Create an order, its items, COD payment and shipping record.
 */
function createOrderFromCart($userId, $shippingAddress)
{
    $conn = getConnection();

    try {
        $cart = getCart();

        if (!$cart) {
            return ['success' => false, 'message' => 'Cart not found.'];
        }

        $items = $cart->getItems();
        $totalAmount = (float) $cart->getTotal();

        if (empty($items) || $totalAmount <= 0) {
            return ['success' => false, 'message' => 'Your cart is empty.'];
        }

        $shippingAddress = trim($shippingAddress);

        if ($shippingAddress === '') {
            return ['success' => false, 'message' => 'Shipping address is required.'];
        }

        $conn->begin_transaction();

        // 1. Create the order.
        $stmt = $conn->prepare(
            "INSERT INTO orders
                (user_id, shipping_address, total_amount, status)
             VALUES (?, ?, ?, 'Pending')"
        );
        $stmt->bind_param('isd', $userId, $shippingAddress, $totalAmount);
        $stmt->execute();

        $orderId = $conn->insert_id;
        $stmt->close();

        // 2. Save all order items.
        $itemStmt = $conn->prepare(
            "INSERT INTO order_items
                (order_id, product_id, product_name, quantity, unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        foreach ($items as $item) {
            $productId = (int) $item->getProductId();
            $productName = (string) $item->getProductName();
            $quantity = (int) $item->getQuantity();
            $unitPrice = (float) $item->getPrice();
            $subtotal = (float) $item->getSubtotal();

            $itemStmt->bind_param(
                'iisidd',
                $orderId,
                $productId,
                $productName,
                $quantity,
                $unitPrice,
                $subtotal
            );

            $itemStmt->execute();
        }

        $itemStmt->close();

        // 3. Save the COD payment.
        $paymentMethod = 'Cash on Delivery';
        $paymentStatus = 'Pending';

        $paymentStmt = $conn->prepare(
            "INSERT INTO payments
                (order_id, amount, payment_method, payment_status)
             VALUES (?, ?, ?, ?)"
        );
        $paymentStmt->bind_param(
            'idss',
            $orderId,
            $totalAmount,
            $paymentMethod,
            $paymentStatus
        );
        $paymentStmt->execute();
        $paymentStmt->close();

        // 4. Create the shipping record.
        $shippingStatus = 'Pending';
        $trackingNumber = null;

        $shippingStmt = $conn->prepare(
            "INSERT INTO shipping
                (order_id, shipping_address, shipping_status, tracking_number)
             VALUES (?, ?, ?, ?)"
        );
        $shippingStmt->bind_param(
            'isss',
            $orderId,
            $shippingAddress,
            $shippingStatus,
            $trackingNumber
        );
        $shippingStmt->execute();
        $shippingStmt->close();

        $conn->commit();

// Clear cart after successful checkout
$cart->clear();
saveCart($cart);

        return [
            'success' => true,
            'message' => 'Order placed successfully.',
            'orderId' => $orderId
        ];

    } catch (Throwable $e) {
        $conn->rollback();
        error_log('createOrderFromCart error: ' . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Unable to place order. Please try again.'
        ];
    }
}


/**
 * Get a customer's orders.
 */
function getOrdersByUserId($userId)
{
    $conn = getConnection();

    $stmt = $conn->prepare(
        "SELECT order_id, user_id, shipping_address,
                total_amount, status, created_at
         FROM orders
         WHERE user_id = ?
         ORDER BY created_at DESC"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = new Order(
            $row['order_id'],
            $row['user_id'],
            $row['shipping_address'],
            $row['total_amount'],
            $row['status'],
            $row['created_at']
        );
    }

    $stmt->close();

    return $orders;
}


/**
 * Allow a customer to cancel a Pending order.
 */
function cancelCustomerOrder($orderId, $userId)
{
    $conn = getConnection();

    $stmt = $conn->prepare(
        "UPDATE orders
         SET status = 'Cancelled'
         WHERE order_id = ? AND user_id = ? AND status = 'Pending'"
    );

    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();

    $success = $stmt->affected_rows > 0;
    $stmt->close();

    if ($success) {
        $shippingStmt = $conn->prepare(
            "UPDATE shipping
             SET shipping_status = 'Cancelled'
             WHERE order_id = ? AND shipping_status = 'Pending'"
        );
        $shippingStmt->bind_param('i', $orderId);
        $shippingStmt->execute();
        $shippingStmt->close();
    }

    return $success;
}


/**
 * Get orders and related payment/shipping information for the admin page.
 */
function getAllOrdersForAdmin()
{
    $conn = getConnection();

    $sql = "
        SELECT
            o.order_id,
            o.user_id,
            o.shipping_address,
            o.total_amount,
            o.status,
            o.created_at,
            u.name AS customer_name,

            (SELECT p.payment_method
             FROM payments p
             WHERE p.order_id = o.order_id
             ORDER BY p.payment_id DESC LIMIT 1) AS payment_method,

            (SELECT p.payment_status
             FROM payments p
             WHERE p.order_id = o.order_id
             ORDER BY p.payment_id DESC LIMIT 1) AS payment_status,

            (SELECT s.shipping_status
             FROM shipping s
             WHERE s.order_id = o.order_id
             ORDER BY s.shipping_id DESC LIMIT 1) AS shipping_status,

            (SELECT s.tracking_number
             FROM shipping s
             WHERE s.order_id = o.order_id
             ORDER BY s.shipping_id DESC LIMIT 1) AS tracking_number

        FROM orders o
        LEFT JOIN users u ON u.user_id = o.user_id
        ORDER BY o.created_at DESC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        error_log('getAllOrdersForAdmin error: ' . $conn->error);
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}


/**
 * Update the overall order status.
 */
function updateOrderStatus($orderId, $newStatus)
{
    $allowedStatuses = [
        'Pending', 'Preparing', 'Shipped',
        'Completed', 'Cancelled'
    ];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        return false;
    }

    $conn = getConnection();

    $stmt = $conn->prepare(
        "UPDATE orders SET status = ? WHERE order_id = ?"
    );
    $stmt->bind_param('si', $newStatus, $orderId);
    $success = $stmt->execute();

    $stmt->close();

    return $success;
}


/**
 * Update the shipping status and tracking number for an order.
 */
function updateShippingStatus($orderId, $shippingStatus, $trackingNumber = '')
{
    $allowedStatuses = [
        'Pending', 'Preparing', 'Shipped',
        'Completed', 'Cancelled'
    ];

    $trackingNumber = trim($trackingNumber);

    if (
        !in_array($shippingStatus, $allowedStatuses, true) ||
        strlen($trackingNumber) > 100
    ) {
        return false;
    }

    $conn = getConnection();

    $stmt = $conn->prepare(
        "SELECT shipping_id
         FROM shipping
         WHERE order_id = ?
         ORDER BY shipping_id DESC
         LIMIT 1"
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $shipping = $result->fetch_assoc();
    $stmt->close();

    if ($shipping) {
        $shippingId = (int) $shipping['shipping_id'];

        $update = $conn->prepare(
            "UPDATE shipping
             SET shipping_status = ?, tracking_number = ?
             WHERE shipping_id = ?"
        );
        $update->bind_param(
            'ssi',
            $shippingStatus,
            $trackingNumber,
            $shippingId
        );
        $success = $update->execute();
        $update->close();

        return $success;
    }

    // If a shipping record is missing, create it using the order address.
    $addressStmt = $conn->prepare(
        "SELECT shipping_address FROM orders WHERE order_id = ?"
    );
    $addressStmt->bind_param('i', $orderId);
    $addressStmt->execute();
    $addressResult = $addressStmt->get_result();
    $order = $addressResult->fetch_assoc();
    $addressStmt->close();

    if (!$order) {
        return false;
    }

    $address = $order['shipping_address'];

    $insert = $conn->prepare(
        "INSERT INTO shipping
            (order_id, shipping_address, shipping_status, tracking_number)
         VALUES (?, ?, ?, ?)"
    );
    $insert->bind_param(
        'isss',
        $orderId,
        $address,
        $shippingStatus,
        $trackingNumber
    );
    $success = $insert->execute();
    $insert->close();

    return $success;
}