<?php
/**
 * File: checkout.php
 * Module: Order Module
 * Description: Final step of the purchase flow. Shows an order summary built
 *              from the session cart, collects the delivery address and the
 *              payment method, and converts the cart into a confirmed order.
 *
 *              Two payment methods are offered. Cash on Delivery leaves the
 *              payment Pending and sends the customer straight to their order
 *              history. SiberQR sends them to payment-qr.php, where the
 *              payment gateway middleware issues a QR code and waits for the
 *              bank to confirm the transfer.
 *
 * Module author: Nad
 * QR payment option added for Lab 7 & 8 by Siti Sarah Raihanah, 11/11/2026.
 * Input audit & defensive hardening for Lab 9 by Sharifah Sofiyyah Lu'lu', 19/11/2026.
 */

require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/order-service.php';
require_once __DIR__ . '/../services/cart-service.php';

requireCustomer();

$cart       = getCart();
$cartItems  = $cart->getItems();
$grandTotal = $cart->getTotal();

$errorMessage = '';

// Strict whitelist of supported payment methods
$allowedPaymentMethods = ['Cash on Delivery', 'SiberQR'];

$rawMethod      = $_POST['payment_method'] ?? 'SiberQR';
$selectedMethod = is_string($rawMethod) ? trim(str_replace("\0", '', $rawMethod)) : '';

// ---- Process the checkout when the form is submitted ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize address: strip null bytes and surrounding whitespace
    $rawAddress      = is_string($_POST['shipping_address'] ?? '') ? $_POST['shipping_address'] : '';
    $shippingAddress = trim(str_replace("\0", '', $rawAddress));

    // 1. Guard Clause: Reject empty cart submissions before any DB write
    if (empty($cartItems) || $grandTotal <= 0) {
        http_response_code(422);
        $errorMessage = 'Your cart is empty. Please add products before checking out.';
    }
    // 2. Guard Clause: Strict whitelist check for payment_method
    elseif (!in_array($selectedMethod, $allowedPaymentMethods, true)) {
        http_response_code(422);
        $errorMessage = 'Invalid payment method selected. Please choose Cash on Delivery or SiberQR.';
    }
    // 3. Guard Clause: Reject blank delivery address after trimming
    elseif ($shippingAddress === '') {
        http_response_code(422);
        $errorMessage = 'Delivery address cannot be blank.';
    }
    // 4. Guard Clause: Minimum delivery address length
    elseif (mb_strlen($shippingAddress, 'UTF-8') < 5) {
        http_response_code(422);
        $errorMessage = 'Please enter a complete delivery address (at least 5 characters).';
    }
    // 5. Guard Clause: Maximum delivery address length
    elseif (mb_strlen($shippingAddress, 'UTF-8') > 500) {
        http_response_code(422);
        $errorMessage = 'The delivery address is too long. Please shorten it.';
    } else {

        $result = createOrderFromCart(
            (int)$_SESSION['userId'],
            $shippingAddress,
            $selectedMethod
        );

        if (!empty($result['success'])) {
            $newOrderId = (int)($result['orderId'] ?? 0);

            if ($selectedMethod === 'SiberQR') {
                if ($newOrderId <= 0) {
                    http_response_code(500);
                    $errorMessage = 'Order created, but failed to initialize QR payment reference.';
                } else {
                    // Hand over to the payment gateway. The order exists and is
                    // Pending; it stays that way until the bank confirms.
                    header('Location: payment-qr.php?order_id=' . $newOrderId);
                    exit;
                }
            } else {
                $_SESSION['orderSuccess'] =
                    'Your order has been placed successfully. Please pay on delivery.';
                header('Location: order-history.php');
                exit;
            }
        } else {
            http_response_code(400);
            $errorMessage = $result['message'] ?? 'Unable to place order.';
        }
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Checkout</h1>
<p class="page-subtitle">Review your order, choose how to pay, and confirm your address</p>

<?php if ($errorMessage !== ''): ?>
    <div class="message-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (empty($cartItems)): ?>

    <div class="no-results">
        Your cart is empty. Please add products before checking out.
    </div>

    <div class="form-actions">
        <a class="btn btn-primary" href="product-list.php">Browse Products</a>
    </div>

<?php else: ?>

    <h2 class="section-title">Order Summary</h2>

    <table class="data-table">
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-right">Unit Price (RM)</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Subtotal (RM)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item->getProductName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td class="text-right"><?= number_format($item->getPrice(), 2) ?></td>
                    <td class="text-right"><?= (int)$item->getQuantity() ?></td>
                    <td class="text-right"><?= number_format($item->getSubtotal(), 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total Payable</td>
                <td class="text-right">RM <?= number_format($grandTotal, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- novalidate ensures server-side PHP guard clauses are testable directly -->
    <form method="post" action="checkout.php" novalidate>

        <h2 class="section-title">Payment Method</h2>

        <div class="form-group">
            <label class="payment-option">
                <input type="radio" name="payment_method" value="SiberQR"
                    <?= ($selectedMethod === 'SiberQR' || !in_array($selectedMethod, $allowedPaymentMethods, true)) ? 'checked' : '' ?>>
                <span>
                    <strong>QR Payment by Bank Siber (SiberQR)</strong><br>
                    <span class="form-hint">
                        Scan a QR code with your banking app. Your order moves
                        straight into preparation once the bank confirms the transfer.
                    </span>
                </span>
            </label>

            <label class="payment-option">
                <input type="radio" name="payment_method" value="Cash on Delivery"
                    <?= $selectedMethod === 'Cash on Delivery' ? 'checked' : '' ?>>
                <span>
                    <strong>Cash on Delivery (COD)</strong><br>
                    <span class="form-hint">
                        Pay the courier when your order arrives.
                    </span>
                </span>
            </label>
        </div>

        <h2 class="section-title">Shipping Information</h2>

        <div class="form-group">
            <label class="form-label" for="shipping_address">Delivery Address</label>
            <textarea id="shipping_address" name="shipping_address"
                      class="form-textarea" rows="5" maxlength="500"
                      placeholder="Enter your full delivery address"
                      required><?= htmlspecialchars($_POST['shipping_address'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
            <span class="form-hint">Include unit number, street, postcode and state.</span>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Place Order</button>
            <a class="link-back" href="cart.php">Back to Cart</a>
        </div>
    </form>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>