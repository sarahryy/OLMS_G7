
<?php
/**
 * File: Order.php
 * Module: Order Management
 * Description: Represents a customer order.
 * Author: Dini Nadhirah
 */

class Order
{
    private $orderId;
    private $userId;
    private $shippingAddress;
    private $totalAmount;
    private $status;
    private $createdAt;
    private $items = [];

    public function __construct(
        $orderId,
        $userId,
        $shippingAddress,
        $totalAmount,
        $status = 'Pending',
        $createdAt = null
    ) {
        $this->orderId = $orderId;
        $this->userId = $userId;
        $this->shippingAddress = $shippingAddress;
        $this->totalAmount = (float) $totalAmount;
        $this->status = $status;
        $this->createdAt = $createdAt;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    public function setStatus($status)
    {
        $allowedStatuses = [
            'Pending',
            'Preparing',
            'Shipped',
            'Completed',
            'Cancelled'
        ];

        if (in_array($status, $allowedStatuses, true)) {
            $this->status = $status;
        }
    }

    public function setItems(array $items)
    {
        $this->items = $items;
    }
}