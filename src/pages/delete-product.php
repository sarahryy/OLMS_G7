<?php
/**
 * File: delete-product.php
 * Module: Product Management
 * Description: Interface to search, preview, confirm, and execute deletion
 *              of a product, followed by displaying the updated product list.
 */

require_once __DIR__ . '/../services/product-service.php';

$searchId = trim($_GET['id'] ?? '');
$message  = '';
$isError  = false;
$product  = null;

// Handle deletion when user clicks "Yes, Delete"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_delete') {
    $deleteId = (int)($_POST['product_id'] ?? 0);
    
    if ($deleteId > 0 && deleteProduct($deleteId)) {
        $message = "Product ID {$deleteId} was successfully deleted.";
        $searchId = '';
    } else {
        $message = "Failed to delete Product ID {$deleteId}.";
        $isError = true;
    }
} 
// Handle product search view
elseif ($searchId !== '') {
    if (ctype_digit($searchId)) {
        $product = getProductById((int)$searchId);
    }
    
    if (!$product) {
        $message = "Product not found.";
        $isError = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Product — OLMS</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; line-height: 1.5; }
        .search-box { background: #f4f4f4; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .product-card { border: 2px solid #e0e0e0; border-radius: 5px; padding: 20px; background: #fafafa; margin-bottom: 20px; }
        .attribute-grid { display: grid; grid-template-columns: 150px 1fr; gap: 8px; margin-bottom: 15px; }
        .attribute-label { font-weight: bold; color: #333; }
        .alert-error { background: #fdd; color: #900; padding: 10px; border: 1px solid #c00; margin-bottom: 15px; border-radius: 4px; }
        .alert-success { background: #dfd; color: #060; padding: 10px; border: 1px solid #0a0; margin-bottom: 15px; border-radius: 4px; }
        .btn { padding: 8px 16px; border: none; cursor: pointer; text-decoration: none; display: inline-block; border-radius: 4px; }
        .btn-danger { background: #d9534f; color: white; }
        .btn-secondary { background: #6c757d; color: white; margin-left: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>

    <h1>Delete Product</h1>

    <!-- 1. Search Box -->
    <div class="search-box">
        <form method="get" action="delete-product.php">
            <label for="id"><strong>Search Product by ID to Delete:</strong></label><br><br>
            <input type="number" id="id" name="id" value="<?= htmlspecialchars($searchId) ?>" placeholder="e.g. 1" required>
            <button type="submit" class="btn btn-secondary">Search</button>
        </form>
    </div>

    <!-- Status Messages -->
    <?php if ($message !== ''): ?>
        <div class="<?= $isError ? 'alert-error' : 'alert-success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- 2. Display Complete Attributes & 3. Confirmation Prompt -->
    <?php if ($product): ?>
        <div class="product-card">
            <h3>Product Details Preview</h3>
            <div class="attribute-grid">
                <div class="attribute-label">Product ID:</div>
                <div><?= htmlspecialchars($product->getProductId()) ?></div>

                <div class="attribute-label">Product Name:</div>
                <div><?= htmlspecialchars($product->getProductName()) ?></div>

                <div class="attribute-label">Description:</div>
                <div><?= htmlspecialchars($product->getDescription() ?: 'N/A') ?></div>

                <div class="attribute-label">Price (MYR):</div>
                <div>RM <?= number_format($product->getPrice(), 2) ?></div>

                <div class="attribute-label">Stock Quantity:</div>
                <div><?= htmlspecialchars($product->getStockQuantity()) ?></div>

                <div class="attribute-label">Brand:</div>
                <div><?= htmlspecialchars($product->getBrand() ?: 'N/A') ?></div>

                <div class="attribute-label">Category ID:</div>
                <div><?= htmlspecialchars($product->getCategoryId()) ?></div>

                <div class="attribute-label">Image URL:</div>
                <div><?= htmlspecialchars($product->getImageUrl() ?: 'N/A') ?></div>
            </div>

            <hr>
            <p style="color: #c00; font-weight: bold;">Are you sure you want to delete this product?</p>

            <form method="post" action="delete-product.php">
                <input type="hidden" name="product_id" value="<?= (int)$product->getProductId() ?>">
                <input type="hidden" name="action" value="confirm_delete">
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                <a href="delete-product.php" class="btn btn-secondary">No, Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <hr>

    <!-- 6. Display Full Product List Afterwards -->
    <h2>Current Product List</h2>

    <?php
    if (function_exists('displayAllProducts')) {
        displayAllProducts();
    } else {
        $allProducts = getAllProducts();
        if (!empty($allProducts)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price (MYR)</th>
                        <th>Stock</th>
                        <th>Brand</th>
                        <th>Category ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allProducts as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p->getProductId()) ?></td>
                            <td><?= htmlspecialchars($p->getProductName()) ?></td>
                            <td>RM <?= number_format($p->getPrice(), 2) ?></td>
                            <td><?= htmlspecialchars($p->getStockQuantity()) ?></td>
                            <td><?= htmlspecialchars($p->getBrand()) ?></td>
                            <td><?= htmlspecialchars($p->getCategoryId()) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No products currently available in the system.</p>
        <?php endif;
    }
    ?>

</body>
</html>
