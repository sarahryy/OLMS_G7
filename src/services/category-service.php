<?php
/**
 * File: category-service.php
 * Module: Category Management
 * Description: Data-access functions for the categories table. All queries
 *              use prepared statements so user input is never concatenated
 *              into SQL. Called by manage-category.php.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 27/10/2026
 */
 
require_once __DIR__ . '/../config/db-config.php';
 
/**
 * Retrieves every category, ordered by name.
 *
 * @return array List of associative arrays with category_id and category_name
 */
function getAllCategories()
{
    $conn = getConnection();
 
    $result     = $conn->query("SELECT category_id, category_name
                                FROM categories
                                ORDER BY category_name");
    $categories = [];
 
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
 
    $conn->close();
    return $categories;
}
 
/**
 * Retrieves a single category by its identifier.
 *
 * @param int $categoryId Identifier of the category to fetch
 * @return array|null The category row, or null when no match is found
 */
function getCategoryById($categoryId)
{
    $conn = getConnection();
 
    $stmt = $conn->prepare("SELECT category_id, category_name
                            FROM categories
                            WHERE category_id = ?
                            LIMIT 1");
    $id = (int)$categoryId;
    $stmt->bind_param('i', $id);
    $stmt->execute();
 
    $row = $stmt->get_result()->fetch_assoc();
 
    $stmt->close();
    $conn->close();
 
    return $row ?: null;
}
 
/**
 * Creates a new category.
 *
 * @param string $categoryName Name of the new category
 * @return int The new category identifier, or 0 when the insert failed
 */
function createCategory($categoryName)
{
    $conn = getConnection();
 
    try {
        $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->bind_param('s', $categoryName);
        $stmt->execute();
 
        $newId = $conn->insert_id;
 
        $stmt->close();
        $conn->close();
        return (int)$newId;
    } catch (mysqli_sql_exception $e) {
        // Duplicate names are rejected by the UNIQUE constraint.
        $conn->close();
        return 0;
    }
}
 
/**
 * Updates the name of an existing category.
 *
 * @param int    $categoryId   Identifier of the category to update
 * @param string $categoryName New name for the category
 * @return bool True when the update succeeded
 */
function updateCategory($categoryId, $categoryName)
{
    $conn = getConnection();
 
    try {
        $stmt = $conn->prepare("UPDATE categories
                                SET category_name = ?
                                WHERE category_id = ?");
        $id = (int)$categoryId;
        $stmt->bind_param('si', $categoryName, $id);
        $success = $stmt->execute();
 
        $stmt->close();
        $conn->close();
        return $success;
    } catch (mysqli_sql_exception $e) {
        $conn->close();
        return false;
    }
}
 
/**
 * Deletes a category. Products in that category keep their record and have
 * their category_id set to NULL by the foreign key rule.
 *
 * @param int $categoryId Identifier of the category to delete
 * @return bool True when the delete succeeded
 */
function deleteCategory($categoryId)
{
    $conn = getConnection();
 
    try {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $id = (int)$categoryId;
        $stmt->bind_param('i', $id);
        $success = $stmt->execute();
 
        $stmt->close();
        $conn->close();
        return $success;
    } catch (mysqli_sql_exception $e) {
        $conn->close();
        return false;
    }
}
 