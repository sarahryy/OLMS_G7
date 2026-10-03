<?php
/**
 * File: product-list.php
 * Module: Product Management
 * Description: Displays every product in the catalogue using the shared
 *              product display function. Serves as the main listing page
 *              and is linked from the navigation bar.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 03/10/2026
 */

require_once __DIR__ . '/../services/product-service.php';
require_once __DIR__ . '/../includes/product-display.php';

// ---- Fetch data ----
$products      = getAllProducts();
$categoryNames = getCategoryNames();
$productCount  = count($products);

$pageTitle = 'All Products';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">All Products</h1>
<p class="page-subtitle">
    <?= $productCount ?> product<?= $productCount === 1 ? '' : 's' ?>
    currently in the StationeryMart catalogue
</p>

<?php
displayProductTable(
    $products,
    $categoryNames,
    'There are no products in the catalogue yet.'
);
?>

<?php include __DIR__ . '/../includes/footer.php'; ?>