<?php
/**
 * File: CartItem.php
 * Module: Cart Management
 * Description: Represents an individual item added to the shopping cart.
 * Author: Savitta
 */

class CartItem {
    private $productId;
    private $productName;
    private $price;
    private $quantity;
    private $imageUrl;

    public function __construct($productId, $productName, $price, $quantity, $imageUrl = '') {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->price = (float)$price;
        $this->quantity = (int)$quantity;
        $this->imageUrl = $imageUrl;
    }

    // Getters
    public function getProductId() {
        return $this->productId;
    }

    public function getProductName() {
        return $this->productName;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getQuantity() {
        return $this->quantity;
    }

    public function getImageUrl() {
        return $this->imageUrl;
    }

    // Setters
    public function setQuantity($quantity) {
        $this->quantity = max(1, (int)$quantity);
    }

    public function getSubtotal() {
        return $this->price * $this->quantity;
    }
}
?>
