<?php
/**
 * File: header.php
 * Module: Shared Presentation
 * Description: Shared page header for all StationeryMart (OLMS) pages.
 *              Outputs the document head, stylesheet link and a role-aware
 *              navigation bar. The links shown depend on whether the visitor
 *              is a guest, a logged-in customer, or an administrator, so that
 *              every page in the system is reachable from every other page.
 *              Set $pageTitle before including this file to set the page title.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 02/10/2026
 * Last Modified: 27/10/2026
 */
 
// The Cart object is stored in the session. PHP must know the Cart and
// CartItem classes BEFORE the session is opened, otherwise the stored
// object is restored as an unusable "incomplete class".
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/CartItem.php';
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
$pageTitle = isset($pageTitle) ? $pageTitle : 'StationeryMart';
 
// ---- Work out who is viewing the page ----
$isLoggedIn = isset($_SESSION['userId']);
$userRole   = $_SESSION['role'] ?? 'guest';
$userName   = $_SESSION['name'] ?? '';
 
// ---- Count the items in the cart so the nav can show a badge ----
$cartCount = 0;
if (isset($_SESSION['cart']) && $_SESSION['cart'] instanceof Cart) {
    $cartCount = count($_SESSION['cart']->getItems());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | StationeryMart</title>
    <link rel="stylesheet" href="../css/main-style.css">
</head>
<body>
 
<nav class="nav-bar">
    <span class="nav-brand">StationeryMart</span>
 
    <?php if ($userRole === 'admin'): ?>
        <!-- Administrator menu -->
        <a class="nav-link" href="product-list.php">All Products</a>
        <a class="nav-link" href="search-product.php">Search</a>
        <a class="nav-link" href="add-product.php">Add Product</a>
        <a class="nav-link" href="edit-product.php">Edit Product</a>
        <a class="nav-link" href="delete-product.php">Delete Product</a>
        <a class="nav-link" href="manage-category.php">Categories</a>
        <a class="nav-link" href="manage-orders.php">Orders</a>
        <a class="nav-link" href="manage-users.php">Users</a>
 
    <?php elseif ($userRole === 'customer'): ?>
        <!-- Customer menu -->
        <a class="nav-link" href="product-list.php">All Products</a>
        <a class="nav-link" href="search-product.php">Search</a>
        <a class="nav-link" href="cart.php">
            Cart<?php if ($cartCount > 0): ?> (<?php echo $cartCount; ?>)<?php endif; ?>
        </a>
        <a class="nav-link" href="order-history.php">My Orders</a>
        <a class="nav-link" href="profile.php">My Profile</a>
 
   <?php else: ?>
    <!-- Guest menu: only public navigation -->
<?php endif; ?>
 
    <span class="nav-right">
        <?php if ($isLoggedIn): ?>
            <span class="nav-user">
                <?php echo htmlspecialchars($userName); ?>
                (<?php echo htmlspecialchars($userRole); ?>)
            </span>
            <a class="nav-link" href="logout.php">Logout</a>
        <?php else: ?>
    <!-- Login and Register links hidden for guests -->
<?php endif; ?>
    </span>
</nav>
 
<div class="page-container">
 