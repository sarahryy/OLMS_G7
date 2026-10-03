<?php
/**
 * File: add-product.php
 * Module: Product Management
 * Description: Admin form for adding a new product to the catalogue.
 *              Validates input before persisting and displays errors
 *              or a success message.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 03/10/2026
 */

require_once __DIR__ . '/../services/product-service.php';

$errors       = [];
$successMsg   = '';
$formData     = [
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

    if ($formData['stockQuantity'] === '' ||
        !ctype_digit($formData['stockQuantity'])) {
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
            $successMsg = "Product added successfully (ID: $newId).";
            $formData   = array_map(fn($v) => '', $formData);
        } else {
            $errors[] = 'Failed to insert product. Please try again.';
        }
    }
}

// ---- Load categories for the dropdown ----
$conn       = getConnection();
$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product — StationeryMart</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 640px; margin: 40px auto; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 6px; box-sizing: border-box; }
        .error   { background: #fdd; padding: 10px; border: 1px solid #c00; margin-bottom: 12px; }
        .success { background: #dfd; padding: 10px; border: 1px solid #0a0; margin-bottom: 12px; }
        button   { margin-top: 16px; padding: 10px 20px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Add New Product</h1>

    <?php if ($successMsg !== ''): ?>
        <div class="success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="add-product.php">
        <label for="productName">Product Name *</label>
        <input type="text" id="productName" name="productName"
               value="<?= htmlspecialchars($formData['productName']) ?>" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3"><?= htmlspecialchars($formData['description']) ?></textarea>

        <label for="price">Price (MYR) *</label>
        <input type="number" id="price" name="price" step="0.01" min="0.01"
               value="<?= htmlspecialchars($formData['price']) ?>" required>

        <label for="stockQuantity">Stock Quantity *</label>
        <input type="number" id="stockQuantity" name="stockQuantity" min="0"
               value="<?= htmlspecialchars($formData['stockQuantity']) ?>" required>

        <label for="brand">Brand</label>
        <input type="text" id="brand" name="brand"
               value="<?= htmlspecialchars($formData['brand']) ?>">

        <label for="categoryId">Category *</label>
        <select id="categoryId" name="categoryId" required>
            <option value="">-- Select category --</option>
            <?php while ($row = $categories->fetch_assoc()): ?>
                <option value="<?= (int)$row['category_id'] ?>"
                    <?= ($formData['categoryId'] == $row['category_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['category_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="imageUrl">Image URL</label>
        <input type="text" id="imageUrl" name="imageUrl"
               value="<?= htmlspecialchars($formData['imageUrl']) ?>">

        <button type="submit">Add Product</button>
    </form>
</body>
</html>