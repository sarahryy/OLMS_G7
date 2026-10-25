<?php
/**
 * File: cart-service.php
 * Module: Cart Management
 * Description: Service functions to manage shopping cart state in session.
 * Author: Savitta
 */

require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/CartItem.php';

/**
 * Retrieves the active Cart object from the session, or initializes a new one.
 *
 * @return Cart
 */
function getCart() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['cart']) || !($_SESSION['cart'] instanceof Cart)) {
        $_SESSION['cart'] = new Cart();
    }

    return $_SESSION['cart'];
}

/**
 * Saves the Cart object back to the session.
 *
 * @param Cart $cart
 */
function saveCart(Cart $cart) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['cart'] = $cart;
}
?>
