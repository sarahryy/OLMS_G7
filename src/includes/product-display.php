
<?php
/**
 * File: product-display.php
 * Module: Product Management
 * Description: Shared product display function with product images
 *              and Add to Cart functionality.
 */

require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/CartItem.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function displayProductTable($products, $categoryNames, $emptyMessage = 'No products found.')
{
    if (empty($products)) {
        echo '<div class="no-results">' . htmlspecialchars($emptyMessage) . '</div>';
        return;
    }

    $isCustomer = (($_SESSION['role'] ?? '') === 'customer');

    echo '<table class="data-table">';
    echo '<thead><tr>'
       . '<th>ID</th>'
       . '<th>Image</th>'
       . '<th>Product Name</th>'
       . '<th>Category</th>'
       . '<th class="text-right">Price (RM)</th>'
       . '<th class="text-right">Stock</th>'
       . '<th>Brand</th>';

    if ($isCustomer) {
        echo '<th>Action</th>';
    }

    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($products as $product) {
        $categoryId = $product->getCategoryId();

        $categoryName = isset($categoryNames[$categoryId])
            ? $categoryNames[$categoryId]
            : 'Uncategorised';

        $stock = (int)$product->getStockQuantity();

        // Get product image from database.
        $imageUrl = trim((string)$product->getImageUrl());

        // Use default image when product has no image.
        if (empty($imageUrl)) {
            $imageUrl = '../uploads/products/no-image.png';
        }

        echo '<tr>';

        echo '<td>' . htmlspecialchars($product->getProductId()) . '</td>';

        echo '<td>';
        echo '<img src="' . htmlspecialchars($imageUrl) . '"
                   alt="' . htmlspecialchars($product->getProductName()) . '"
                   style="width:70px; height:70px; object-fit:contain;
                          border-radius:6px; background:#fff; padding:3px;">';
        echo '</td>';

        echo '<td>' . htmlspecialchars($product->getProductName()) . '</td>';

        echo '<td>' . htmlspecialchars($categoryName) . '</td>';

        echo '<td class="text-right">'
           . htmlspecialchars(number_format((float)$product->getPrice(), 2))
           . '</td>';

        echo '<td class="text-right">' . htmlspecialchars($stock) . '</td>';

        echo '<td>' . htmlspecialchars($product->getBrand()) . '</td>';

        if ($isCustomer) {
            echo '<td>';

            if ($stock > 0) {
                echo '<form method="post" action="add-to-cart.php" class="inline-form">'
                   . '<input type="hidden" name="product_id" value="'
                   . htmlspecialchars($product->getProductId()) . '">'
                   . '<input type="number" name="quantity" value="1" min="1" max="'
                   . $stock . '" class="qty-input">'
                   . '<button type="submit" class="btn btn-primary">Add to Cart</button>'
                   . '</form>';
            } else {
                echo '<span class="out-of-stock">Out of stock</span>';
            }

            echo '</td>';
        }

        echo '</tr>';
    }

    echo '</tbody></table>';
}
