<?php
/**
 * File: Cart.php
 * Module: Cart Management
 * Description: Manages the collection of CartItems for a shopping session.
 * Author: Savitta
 */

require_once __DIR__ . '/CartItem.php';

class Cart {
    /** @var CartItem[] */
    private $items = [];

    /**
     * Adds an item or increases its quantity if it already exists in the cart.
     */
    public function addItem($productId, $productName, $price, $quantity = 1, $imageUrl = '') {
        if (isset($this->items[$productId])) {
            $existingQty = $this->items[$productId]->getQuantity();
            $this->items[$productId]->setQuantity($existingQty + $quantity);
        } else {
            $this->items[$productId] = new CartItem($productId, $productName, $price, $quantity, $imageUrl);
        }
    }

    /**
     * Updates the quantity of a specific item.
     */
    public function updateQuantity($productId, $quantity) {
        if (isset($this->items[$productId])) {
            if ($quantity <= 0) {
                $this->removeItem($productId);
            } else {
                $this->items[$productId]->setQuantity($quantity);
            }
        }
    }

    /**
     * Removes an item from the cart.
     */
    public function removeItem($productId) {
        if (isset($this->items[$productId])) {
            unset($this->items[$productId]);
        }
    }

    /**
     * Clears all items from the cart.
     */
    public function clear() {
        $this->items = [];
    }

    /**
     * Returns all cart items.
     * @return CartItem[]
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * Calculates the grand total for all items in the cart.
     */
    public function getTotal() {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getSubtotal();
        }
        return $total;
    }

    /**
     * Returns total item count in the cart.
     */
    public function getItemCount() {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item->getQuantity();
        }
        return $count;
    }
}
?>
