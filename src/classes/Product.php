<?php
/**
 * File: Product.php
 * Module: Product Management
 * Description: Represents a stationery product sold on StationeryMart.
 *              Encapsulates product attributes with private visibility
 *              and exposes accessor and mutator methods for each.
 * Author: Siti Sarah Raihanah Binti Azizan
 * Created: 03/10/2026
 */

class Product
{
    private $productId;
    private $productName;
    private $description;
    private $price;
    private $stockQuantity;
    private $brand;
    private $categoryId;
    private $imageUrl;

    /**
     * Constructs a Product instance.
     *
     * @param string $productId     Unique product identifier
     * @param string $productName   Display name of the product
     * @param string $description   Short description of the product
     * @param float  $price         Price per unit in MYR
     * @param int    $stockQuantity Units currently in stock
     * @param string $brand         Manufacturer or brand name
     * @param string $categoryId    Identifier of the owning category
     * @param string $imageUrl      Relative path to the product image
     */
    public function __construct(
        $productId,
        $productName,
        $description,
        $price,
        $stockQuantity,
        $brand,
        $categoryId,
        $imageUrl
    ) {
        $this->productId     = $productId;
        $this->productName   = $productName;
        $this->description   = $description;
        $this->price         = $price;
        $this->stockQuantity = $stockQuantity;
        $this->brand         = $brand;
        $this->categoryId    = $categoryId;
        $this->imageUrl      = $imageUrl;
    }

    // ---------------- Getters ----------------

    /** @return string */
    public function getProductId() { return $this->productId; }

    /** @return string */
    public function getProductName() { return $this->productName; }

    /** @return string */
    public function getDescription() { return $this->description; }

    /** @return float */
    public function getPrice() { return $this->price; }

    /** @return int */
    public function getStockQuantity() { return $this->stockQuantity; }

    /** @return string */
    public function getBrand() { return $this->brand; }

    /** @return string */
    public function getCategoryId() { return $this->categoryId; }

    /** @return string */
    public function getImageUrl() { return $this->imageUrl; }

    // ---------------- Setters ----------------

    /** @param string $productId */
    public function setProductId($productId) { $this->productId = $productId; }

    /** @param string $productName */
    public function setProductName($productName) { $this->productName = $productName; }

    /** @param string $description */
    public function setDescription($description) { $this->description = $description; }

    /** @param float $price */
    public function setPrice($price) { $this->price = $price; }

    /** @param int $stockQuantity */
    public function setStockQuantity($stockQuantity) { $this->stockQuantity = $stockQuantity; }

    /** @param string $brand */
    public function setBrand($brand) { $this->brand = $brand; }

    /** @param string $categoryId */
    public function setCategoryId($categoryId) { $this->categoryId = $categoryId; }

    /** @param string $imageUrl */
    public function setImageUrl($imageUrl) { $this->imageUrl = $imageUrl; }
}