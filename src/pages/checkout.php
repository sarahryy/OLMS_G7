<?php
/**
 * File: checkout.php
 * Module: Order Module
 * Description: Final step of the purchase flow. Shows an order summary built
 *              from the session cart, collects the delivery address, and
 *              converts the cart into a confirmed order.
 * Module author: Nad
 * Layout migrated to the shared header/footer on 27/10/2026.
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/order-service.php';
require_once __DIR__ . '/../services/cart-service.php';
 
requireCustomer();
 
$cart       = getCart();
$cartItems  = $cart->getItems();
$grandTotal = $cart->getTotal();
 
$errorMessage = '';
 
// Process the checkout when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
 
    $result = createOrderFromCart((int)$_SESSION['userId'], $shippingAddress);
 
    if ($result['success']) {
        $_SESSION['orderSuccess'] = 'Your order has been placed successfully.';
        header('Location: order-history.php');
        exit;
    }
 
    $errorMessage = $result['message'];
}
 
$pageTitle = 'Checkout';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">Checkout</h1>
<p class="page-subtitle">Review your order and confirm your delivery address</p>
 
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
 
    <p class="page-subtitle" style="margin-top:14px;">
        Payment method: <strong>Cash on Delivery (COD)</strong>
    </p>
 
    <h2 class="section-title">Shipping Information</h2>
 
    <form method="post" action="checkout.php">
        <div class="form-group">
            <label class="form-label" for="shipping_address">Delivery Address</label>
            <textarea id="shipping_address" name="shipping_address"
                      class="form-textarea" rows="5"
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
 
