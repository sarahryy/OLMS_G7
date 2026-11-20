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
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/order-service.php';
require_once __DIR__ . '/../services/cart-service.php';
 
requireCustomer();
 
$cart       = getCart();
$cartItems  = $cart->getItems();
$grandTotal = $cart->getTotal();
 
$errorMessage = '';
 
// Payment methods this checkout accepts. Keeping them in one array means
// the form and the validation cannot drift apart.
$paymentMethods = [
    'cod' => 'Cash on Delivery (COD)',
    'qr'  => 'QR Payment by Bank Siber (SiberQR)'
];
 
$selectedMethod = $_POST['payment_method'] ?? 'qr';
 
// ---- Process the checkout when the form is submitted ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
 
    // Validate the submitted method against the list above rather than
    // trusting it. A crafted request cannot introduce a payment method
    // the system does not support.
    if (!array_key_exists($selectedMethod, $paymentMethods)) {
        $errorMessage = 'Please choose a valid payment method.';
    } elseif ($shippingAddress === '') {
        $errorMessage = 'A delivery address is required.';
    } elseif (strlen($shippingAddress) > 500) {
        $errorMessage = 'The delivery address is too long. Please shorten it.';
    } else {
 
        $methodLabel = ($selectedMethod === 'qr')
            ? 'QR Payment (SiberQR)'
            : 'Cash on Delivery';
 
        $result = createOrderFromCart(
            (int)$_SESSION['userId'],
            $shippingAddress,
            $methodLabel
        );
 
        if ($result['success']) {
 
            if ($selectedMethod === 'qr') {
                // Hand over to the payment gateway. The order exists and is
                // Pending; it stays that way until the bank confirms.
                header('Location: payment-qr.php?order_id='
                     . (int)($result['orderId'] ?? 0));
                exit;
            }
 
            $_SESSION['orderSuccess'] =
                'Your order has been placed successfully. Please pay on delivery.';
            header('Location: order-history.php');
            exit;
        }
 
        $errorMessage = $result['message'];
    }
}
 
$pageTitle = 'Checkout';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">Checkout</h1>
<p class="page-subtitle">Review your order, choose how to pay, and confirm your address</p>
 
<?php if ($errorMessage !== ''): ?>
    <div class="message-error"><?= htmlspecialchars($errorMessage) ?></div>
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
                    <td><?= htmlspecialchars($item->getProductName()) ?></td>
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
 
    <form method="post" action="checkout.php">
 
        <h2 class="section-title">Payment Method</h2>
 
        <div class="form-group">
            <label class="payment-option">
                <input type="radio" name="payment_method" value="qr"
                    <?= $selectedMethod === 'qr' ? 'checked' : '' ?>>
                <span>
                    <strong>QR Payment by Bank Siber (SiberQR)</strong><br>
                    <span class="form-hint">
                        Scan a QR code with your banking app. Your order moves
                        straight into preparation once the bank confirms the transfer.
                    </span>
                </span>
            </label>
 
            <label class="payment-option">
                <input type="radio" name="payment_method" value="cod"
                    <?= $selectedMethod === 'cod' ? 'checked' : '' ?>>
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
                      required><?= htmlspecialchars($_POST['shipping_address'] ?? '') ?></textarea>
            <span class="form-hint">Include unit number, street, postcode and state.</span>
        </div>
 
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Place Order</button>
            <a class="link-back" href="cart.php">Back to Cart</a>
        </div>
    </form>
 
<?php endif; ?>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 