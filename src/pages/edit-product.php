<?php
/**
 * File: edit-product.php
 * Module: Product Management
 * Description: Allows an administrator to search for a product by ID,
 *              view its current details, edit the product information,
 *              validate the updated values and save the changes.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 04/10/2026
 */

require_once __DIR__ . '/../services/product-service.php';

$errors = [];
$successMsg = '';
$product = null;

$productId = trim($_GET['productId'] ?? $_POST['productId'] ?? '');

/*
 * Form data is used to keep the user's input when validation fails.
 */
$formData = [
    'productName'   => '',
    'description'   => '',
    'price'         => '',
    'stockQuantity' => '',
    'brand'         => '',
    'categoryId'    => '',
    'imageUrl'      => ''
];


/* ==================================================
   SEARCH PRODUCT
   ================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $productId !== '') {

    if (!ctype_digit($productId) || (int)$productId <= 0) {

        $errors[] = 'Product ID must be a valid positive number.';

    } else {

        $product = getProductById((int)$productId);

        if ($product === null) {

            $errors[] = 'Product not found.';

        } else {

            /*
             * Load the existing product values into the form.
             */
            $formData = [
                'productName'   => $product->getProductName(),
                'description'   => $product->getDescription(),
                'price'         => $product->getPrice(),
                'stockQuantity' => $product->getStockQuantity(),
                'brand'         => $product->getBrand(),
                'categoryId'    => $product->getCategoryId(),
                'imageUrl'      => $product->getImageUrl()
            ];
        }
    }
}


/* ==================================================
   UPDATE PRODUCT
   ================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productId = trim($_POST['productId'] ?? '');

    /*
     * Collect form input.
     */
    $formData['productName']   = trim($_POST['productName'] ?? '');
    $formData['description']   = trim($_POST['description'] ?? '');
    $formData['price']         = trim($_POST['price'] ?? '');
    $formData['stockQuantity'] = trim($_POST['stockQuantity'] ?? '');
    $formData['brand']         = trim($_POST['brand'] ?? '');
    $formData['categoryId']    = trim($_POST['categoryId'] ?? '');
    $formData['imageUrl']      = trim($_POST['imageUrl'] ?? '');


    /* ---------------- Validation ---------------- */

    if (!ctype_digit($productId) || (int)$productId <= 0) {

        $errors[] = 'Product ID must be valid.';
    }

    if ($formData['productName'] === '') {

        $errors[] = 'Product name cannot be empty.';
    }

    if (
        !is_numeric($formData['price']) ||
        (float)$formData['price'] <= 0
    ) {

        $errors[] = 'Price must be a positive number.';
    }

    if (
        $formData['stockQuantity'] === '' ||
        !ctype_digit($formData['stockQuantity'])
    ) {

        $errors[] = 'Stock quantity must be a non-negative integer.';
    }

    if ($formData['categoryId'] === '') {

        $errors[] = 'Please select a category.';
    }


    /* ---------------- Save changes ---------------- */

    if (empty($errors)) {

        $product = new Product(
            (int)$productId,
            $formData['productName'],
            $formData['description'],
            (float)$formData['price'],
            (int)$formData['stockQuantity'],
            $formData['brand'],
            $formData['categoryId'],
            $formData['imageUrl']
        );

        if (updateProduct($product)) {

            $successMsg = 'Product updated successfully.';

            /*
             * Retrieve the updated product from the database
             * so the page displays the latest saved values.
             */
            $product = getProductById((int)$productId);

            if ($product !== null) {

                $formData = [
                    'productName'   => $product->getProductName(),
                    'description'   => $product->getDescription(),
                    'price'         => $product->getPrice(),
                    'stockQuantity' => $product->getStockQuantity(),
                    'brand'         => $product->getBrand(),
                    'categoryId'    => $product->getCategoryId(),
                    'imageUrl'      => $product->getImageUrl()
                ];
            }

        } else {

            $errors[] = 'Failed to update product. Please try again.';
        }
    }
}


/* ==================================================
   LOAD CATEGORIES
   ================================================== */

$conn = getConnection();

$categories = $conn->query(
    "SELECT category_id, category_name
     FROM categories
     ORDER BY category_name"
);

$conn->close();


/* ==================================================
   PAGE HEADER
   ================================================== */

$pageTitle = 'Edit Product';

include __DIR__ . '/../includes/header.php';

