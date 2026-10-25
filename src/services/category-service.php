<?php
/**
 * File: category-service.php
 * Module: Category Management
 * Description: Data access layer for managing product categories (CRUD operations).
 * Author: Savitta
 */

require_once __DIR__ . '/../config/db-config.php';
require_once __DIR__ . '/../classes/Category.php';

/**
 * Fetches all categories ordered alphabetically by name.
 *
 * @return array
 */
function getAllCategories() {
    $conn = getConnection();
    $result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
    $categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $conn->close();
    return $categories;
}

/**
 * Fetches a single category by its ID.
 *
 * @param int $categoryId
 * @resid array|null
 */
function getCategoryById($categoryId) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT category_id, category_name FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $category ?: null;
}

/**
 * Creates a new category.
 *
 * @param string $categoryName
 * @return int Inserted category ID or 0 on failure
 */
function createCategory($categoryName) {
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
    $stmt->bind_param("s", $categoryName);
    $success = $stmt->execute();
    $newId = $success ? $stmt->insert_id : 0;
    $stmt->close();
    $conn->close();
    return $newId;
}

/**
 * Updates an existing category name.
 *
 * @param int $categoryId
 * @param string $categoryName
 * @return bool
 */
function updateCategory($categoryId, $categoryName) {
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
    $stmt->bind_param("si", $categoryName, $categoryId);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

/**
 * Deletes a category. 
 * Note: Database foreign key constraints (ON DELETE SET NULL) automatically handle linked products.
 *
 * @param int $categoryId
 * @return bool
 */
function deleteCategory($categoryId) {
    $conn = getConnection();
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}
?>
