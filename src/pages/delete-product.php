<?php
/**
 * File: delete-product.php
 * Module: Product Management
 * Description: Administrator interface to search for a product by ID, review
 *              its full details, confirm the deletion, and then view the
 *              updated catalogue. The catalogue is rendered by the shared
 *              display function rather than a table of its own.
 * Author: Savitta Ram
 * Created: 04/10/2026
 * Last Modified: 27/10/2026
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/product-service.php';
require_once __DIR__ . '/../includes/product-display.php';
 
requireAdmin();
 
$searchId = trim($_GET['id'] ?? '');
$message  = '';
$isError  = false;
$product  = null;
 
// Handle the deletion when the administrator confirms
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'confirm_delete') {
 
    $deleteId = (int)($_POST['product_id'] ?? 0);
 
    if ($deleteId > 0 && deleteProduct($deleteId)) {
        $message  = "Product ID {$deleteId} was successfully deleted.";
        $searchId = '';
    } else {
        $message = "Failed to delete product ID {$deleteId}.";
        $isError = true;
    }
} elseif ($searchId !== '') {
    // Look the product up so its details can be reviewed before deleting
    if (ctype_digit($searchId)) {
        $product = getProductById((int)$searchId);
    }
 
    if (!$product) {
        $message = 'Product not found.';
        $isError = true;
    }
}
 
$allProducts   = getAllProducts();
$categoryNames = getCategoryNames();
 
$pageTitle = 'Delete Product';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">Delete Product</h1>
<p class="page-subtitle">Search for a product by its ID, review it, then confirm the deletion</p>
 
<form method="get" action="delete-product.php">
    <div class="search-bar">
        <input type="number" id="id" name="id" class="form-input"
               value="<?= htmlspecialchars($searchId) ?>"
               placeholder="Enter product ID, e.g. 1" required>
        <button type="submit" class="btn btn-secondary">Search</button>
    </div>
</form>
 
<?php if ($message !== ''): ?>
    <div class="<?= $isError ? 'message-error' : 'message-success' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>
 
<?php if ($product): ?>
 
    <div class="confirm-panel">
        <h2 class="confirm-title">Confirm Deletion</h2>
 
        <div class="detail-row">
            <span class="detail-label">Product ID</span>
            <?= htmlspecialchars($product->getProductId()) ?>
        </div>
        <div class="detail-row">
            <span class="detail-label">Product Name</span>
            <?= htmlspecialchars($product->getProductName()) ?>
        </div>
        <div class="detail-row">
            <span class="detail-label">Description</span>
            <?= htmlspecialchars($product->getDescription() ?: 'Not provided') ?>
        </div>
        <div class="detail-row">
            <span class="detail-label">Price (RM)</span>
            <?= number_format((float)$product->getPrice(), 2) ?>
        </div>
        <div class="detail-row">
            <span class="detail-label">Stock Quantity</span>
            <?= htmlspecialchars($product->getStockQuantity()) ?>
        </div>
        <div class="detail-row">
            <span class="detail-label">Brand</span>
            <?= htmlspecialchars($product->getBrand() ?: 'Not provided') ?>
        </div>
        <div class="detail-row">
            <span class="detail-label">Category</span>
            <?php
            $catId = $product->getCategoryId();
            echo htmlspecialchars($categoryNames[$catId] ?? 'Uncategorised');
            ?>
        </div>
        <div class="detail-row">
            <span class="detail-label">Image URL</span>
            <?= htmlspecialchars($product->getImageUrl() ?: 'Not provided') ?>
        </div>
 
        <p style="margin-top:14px;"><strong>This cannot be undone.</strong>
            Are you sure you want to delete this product?</p>
 
        <form method="post" action="delete-product.php">
            <input type="hidden" name="product_id" value="<?= (int)$product->getProductId() ?>">
            <input type="hidden" name="action" value="confirm_delete">
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                <a class="btn btn-secondary" href="delete-product.php">No, Cancel</a>
            </div>
        </form>
    </div>
 
<?php endif; ?>
 
<h2 class="section-title">Current Product List</h2>
 
<?php
displayProductTable(
    $allProducts,
    $categoryNames,
    'There are no products in the catalogue yet.'
);
?>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 