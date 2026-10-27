
<?php
/**
 * File: OrderItem.php
 * Module: Order Management
 * Description: Represents a product within an order.
 * Author: Dini Nadhirah
 */

class OrderItem
{
    private $orderItemId;
    private $orderId;
    private $productId;
    private $productName;
    private $quantity;
    private $unitPrice;
    private $subtotal;

    public function __construct(
        $orderItemId,
        $orderId,
        $productId,
        $productName,
        $quantity,
        $unitPrice,
        $subtotal
    ) {
        $this->orderItemId = $orderItemId;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->quantity = (int) $quantity;
        $this->unitPrice = (float) $unitPrice;
        $this->subtotal = (float) $subtotal;
    }

    public function getOrderItemId()
    {
        return $this->orderItemId;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function getProductName()
    {
        return $this->productName;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    public function getSubtotal()
    {
        return $this->subtotal;
    }
}