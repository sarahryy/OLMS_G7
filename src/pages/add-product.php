<?php
/**
 * File: add-product.php
 * Module: Product Management
 * Description: Administrator form for adding a new product to the catalogue.
 *              Validates input before persisting and displays errors or a
 *              success message.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 03/10/2026
 * Last Modified: 27/10/2026
 */
 
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../services/product-service.php';
 
requireAdmin();
 
$errors     = [];
$successMsg = '';
$formData   = [
    'productName'   => '',
    'description'   => '',
    'price'         => '',
    'stockQuantity' => '',
    'brand'         => '',
    'categoryId'    => '',
    'imageUrl'      => '',
];
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- Collect input ----
    $formData['productName']   = trim($_POST['productName']   ?? '');
    $formData['description']   = trim($_POST['description']   ?? '');
    $formData['price']         = trim($_POST['price']         ?? '');
    $formData['stockQuantity'] = trim($_POST['stockQuantity'] ?? '');
    $formData['brand']         = trim($_POST['brand']         ?? '');
    $formData['categoryId']    = trim($_POST['categoryId']    ?? '');
    $formData['imageUrl']      = trim($_POST['imageUrl']      ?? '');
 
    // ---- Validation ----
    if ($formData['productName'] === '') {
        $errors[] = 'Product name cannot be empty.';
    }
 
    if (!is_numeric($formData['price']) || (float)$formData['price'] <= 0) {
        $errors[] = 'Price must be a positive number.';
    }
 
    if ($formData['stockQuantity'] === '' || !ctype_digit($formData['stockQuantity'])) {
        $errors[] = 'Stock quantity must be a non-negative integer.';
    }
 
    if ($formData['categoryId'] === '') {
        $errors[] = 'Please select a category.';
    }
 
    // ---- Persist if valid ----
    if (empty($errors)) {
        $product = new Product(
            null,
            $formData['productName'],
            $formData['description'],
            (float)$formData['price'],
            (int)$formData['stockQuantity'],
            $formData['brand'],
            $formData['categoryId'],
            $formData['imageUrl']
        );
 
        $newId = createProduct($product);
 
        if ($newId > 0) {
            $successMsg = "Product added successfully (ID {$newId}).";
            $formData   = array_map(fn($v) => '', $formData);
        } else {
            $errors[] = 'Failed to insert product. Please try again.';
        }
    }
}
 
// ---- Load categories for the dropdown ----
$categoryNames = getCategoryNames();
 
$pageTitle = 'Add Product';
include __DIR__ . '/../includes/header.php';
?>
 
<h1 class="page-title">Add New Product</h1>
<p class="page-subtitle">Fields marked * are required</p>
 
<?php if ($successMsg !== ''): ?>
    <div class="message-success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
 
<?php if (!empty($errors)): ?>
    <div class="message-error">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
 
<form method="post" action="add-product.php">
    <div class="form-group">
        <label class="form-label" for="productName">Product Name *</label>
        <input type="text" id="productName" name="productName" class="form-input"
               value="<?= htmlspecialchars($formData['productName']) ?>" required>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="form-textarea"
                  rows="3"><?= htmlspecialchars($formData['description']) ?></textarea>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="price">Price (RM) *</label>
        <input type="number" id="price" name="price" class="form-input"
               step="0.01" min="0.01"
               value="<?= htmlspecialchars($formData['price']) ?>" required>
        <span class="form-hint">Numbers only, for example 12.50</span>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="stockQuantity">Stock Quantity *</label>
        <input type="number" id="stockQuantity" name="stockQuantity" class="form-input"
               min="0" value="<?= htmlspecialchars($formData['stockQuantity']) ?>" required>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="brand">Brand</label>
        <input type="text" id="brand" name="brand" class="form-input"
               value="<?= htmlspecialchars($formData['brand']) ?>">
    </div>
 
    <div class="form-group">
        <label class="form-label" for="categoryId">Category *</label>
        <select id="categoryId" name="categoryId" class="form-select" required>
            <option value="">-- Select category --</option>
            <?php foreach ($categoryNames as $id => $name): ?>
                <option value="<?= (int)$id ?>"
                    <?= ((string)$formData['categoryId'] === (string)$id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
 
    <div class="form-group">
        <label class="form-label" for="imageUrl">Image URL</label>
        <input type="text" id="imageUrl" name="imageUrl" class="form-input"
               value="<?= htmlspecialchars($formData['imageUrl']) ?>">
    </div>
 
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Add Product</button>
        <a class="link-back" href="product-list.php">Back to Catalogue</a>
    </div>
</form>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 