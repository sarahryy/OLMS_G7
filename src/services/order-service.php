
<?php
/**
 * File: order-service.php
 * Module: Order Management
 * Description: Handles checkout, order history, cancellation and order status.
 * Author: Dini Nadhirah
 */

require_once __DIR__ . '/../config/db-config.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/OrderItem.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/CartItem.php';
require_once __DIR__ . '/cart-service.php';

/**
 * Creates an order from the current cart.
 * Returns ['success' => true, 'orderId' => ID]
 * or ['success' => false, 'message' => error].
 */
function createOrderFromCart($userId, $shippingAddress)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $shippingAddress = trim($shippingAddress);

    if ((int)$userId <= 0) {
        return ['success' => false, 'message' => 'Please log in first.'];
    }

    if ($shippingAddress === '') {
        return ['success' => false, 'message' => 'Shipping address is required.'];
    }

    $cart = getCart();
    $items = $cart->getItems();

    if (empty($items)) {
        return ['success' => false, 'message' => 'Your cart is empty.'];
    }

    $conn = getConnection();
    $conn->begin_transaction();

    try {
        $total = 0;
        $verifiedItems = [];

        // Recheck product prices and stock from the database.
        $productStmt = $conn->prepare(
            "SELECT product_id, product_name, price, stock_quantity
             FROM products
             WHERE product_id = ?
             FOR UPDATE"
        );

        foreach ($items as $item) {
            $productId = (int)$item->getProductId();
            $quantity = (int)$item->getQuantity();

            if ($productId <= 0 || $quantity <= 0) {
                throw new Exception('Invalid product or quantity in cart.');
            }

            $productStmt->bind_param('i', $productId);
            $productStmt->execute();
            $result = $productStmt->get_result();
            $product = $result->fetch_assoc();

            if (!$product) {
                throw new Exception('A product in your cart no longer exists.');
            }

            if ((int)$product['stock_quantity'] < $quantity) {
                throw new Exception(
                    'Not enough stock for ' . $product['product_name'] . '.'
                );
            }

            $unitPrice = (float)$product['price'];
            $subtotal = $unitPrice * $quantity;
            $total += $subtotal;

            $verifiedItems[] = [
                'productId' => (int)$product['product_id'],
                'productName' => $product['product_name'],
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'subtotal' => $subtotal
            ];
        }

        $productStmt->close();

        // Create the order.
        $stmt = $conn->prepare(
            "INSERT INTO orders (user_id, shipping_address, total_amount, status)
             VALUES (?, ?, ?, 'Pending')"
        );
        $stmt->bind_param('isd', $userId, $shippingAddress, $total);
        $stmt->execute();

        $orderId = $conn->insert_id;
        $stmt->close();

        // Save each order item and deduct stock.
        $itemStmt = $conn->prepare(
            "INSERT INTO order_items
             (order_id, product_id, product_name, quantity, unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stockStmt = $conn->prepare(
            "UPDATE products
             SET stock_quantity = stock_quantity - ?
             WHERE product_id = ? AND stock_quantity >= ?"
        );

        foreach ($verifiedItems as $item) {
            $productId = $item['productId'];
            $productName = $item['productName'];
            $quantity = $item['quantity'];
            $unitPrice = $item['unitPrice'];
            $subtotal = $item['subtotal'];

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

            $stockStmt->bind_param('iii', $quantity, $productId, $quantity);
            $stockStmt->execute();

            if ($stockStmt->affected_rows !== 1) {
                throw new Exception('Stock changed during checkout. Please try again.');
            }
        }

        $itemStmt->close();
        $stockStmt->close();

        // Create payment record automatically.
        $paymentMethod = 'Cash on Delivery';
        $paymentStmt = $conn->prepare(
            "INSERT INTO payments (order_id, amount, payment_method, payment_status)
             VALUES (?, ?, ?, 'Pending')"
        );
        $paymentStmt->bind_param('ids', $orderId, $total, $paymentMethod);
        $paymentStmt->execute();
        $paymentStmt->close();

        // Create shipping record automatically.
        $shippingStmt = $conn->prepare(
            "INSERT INTO shipping (order_id, shipping_address, shipping_status)
             VALUES (?, ?, 'Pending')"
        );
        $shippingStmt->bind_param('is', $orderId, $shippingAddress);
        $shippingStmt->execute();
        $shippingStmt->close();

        $conn->commit();

        // Empty the cart only after the order succeeds.
        $cart->clear();
        saveCart($cart);

        return ['success' => true, 'orderId' => $orderId];

    } catch (Throwable $e) {
        $conn->rollback();

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    } finally {
        $conn->close();
    }
}

/**
 * Returns all orders belonging to one customer.
 */
function getOrdersByUserId($userId)
{
    $conn = getConnection();

    $stmt = $conn->prepare(
        "SELECT order_id, user_id, shipping_address, total_amount, status, created_at
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
    $conn->close();

    return $orders;
}

/**
 * Gets one order with its items.
 * Customers must provide their own user ID.
 * Admins can pass null as the user ID.
 */
function getOrderDetails($orderId, $userId = null)
{
    $conn = getConnection();

    if ($userId === null) {
        $stmt = $conn->prepare(
            "SELECT order_id, user_id, shipping_address, total_amount, status, created_at
             FROM orders WHERE order_id = ?"
        );
        $stmt->bind_param('i', $orderId);
    } else {
        $stmt = $conn->prepare(
            "SELECT order_id, user_id, shipping_address, total_amount, status, created_at
             FROM orders WHERE order_id = ? AND user_id = ?"
        );
        $stmt->bind_param('ii', $orderId, $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $conn->close();
        return null;
    }

    $order = new Order(
        $row['order_id'],
        $row['user_id'],
        $row['shipping_address'],
        $row['total_amount'],
        $row['status'],
        $row['created_at']
    );

    $itemStmt = $conn->prepare(
        "SELECT order_item_id, order_id, product_id, product_name,
                quantity, unit_price, subtotal
         FROM order_items
         WHERE order_id = ?
         ORDER BY order_item_id"
    );
    $itemStmt->bind_param('i', $orderId);
    $itemStmt->execute();

    $itemResult = $itemStmt->get_result();
    $orderItems = [];

    while ($itemRow = $itemResult->fetch_assoc()) {
        $orderItems[] = new OrderItem(
            $itemRow['order_item_id'],
            $itemRow['order_id'],
            $itemRow['product_id'],
            $itemRow['product_name'],
            $itemRow['quantity'],
            $itemRow['unit_price'],
            $itemRow['subtotal']
        );
    }

    $itemStmt->close();
    $conn->close();

    $order->setItems($orderItems);

    return $order;
}

/**
 * Cancels a customer's own order while it is Pending.
 * Restores stock and marks shipping as Cancelled.
 */
function cancelCustomerOrder($orderId, $userId)
{
    $conn = getConnection();
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "SELECT status FROM orders
             WHERE order_id = ? AND user_id = ?
             FOR UPDATE"
        );
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            throw new Exception('Order not found.');
        }

        if ($order['status'] !== 'Pending') {
            throw new Exception('Only Pending orders can be cancelled.');
        }

        $itemStmt = $conn->prepare(
            "SELECT product_id, quantity
             FROM order_items
             WHERE order_id = ? AND product_id IS NOT NULL"
        );
        $itemStmt->bind_param('i', $orderId);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $itemStmt->close();

        $stockStmt = $conn->prepare(
            "UPDATE products SET stock_quantity = stock_quantity + ?
             WHERE product_id = ?"
        );

        foreach ($items as $item) {
            $quantity = (int)$item['quantity'];
            $productId = (int)$item['product_id'];
            $stockStmt->bind_param('ii', $quantity, $productId);
            $stockStmt->execute();
        }
        $stockStmt->close();

        $updateStmt = $conn->prepare(
            "UPDATE orders SET status = 'Cancelled'
             WHERE order_id = ? AND user_id = ? AND status = 'Pending'"
        );
        $updateStmt->bind_param('ii', $orderId, $userId);
        $updateStmt->execute();

        if ($updateStmt->affected_rows !== 1) {
            throw new Exception('Unable to cancel order.');
        }
        $updateStmt->close();

        $shippingStmt = $conn->prepare(
            "UPDATE shipping SET shipping_status = 'Cancelled'
             WHERE order_id = ?"
        );
        $shippingStmt->bind_param('i', $orderId);
        $shippingStmt->execute();
        $shippingStmt->close();

        $paymentStmt = $conn->prepare(
            "UPDATE payments SET payment_status = 'Refunded'
             WHERE order_id = ? AND payment_status = 'Paid'"
        );
        $paymentStmt->bind_param('i', $orderId);
        $paymentStmt->execute();
        $paymentStmt->close();

        $conn->commit();
        return ['success' => true, 'message' => 'Order cancelled successfully.'];

    } catch (Throwable $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    } finally {
        $conn->close();
    }
}

