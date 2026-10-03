<?php
/**
 * File: product-display.php
 * Module: Product Management
 * Description: Shared data display function for product records. Renders a
 *              list of Product objects as a table, or a "not found" message
 *              when the list is empty. Invoked by the list, search, edit and
 *              delete pages so that product output is consistent system-wide.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 04/10/2026
 */

/**
 * Displays a list of products in a clear, organised table.
 *
 * When the supplied list is empty, a "no products found" message is shown
 * instead of an empty table.
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

    echo '<table class="data-table">';
    echo '<thead><tr>'
       . '<th>ID</th>'
       . '<th>Product Name</th>'
       . '<th>Category</th>'
       . '<th class="text-right">Price (RM)</th>'
       . '<th class="text-right">Stock</th>'
       . '<th>Brand</th>'
       . '</tr></thead>';
    echo '<tbody>';

    foreach ($products as $product) {
        $categoryId   = $product->getCategoryId();
        $categoryName = isset($categoryNames[$categoryId])
            ? $categoryNames[$categoryId]
            : 'Uncategorised';

        echo '<tr>';
        echo '<td>' . htmlspecialchars($product->getProductId()) . '</td>';
        echo '<td>' . htmlspecialchars($product->getProductName()) . '</td>';
        echo '<td>' . htmlspecialchars($categoryName) . '</td>';
        echo '<td class="text-right">'
           . htmlspecialchars(number_format((float)$product->getPrice(), 2))
           . '</td>';
        echo '<td class="text-right">' . htmlspecialchars($product->getStockQuantity()) . '</td>';
        echo '<td>' . htmlspecialchars($product->getBrand()) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}