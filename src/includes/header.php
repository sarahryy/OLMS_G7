<?php
/**
 * File: header.php
 * Module: Shared Presentation
 * Description: Shared page header for all StationeryMart (OLMS) pages.
 *              Outputs the document head, stylesheet link and a role-aware
 *              navigation bar. The links shown depend on whether the visitor
 *              is a guest, a logged-in customer, or an administrator, so that
 *              every page in the system is reachable from every other page.
 *
 *              The administrator menu groups related screens into click-to-
 *              open menus. Their styling is held in this file rather than in
 *              main-style.css because it belongs to this component alone and
 *              is defined once here for the whole system.
 *
 *              Set $pageTitle before including this file to set the page title.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 02/10/2026
 * Last Modified: 21/11/2026
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

    <style>
    /* ===== Administrator drop-down menus =====
       Each menu is a <details> element, so it opens on click with no
       JavaScript. The two menus share a name attribute, so opening one
       closes the other. */

    .nav-group {
        position: relative;
        display: inline-block;
        margin-right: 12px;
    }

    /* The toggle reads as a solid button sitting on the navigation bar.
       The browser's own disclosure triangle is removed in favour of a
       chevron drawn in CSS, which can rotate when the menu opens. */
    .nav-group-toggle {
        list-style: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background-color: #2a5699;
        color: #ffffff;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        user-select: none;
    }

    .nav-group-toggle::-webkit-details-marker { display: none; }
    .nav-group-toggle::marker { content: ''; }

    .nav-group-toggle:hover,
    .nav-group[open] .nav-group-toggle {
        background-color: #3165b3;
    }

    .nav-caret {
        width: 7px;
        height: 7px;
        border-right: 2px solid #ffffff;
        border-bottom: 2px solid #ffffff;
        transform: translateY(-2px) rotate(45deg);
        transition: transform 0.18s ease;
    }

    .nav-group[open] .nav-caret {
        transform: translateY(2px) rotate(-135deg);
    }

    .nav-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        min-width: 205px;
        background-color: #2a5699;
        border-radius: 8px;
        padding: 10px 0;
        box-shadow: 0 10px 26px rgba(0, 0, 0, 0.28);
        z-index: 200;
    }

    .nav-dropdown-label {
        display: block;
        padding: 8px 18px 4px 18px;
        font-size: 11px;
        font-weight: bold;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #9db8e0;
    }

    .nav-dropdown-link {
        display: block;
        padding: 9px 18px;
        color: #e8f0fb;
        text-decoration: none;
        font-size: 14px;
        white-space: nowrap;
    }

    .nav-dropdown-link:hover {
        background-color: #3165b3;
        color: #ffffff;
        text-decoration: none;
    }
    </style>
</head>
<body>

<nav class="nav-bar">
    <span class="nav-brand">StationeryMart</span>

    <?php if ($userRole === 'admin'): ?>
        <!-- Administrator menu. Related screens are grouped into menus that
             open on click, so the bar shows four items instead of eleven. -->
        <a class="nav-link" href="admin-dashboard.php">Dashboard</a>

        <details class="nav-group" name="adminnav">
            <summary class="nav-group-toggle">
                Products <span class="nav-caret"></span>
            </summary>
            <div class="nav-dropdown">
                <span class="nav-dropdown-label">Browse</span>
                <a class="nav-dropdown-link" href="product-list.php">All Products</a>
                <a class="nav-dropdown-link" href="search-product.php">Search</a>

                <span class="nav-dropdown-label">Maintain</span>
                <a class="nav-dropdown-link" href="add-product.php">Add Product</a>
                <a class="nav-dropdown-link" href="edit-product.php">Edit Product</a>
                <a class="nav-dropdown-link" href="delete-product.php">Delete Product</a>
                <a class="nav-dropdown-link" href="manage-category.php">Categories</a>
            </div>
        </details>

        <details class="nav-group" name="adminnav">
            <summary class="nav-group-toggle">
                Orders <span class="nav-caret"></span>
            </summary>
            <div class="nav-dropdown">
                <span class="nav-dropdown-label">Fulfilment</span>
                <a class="nav-dropdown-link" href="manage-orders.php">All Orders</a>
                <a class="nav-dropdown-link" href="manage-payments.php">Payments</a>
                <a class="nav-dropdown-link" href="manage-shipping.php">Shipping</a>
            </div>
        </details>

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
        <!-- Guest menu -->
        <a class="nav-link" href="product-list.php">All Products</a>
        <a class="nav-link" href="search-product.php">Search</a>
    <?php endif; ?>

    <span class="nav-right">
        <?php if ($isLoggedIn): ?>
            <span class="nav-user">
                <?php echo htmlspecialchars($userName); ?>
                (<?php echo htmlspecialchars($userRole); ?>)
            </span>
            <a class="nav-link" href="logout.php">Logout</a>
        <?php else: ?>
            <a class="nav-link" href="login.php">Login</a>
            <a class="nav-link" href="register.php">Register</a>
        <?php endif; ?>
    </span>
</nav>

<div class="page-container">