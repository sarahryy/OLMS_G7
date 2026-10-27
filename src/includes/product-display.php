<?php
/**
 * File: product-display.php
 * Module: Product Management
 * Description: Shared data display function for product records. Renders a
 *              list of Product objects as a table, or a "not found" message
 *              when the list is empty. Invoked by the list, search, edit and
 *              delete pages so that product output is consistent system-wide.
 *              When the viewer is a logged-in customer an "Add to Cart"
 *              action column is appended, which is what connects the product
 *              catalogue to the shopping cart module.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 04/10/2026
 * Last Modified: 27/10/2026
 */
 
// The Cart object lives in the session, so its class must be known before
// the session is opened or the stored cart is restored unusable.
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/CartItem.php';
 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
/**
 * Displays a list of products in a clear, organised table.
 *
 * When the supplied list is empty, a "no products found" message is shown
 * instead of an empty table. An "Add to Cart" column is shown only to
 * logged-in customers, so administrators and guests see the plain catalogue.
 *
 * @param array  $products      List of Product objects to display
 * @param array  $categoryNames Map of category_id => category_name
 * @param string $emptyMessage  Message shown when the list is empty
 * @return void
 */
function displayProductTable($products, $categoryNames, $emptyMessage = 'No products found.')
{
    if (empty($products)) {
        echo '<div class="no-results">' . htmlspecialchars($emptyMessage) . '</div>';
        return;
    }
 
    // Only customers can buy, so only customers get the action column.
    $isCustomer = (($_SESSION['role'] ?? '') === 'customer');
 
    echo '<table class="data-table">';
    echo '<thead><tr>'
       . '<th>ID</th>'
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
        $categoryId   = $product->getCategoryId();
        $categoryName = isset($categoryNames[$categoryId])
            ? $categoryNames[$categoryId]
            : 'Uncategorised';
        $stock = (int)$product->getStockQuantity();
 
        echo '<tr>';
        echo '<td>' . htmlspecialchars($product->getProductId()) . '</td>';
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
                // The form posts to the cart handler, which adds the item and
                // returns the customer to the page they came from.
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
 