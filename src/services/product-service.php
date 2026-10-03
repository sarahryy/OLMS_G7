<?php
/**
 * File: product-service.php
 * Module: Product Management
 * Description: Handles creation, retrieval, update and deletion of
 *              stationery products in the OLMS database. Data-access
 *              functions are separated from display logic.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 03/10/2026
 */

require_once __DIR__ . '/../config/db-config.php';
require_once __DIR__ . '/../classes/Product.php';

/**
 * Inserts a new product into the database.
 *
 * @param Product $product Product instance to persist
 * @return int The newly assigned product ID, or 0 on failure
 */
/**
 * Inserts a new product into the database.
 *
 * @param Product $product Product instance to persist
 * @return int The newly assigned product ID, or 0 on failure
 */
function createProduct($product)
{
    $conn = getConnection();

    $sql  = "INSERT INTO products
             (product_name, description, price, stock_quantity,
              brand, category_id, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'ssdisss',
        $product->getProductName(),
        $product->getDescription(),
        $product->getPrice(),
        $product->getStockQuantity(),
        $product->getBrand(),
        $product->getCategoryId(),
        $product->getImageUrl()
    );

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        $conn->close();
        return $newId;
    }

    $stmt->close();
    $conn->close();
    return 0;
}
/**
 * Searches the catalogue by product name or category.
 *
 * @param string $keyword  Search term entered by the customer
 * @param string $category Category filter, or "all"
 * @return array Matching products; empty array if none found
 */
function searchProducts($keyword, $category)
{
    // TODO: implement in Lab 3
}

/**
 * Retrieves a single product by its identifier.
 *
 * @param int $productId Unique product identifier
 * @return Product|null The matching Product, or null if not found
 */
function getProductById($productId)
{
    // TODO: implement in Lab 3
}

/**
 * Updates an existing product record.
 *
 * @param Product $product Product instance with updated values
 * @return bool True on success, false otherwise
 */
function updateProduct($product)
{
    // TODO: implement in Lab 4
}

/**
 * Deletes a product from the database.
 *
 * @param int $productId Identifier of the product to remove
 * @return bool True on success, false otherwise
 */
function deleteProduct($productId)
{
    // TODO: implement in Lab 4
}

/**
 * Retrieves all products in the catalogue.
 *
 * @return array List of Product objects; empty array if none found
 */
function getAllProducts()
{
    // TODO: implement in Lab 3
}