<?php
/**
 * File: cart.php
 * Module: Cart Management
 * Description: Shows the items currently in the customer's cart, with
 *              quantity adjustment, item removal, clearing the cart and a
 *              running total. Leads on to the checkout page.
 * Module author: Savitta
 * Layout migrated to the shared header/footer on 27/10/2026.
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/cart-service.php';
 
requireCustomer();
 
$cart       = getCart();
$successMsg = '';
 
// Handle actions (update quantity, remove item, clear cart)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
 
    if ($action === 'update') {
        $productId = $_POST['product_id'] ?? null;
        $quantity  = (int)($_POST['quantity'] ?? 1);
        if ($productId !== null) {
            $cart->updateQuantity($productId, $quantity);
            saveCart($cart);
            $successMsg = 'Cart updated successfully.';
        }
    } elseif ($action === 'remove') {
        $productId = $_POST['product_id'] ?? null;
        if ($productId !== null) {
            $cart->removeItem($productId);
            saveCart($cart);
            $successMsg = 'Item removed from cart.';
        }
    } elseif ($action === 'clear') {
        $cart->clear();
        saveCart($cart);
        $successMsg = 'Cart has been cleared.';
    }
}
 
$cartItems  = $cart->getItems();
$grandTotal = $cart->getTotal();
 
$pageTitle = 'Shopping Cart';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">Your Shopping Cart</h1>
<p class="page-subtitle">
    <?= count($cartItems) ?> item<?= count($cartItems) === 1 ? '' : 's' ?> in your cart
</p>
 
<?php if ($successMsg !== ''): ?>
    <div class="message-success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
 
<?php if (empty($cartItems)): ?>
 
    <div class="no-results">
        Your cart is currently empty.
    </div>
 
    <div class="form-actions">
        <a class="btn btn-primary" href="product-list.php">Browse Products</a>
    </div>
 
<?php else: ?>
 
    <table class="data-table">
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-right">Price (RM)</th>
                <th>Quantity</th>
                <th class="text-right">Subtotal (RM)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item->getProductName()) ?></td>
                    <td class="text-right"><?= number_format($item->getPrice(), 2) ?></td>
                    <td>
                        <form method="post" action="cart.php" class="inline-form">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id"
                                   value="<?= htmlspecialchars($item->getProductId()) ?>">
                            <input type="number" name="quantity" class="qty-input"
                                   value="<?= (int)$item->getQuantity() ?>" min="1">
                            <button type="submit" class="btn btn-secondary btn-small">Update</button>
                        </form>
                    </td>
                    <td class="text-right"><?= number_format($item->getSubtotal(), 2) ?></td>
                    <td>
                        <form method="post" action="cart.php" class="inline-form"
                              onsubmit="return confirm('Remove this item from your cart?');">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id"
                                   value="<?= htmlspecialchars($item->getProductId()) ?>">
                            <button type="submit" class="btn btn-danger btn-small">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Grand Total</td>
                <td class="text-right">RM <?= number_format($grandTotal, 2) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
 
    <div class="form-actions">
        <a class="btn btn-primary" href="checkout.php">Proceed to Checkout</a>
        <a class="btn btn-secondary" href="product-list.php">Continue Shopping</a>
        <form method="post" action="cart.php" class="inline-form"
              onsubmit="return confirm('Are you sure you want to clear your entire cart?');">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-danger">Clear Cart</button>
        </form>
    </div>
 
<?php endif; ?>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 