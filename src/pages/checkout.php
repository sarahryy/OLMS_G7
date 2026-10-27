<?php
session_start();

require_once __DIR__ . '/../services/order-service.php';
require_once __DIR__ . '/../services/cart-service.php';

// Customer mesti login dahulu
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'customer') {
    header('Location: manage-orders.php');
    exit;
}

$cart = getCart();
$cartItems = $cart->getItems();
$grandTotal = $cart->getTotal();

$errorMessage = '';

// Proses checkout apabila butang ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingAddress = trim($_POST['shipping_address'] ?? '');

    $result = createOrderFromCart(
        (int) $_SESSION['userId'],
        $shippingAddress
    );

    if ($result['success']) {
        $_SESSION['orderSuccess'] = 'Your order has been placed successfully!';
        header('Location: order-history.php');
        exit;
    }

    $errorMessage = $result['message'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - StationeryMart</title>
    <link rel="stylesheet" href="../css/main-style.css">

    <style>
        .checkout-container {
            max-width: 850px;
            margin: 30px auto;
            padding: 25px;
        }

        .checkout-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .checkout-card h2 {
            margin-top: 0;
        }

        .checkout-item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .address-input {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
            font: inherit;
        }

        .checkout-button {
            display: inline-block;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .back-link {
            display: inline-block;
            margin-right: 12px;
            color: #2563eb;
            text-decoration: none;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .empty-message {
            text-align: center;
            padding: 40px 20px;
        }
    </style>
</head>

<body>

<div class="checkout-container">
    <h1>Checkout</h1>

    <?php if ($errorMessage !== ''): ?>
        <div class="error-message">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>

        <div class="checkout-card empty-message">
            <h2>Your cart is empty</h2>
            <p>Please add products to your cart before checking out.</p>
            <a href="search-product.php" class="checkout-button">
                Browse Products
            </a>
        </div>

    <?php else: ?>

        <div class="checkout-card">
            <h2>Order Summary</h2>

            <?php foreach ($cartItems as $item): ?>
                <div class="checkout-item">
                    <div>
                        <strong>
                            <?= htmlspecialchars($item->getProductName()) ?>
                        </strong>
                        <br>
                        Quantity: <?= (int) $item->getQuantity() ?>
                        × RM<?= number_format($item->getPrice(), 2) ?>
                    </div>

                    <div>
                        RM<?= number_format($item->getSubtotal(), 2) ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <h3 style="text-align: right;">
                Total: RM<?= number_format($grandTotal, 2) ?>
            </h3>

            <p>Payment method: Cash on Delivery (COD)</p>
        </div>

        <div class="checkout-card">
            <h2>Shipping Information</h2>

            <form method="POST" action="checkout.php">
                <label for="shipping_address">
                    Delivery Address
                </label>

                <textarea
                    id="shipping_address"
                    name="shipping_address"
                    class="address-input"
                    placeholder="Enter your full delivery address"
                    required
                ><?= htmlspecialchars($_POST['shipping_address'] ?? '') ?></textarea>

                <br><br>

                <a href="cart.php" class="back-link">
                    Back to Cart
                </a>

                <button type="submit" class="checkout-button">
                    Place Order
                </button>
            </form>
        </div>

    <?php endif; ?>
</div>

</body>
</html>