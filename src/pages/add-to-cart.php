<?php
/**
 * File: add-to-cart.php
 * Module: Cart Management / System Integration
 * Description: Handler that receives an "Add to Cart" submission from the
 *              product catalogue, looks the product up through the product
 *              service, adds it to the session cart through the cart service,
 *              and returns the customer to the page they came from. This file
 *              is the integration point between the Product module and the
 *              Cart module.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 27/10/2026
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/product-service.php';
require_once __DIR__ . '/../services/cart-service.php';
 
// Only a logged-in customer may add items to a cart.
requireCustomer();
 
// Reject anything that is not a form submission.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product-list.php');
    exit;
}
 
$productId = (int)($_POST['product_id'] ?? 0);
$quantity  = (int)($_POST['quantity'] ?? 1);
 
// Guard against invalid input before touching the cart.
if ($productId <= 0 || $quantity <= 0) {
    header('Location: product-list.php?cart=invalid');
    exit;
}
 
// Look the product up so the cart stores the real name and price,
// not whatever was posted from the browser.
$product = null;
foreach (getAllProducts() as $candidate) {
    if ((int)$candidate->getProductId() === $productId) {
        $product = $candidate;
        break;
    }
}
 
if ($product === null) {
    header('Location: product-list.php?cart=notfound');
    exit;
}
 
// Never allow more than the available stock into the cart.
$stock = (int)$product->getStockQuantity();
if ($quantity > $stock) {
    $quantity = $stock;
}
 
if ($quantity <= 0) {
    header('Location: product-list.php?cart=nostock');
    exit;
}
 
$cart = getCart();
$cart->addItem(
    $product->getProductId(),
    $product->getProductName(),
    (float)$product->getPrice(),
    $quantity
);
saveCart($cart);
 
// Send the customer back where they came from, with a success flag.
$returnTo = $_SERVER['HTTP_REFERER'] ?? 'product-list.php';
$separator = (strpos($returnTo, '?') === false) ? '?' : '&';
header('Location: ' . $returnTo . $separator . 'cart=added');
exit;
 