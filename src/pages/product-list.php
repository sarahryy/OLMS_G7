<?php
/**
 * File: product-list.php
 * Module: Product Management
 * Description: Displays every product in the catalogue using the shared
 *              product display function. Serves as the main listing page
 *              and is linked from the navigation bar. Logged-in customers
 *              can add items to their cart directly from this table.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 03/10/2026
 * Last Modified: 27/10/2026
 */
 
require_once __DIR__ . '/../services/product-service.php';
require_once __DIR__ . '/../includes/product-display.php';
 
// ---- Fetch data ----
$products      = getAllProducts();
$categoryNames = getCategoryNames();
$productCount  = count($products);
 
// ---- Feedback returned from the add-to-cart handler ----
$cartStatus = $_GET['cart'] ?? '';
 
$pageTitle = 'All Products';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">All Products</h1>
<p class="page-subtitle">
    <?= $productCount ?> product<?= $productCount === 1 ? '' : 's' ?>
    currently in the StationeryMart catalogue
</p>
 
<?php if ($cartStatus === 'added'): ?>
    <div class="alert alert-success">Item added to your cart.</div>
<?php elseif ($cartStatus === 'nostock'): ?>
    <div class="alert alert-danger">That product is out of stock.</div>
<?php elseif ($cartStatus === 'notfound'): ?>
    <div class="alert alert-danger">That product could not be found.</div>
<?php elseif ($cartStatus === 'invalid'): ?>
    <div class="alert alert-danger">Please enter a valid quantity.</div>
<?php endif; ?>
 
<?php
displayProductTable(
    $products,
    $categoryNames,
    'There are no products in the catalogue yet.'
);
?>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 