?>


<h1 class="page-title">Edit Product</h1>

<p class="page-subtitle">
    Search for a product by ID and update its details.
</p>


<!-- Success Message -->

<?php if ($successMsg !== ''): ?>

    <div class="message-success">
        <?= htmlspecialchars($successMsg) ?>
    </div>

<?php endif; ?>


<!-- Error Messages -->

<?php if (!empty($errors)): ?>

    <div class="message-error">

        <ul>
            <?php foreach ($errors as $err): ?>

                <li>
                    <?= htmlspecialchars($err) ?>
                </li>

            <?php endforeach; ?>
        </ul>

    </div>

<?php endif; ?>


<!-- Search Product -->

<form method="get" action="edit-product.php">

    <div class="form-group">

        <label class="form-label" for="productId">
            Product ID
        </label>

        <input
            class="form-input"
            type="number"
            id="productId"
            name="productId"
            min="1"
            value="<?= htmlspecialchars($productId) ?>"
            placeholder="Enter product ID"
            required
        >

    </div>

    <button class="btn btn-primary" type="submit">
        Search Product
    </button>

</form>


<?php if ($product !== null): ?>

    <hr style="margin: 24px 0;">


    <h2 class="page-title">
        Product Details
    </h2>

    <p class="page-subtitle">
        Update the product information below.
    </p>


    <!-- Edit Product Form -->

    <form method="post" action="edit-product.php">

        <!-- Product ID -->

        <div class="form-group">

            <label class="form-label" for="editProductId">
                Product ID
            </label>

            <input
                class="form-input"
                type="text"
                id="editProductId"
                name="productId"
                value="<?= htmlspecialchars($product->getProductId()) ?>"
                readonly
            >

        </div>


        <!-- Product Name -->

        <div class="form-group">

            <label class="form-label" for="productName">
                Product Name *
            </label>

            <input
                class="form-input"
                type="text"
                id="productName"
                name="productName"
                value="<?= htmlspecialchars($formData['productName']) ?>"
                required
            >

        </div>


        <!-- Description -->

        <div class="form-group">

            <label class="form-label" for="description">
                Description
            </label>

            <textarea
                class="form-textarea"
                id="description"
                name="description"
            ><?= htmlspecialchars($formData['description']) ?></textarea>

        </div>


        <!-- Price -->

        <div class="form-group">

            <label class="form-label" for="price">
                Price (MYR) *
            </label>

            <input
                class="form-input"
                type="number"
                id="price"
                name="price"
                step="0.01"
                min="0.01"
                value="<?= htmlspecialchars($formData['price']) ?>"
                required
            >

        </div>


        <!-- Stock Quantity -->

        <div class="form-group">

            <label class="form-label" for="stockQuantity">
                Stock Quantity *
            </label>

            <input
                class="form-input"
                type="number"
                id="stockQuantity"
                name="stockQuantity"
                min="0"
                value="<?= htmlspecialchars($formData['stockQuantity']) ?>"
                required
            >

        </div>


        <!-- Brand -->

        <div class="form-group">

            <label class="form-label" for="brand">
                Brand
            </label>

            <input
                class="form-input"
                type="text"
                id="brand"
                name="brand"
                value="<?= htmlspecialchars($formData['brand']) ?>"
            >

        </div>


        <!-- Category -->

        <div class="form-group">

            <label class="form-label" for="categoryId">
                Category *
            </label>

            <select
                class="form-select"
                id="categoryId"
                name="categoryId"
                required
            >

                <option value="">
                    -- Select category --
                </option>

                <?php while ($row = $categories->fetch_assoc()): ?>

                    <option
                        value="<?= (int)$row['category_id'] ?>"
                        <?= (
                            (string)$formData['categoryId']
                            === (string)$row['category_id']
                        ) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($row['category_name']) ?>
                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <!-- Image URL -->

        <div class="form-group">

            <label class="form-label" for="imageUrl">
                Image URL
            </label>

            <input
                class="form-input"
                type="text"
                id="imageUrl"
                name="imageUrl"
                value="<?= htmlspecialchars($formData['imageUrl']) ?>"
            >

        </div>


        <!-- Update Button -->

        <button
            class="btn btn-primary"
            type="submit"
        >
            Update Product
        </button>

    </form>

<?php endif; ?>


<?php include __DIR__ . '/../includes/footer.php'; ?>