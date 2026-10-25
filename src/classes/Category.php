<?php
/**
 * File: Category.php
 * Module: Category Management
 * Description: Represents a product category.
 * Author: Savitta
 */

class Category {
    private $categoryId;
    private $categoryName;

    public function __construct($categoryId, $categoryName) {
        $this->categoryId = $categoryId;
        $this->categoryName = $categoryName;
    }

    // Getters
    public function getCategoryId() {
        return $this->categoryId;
    }

    public function getCategoryName() {
        return $this->categoryName;
    }

    // Setters
    public function setCategoryId($categoryId) {
        $this->categoryId = $categoryId;
    }

    public function setCategoryName($categoryName) {
        $this->categoryName = $categoryName;
    }
}
?>
