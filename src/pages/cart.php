
<?php
/**
 * File: cart.php
 * Module: Cart Management
 * Description: Shopping cart with automatic quantity and total updates.
 */

require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/cart-service.php';

requireCustomer();

$cart = getCart();
$successMsg = '';

// Handle AJAX quantity update
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'update'
) {
    $productId = $_POST['product_id'] ?? null;
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($productId !== null && $quantity >= 1) {
        $cart->updateQuantity($productId, $quantity);
        saveCart($cart);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'grandTotal' => number_format($cart->getTotal(), 2)
        ]);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit;
}

// Handle remove and clear actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'remove') {
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

$cartItems = $cart->getItems();
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

                    <td class="text-right">
                        <?= number_format($item->getPrice(), 2) ?>
                    </td>

                    <td>
                        <input
                            type="number"
                            class="qty-input"
                            value="<?= (int)$item->getQuantity() ?>"
                            min="1"
                            data-product-id="<?= htmlspecialchars((string)$item->getProductId()) ?>"
                            data-price="<?= htmlspecialchars((string)$item->getPrice()) ?>"
                        >
                    </td>

                    <td class="text-right">
                        <span class="item-subtotal">
                            <?= number_format($item->getSubtotal(), 2) ?>
                        </span>
                    </td>

                    <td>
                        <form method="post" action="cart.php" class="inline-form"
                              onsubmit="return confirm('Remove this item from your cart?');">

                            <input type="hidden" name="action" value="remove">

                            <input type="hidden" name="product_id"
                                   value="<?= htmlspecialchars($item->getProductId()) ?>">

                            <button type="submit" class="btn btn-danger btn-small">
                                Remove
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>

        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Grand Total</td>
                <td class="text-right">
                    RM <span id="grand-total"><?= number_format($grandTotal, 2) ?></span>
                </td>
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

<script>
document.addEventListener('DOMContentLoaded', function () {

    const quantityInputs = document.querySelectorAll('.qty-input');
    const grandTotalElement = document.getElementById('grand-total');

    quantityInputs.forEach(function (input) {

        input.addEventListener('change', async function () {

            let quantity = parseInt(input.value);

            if (isNaN(quantity) || quantity < 1) {
                quantity = 1;
                input.value = 1;
            }

            const productId = input.dataset.productId;
            const price = parseFloat(input.dataset.price);

            try {
                const formData = new FormData();

                formData.append('action', 'update');
                formData.append('product_id', productId);
                formData.append('quantity', quantity);

                const response = await fetch('cart.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {

                    const subtotal = price * quantity;

                    const row = input.closest('tr');

                    row.querySelector('.item-subtotal').textContent =
                        subtotal.toFixed(2);

                    grandTotalElement.textContent = result.grandTotal;

                } else {
                    alert('Unable to update cart. Please try again.');
                }

            } catch (error) {
                alert('An error occurred while updating your cart.');
            }

        });

    });

});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>