/**
 * Lists all orders for the admin page.
 */
function getAllOrdersForAdmin()
{
    $conn = getConnection();

    $sql = "SELECT o.order_id, o.user_id, u.name AS customer_name,
                   o.shipping_address, o.total_amount, o.status, o.created_at
            FROM orders o
            INNER JOIN users u ON o.user_id = u.user_id
            ORDER BY o.created_at DESC";

    $result = $conn->query($sql);
    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    $conn->close();
    return $orders;
}

/**
 * Updates order status and corresponding shipping status.
 * Allowed transitions:
 * Pending -> Preparing -> Shipped -> Completed.
 */
function updateOrderStatus($orderId, $newStatus)
{
    $allowedTransitions = [
        'Pending' => ['Preparing'],
        'Preparing' => ['Shipped'],
        'Shipped' => ['Completed'],
        'Completed' => [],
        'Cancelled' => []
    ];

    if (!in_array($newStatus, ['Pending', 'Preparing', 'Shipped', 'Completed'], true)) {
        return ['success' => false, 'message' => 'Invalid order status.'];
    }

    $conn = getConnection();
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "SELECT status FROM orders WHERE order_id = ? FOR UPDATE"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            throw new Exception('Order not found.');
        }

        $currentStatus = $row['status'];

        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
            throw new Exception('This status transition is not allowed.');
        }

        $updateStmt = $conn->prepare(
            "UPDATE orders SET status = ? WHERE order_id = ?"
        );
        $updateStmt->bind_param('si', $newStatus, $orderId);
        $updateStmt->execute();
        $updateStmt->close();

        $shippingStmt = $conn->prepare(
            "UPDATE shipping SET shipping_status = ?
             WHERE order_id = ?"
        );
        $shippingStmt->bind_param('si', $newStatus, $orderId);
        $shippingStmt->execute();
        $shippingStmt->close();

        $conn->commit();
        return ['success' => true, 'message' => 'Order status updated successfully.'];

    } catch (Throwable $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    } finally {
        $conn->close();
    }
}

/**
 * Updates shipping status independently for the admin.
 */
function updateShippingStatus($orderId, $shippingStatus, $trackingNumber = null)
{
    $allowed = ['Pending', 'Preparing', 'Shipped', 'Completed', 'Cancelled'];

    if (!in_array($shippingStatus, $allowed, true)) {
        return ['success' => false, 'message' => 'Invalid shipping status.'];
    }

    $conn = getConnection();

    $stmt = $conn->prepare(
        "UPDATE shipping
         SET shipping_status = ?, tracking_number = ?
         WHERE order_id = ?"
    );
    $stmt->bind_param('ssi', $shippingStatus, $trackingNumber, $orderId);
    $success = $stmt->execute();

    $stmt->close();
    $conn->close();

    return [
        'success' => $success,
        'message' => $success
            ? 'Shipping status updated successfully.'
            : 'Failed to update shipping status.'
    ];
}