<?php
/**
 * File: cart.php
 * Module: Cart Management
 * Description: Frontend page showing cart items, quantity adjustments, item removal, and running totals.
 * Author: Savitta
 */

require_once __DIR__ . '/../services/cart-service.php';

$cart = getCart();
$successMsg = '';
$errorMsg = '';

// Handle actions (Update quantity, remove item, clear cart)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $productId = $_POST['product_id'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 1);
        if ($productId !== null) {
            $cart->updateQuantity($productId, $quantity);
            saveCart($cart);
            $successMsg = "Cart updated successfully.";
        }
    } elseif ($action === 'remove') {
        $productId = $_POST['product_id'] ?? null;
        if ($productId !== null) {
            $cart->removeItem($productId);
            saveCart($cart);
            $successMsg = "Item removed from cart.";
        }
    } elseif ($action === 'clear') {
        $cart->clear();
        saveCart($cart);
        $successMsg = "Cart has been cleared.";
    }
}

$cartItems = $cart->getItems();
$grandTotal = $cart->getTotal();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart — StationeryMart</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; line-height: 1.5; }
        .success { background: #dfd; padding: 10px; border: 1px solid #0a0; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f5f5f5; }
        .text-right { text-align: right; }
        button, .btn { padding: 6px 12px; cursor: pointer; border-radius: 4px; text-decoration: none; font-size: 14px; }
        .btn-update { background-color: #f0ad4e; color: #fff; border: none; }
        .btn-danger { background-color: #d9534f; color: #fff; border: none; }
        .btn-secondary { background-color: #777; color: #fff; border: none; }
        .btn-primary { background-color: #0275d8; color: #fff; border: none; }
        .cart-actions { margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>
    <h1>Your Shopping Cart</h1>

    <p><a href="product-list.php">&larr; Continue Shopping</a></p>

    <?php if ($successMsg !== ''): ?>
        <div class="success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <p>Your cart is currently empty.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price (MYR)</th>
                    <th>Quantity</th>
                    <th>Subtotal (MYR)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item->getProductName()) ?></td>
                        <td><?= number_format($item->getPrice(), 2) ?></td>
                        <td>
                            <form method="post" action="cart.php" style="display:inline;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($item->getProductId()) ?>">
                                <input type="number" name="quantity" value="<?= $item->getQuantity() ?>" min="1" style="width: 60px; padding: 4px;">
                                <button type="submit" class="btn btn-update">Update</button>
                            </form>
                        </td>
                        <td><?= number_format($item->getSubtotal(), 2) ?></td>
                        <td>
                            <form method="post" action="cart.php" style="display:inline;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($item->getProductId()) ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Remove this item from cart?');">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                    <td colspan="2"><strong>MYR <?= number_format($grandTotal, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="cart-actions">
            <form method="post" action="cart.php">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to clear your entire cart?');">Clear Cart</button>
            </form>
            <a href="#" class="btn btn-primary" onclick="alert('Checkout feature will be integrated with Person 3!'); return false;">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</body>
</html>
