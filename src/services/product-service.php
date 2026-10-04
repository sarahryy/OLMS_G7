<?php
/**
 * File: product-service.php
 * Module: Product Management
 * Description: Handles creation, retrieval, update and deletion of
 *              stationery products in the OLMS database. Data-access
 *              functions are separated from display logic.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 03/10/2026
 * Last Modified: 04/10/2026
 */

require_once __DIR__ . '/../config/db-config.php';
require_once __DIR__ . '/../classes/Product.php';

/**
 * Inserts a new product into the database.
 *
 * @param Product $product Product instance to persist
 * @return int The newly assigned product ID, or 0 on failure
 */
function createProduct($product)
{
    $conn = getConnection();

    // Store values in variables so bind_param can reference them
    $productName   = $product->getProductName();
    $description   = $product->getDescription();
    $price         = $product->getPrice();
    $stockQuantity = $product->getStockQuantity();
    $brand         = $product->getBrand();
    $categoryId    = $product->getCategoryId();
    $imageUrl      = $product->getImageUrl();

    $sql  = "INSERT INTO products
             (product_name, description, price, stock_quantity,
              brand, category_id, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'ssdisss',
        $productName,
        $description,
        $price,
        $stockQuantity,
        $brand,
        $categoryId,
        $imageUrl
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
 * Builds a Product object from a database result row.
 *
 * Shared by every function that reads product records so that the mapping
 * from table columns to object attributes is defined in one place only.
 *
 * @param array $row Associative array of one row from the products table
 * @return Product Product instance populated from the row
 */
function buildProductFromRow($row)
{
    return new Product(
        $row['product_id'],
        $row['product_name'],
        $row['description'],
        $row['price'],
        $row['stock_quantity'],
        $row['brand'],
        $row['category_id'],
        $row['image_url']
    );
}

/**
 * Retrieves all products in the catalogue.
 *
 * @return array List of Product objects; empty array if none found
 */
function getAllProducts()
{
    $conn = getConnection();

    $sql = "SELECT product_id, product_name, description, price,
                   stock_quantity, brand, category_id, image_url
            FROM products
            ORDER BY product_name";

    $result   = $conn->query($sql);
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = buildProductFromRow($row);
    }

    $conn->close();
    return $products;
}

/**
 * Searches the catalogue by product name or category.
 *
 * An empty keyword matches every product name. A category of "all" or an
 * empty string disables the category filter.
 *
 * @param string $keyword  Search term entered by the customer
 * @param string $category Category identifier to filter by, or "all"
 * @return array Matching products; empty array if none found
 */
function searchProducts($keyword, $category)
{
    $conn = getConnection();

    // Wildcards around the keyword so partial product names match
    $namePattern = '%' . $keyword . '%';

    // 0 means "no category filter"; the OR then short-circuits the condition
    $categoryFilter = ($category === 'all' || $category === '') ? 0 : (int)$category;

    $sql = "SELECT product_id, product_name, description, price,
                   stock_quantity, brand, category_id, image_url
            FROM products
            WHERE product_name LIKE ?
              AND (? = 0 OR category_id = ?)
            ORDER BY product_name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $namePattern, $categoryFilter, $categoryFilter);
    $stmt->execute();

    $result   = $stmt->get_result();
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = buildProductFromRow($row);
    }

    $stmt->close();
    $conn->close();
    return $products;
}

/**
 * Retrieves all category names keyed by their identifier.
 *
 * Used by display pages to show a readable category name instead of the
 * numeric foreign key stored on each product.
 *
 * @return array Map of category_id => category_name
 */
function getCategoryNames()
{
    $conn = getConnection();

    $result     = $conn->query("SELECT category_id, category_name
                                FROM categories
                                ORDER BY category_name");
    $categories = [];

    while ($row = $result->fetch_assoc()) {
        $categories[$row['category_id']] = $row['category_name'];
    }

    $conn->close();
    return $categories;
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
    $conn = getConnection();

    $sql  = "DELETE FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $conn->close();
        return false;
    }

    $stmt->bind_param('i', $productId);
    $success = $stmt->execute();

    $stmt->close();
    $conn->close();

    return $success;
}
