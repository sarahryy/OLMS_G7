<?php
/**
 * File: OrderTest.php
 * Module: Testing — Cart and Orders
 * Description: Unit tests for the Cart, CartItem, Order and OrderItem
 *              classes, the session-backed cart service, and the read paths
 *              of the order service. The cart and order classes hold no
 *              database state, so they are tested directly; the order
 *              service is exercised only through calls that do not alter
 *              data, so the suite can be run repeatedly and safely.
 *
 * Author: Member 4
 * Created: 21/11/2026
 */

require_once __DIR__ . '/TestRunner.php';
require_once __DIR__ . '/../src/classes/Cart.php';
require_once __DIR__ . '/../src/classes/CartItem.php';
require_once __DIR__ . '/../src/classes/Order.php';
require_once __DIR__ . '/../src/classes/OrderItem.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/services/cart-service.php';
require_once __DIR__ . '/../src/services/order-service.php';

echo '== CartItem ==' . PHP_EOL;

$item = new CartItem(7, 'A4 Notebook', 4.50, 3, 'notebook.jpg');

assertEquals(7,             $item->getProductId(),   'constructor stores the product id');
assertEquals('A4 Notebook', $item->getProductName(), 'constructor stores the name');
assertEquals(4.50,          $item->getPrice(),       'constructor stores the price');
assertEquals(3,             $item->getQuantity(),    'constructor stores the quantity');
assertEquals(13.50,         $item->getSubtotal(),    'subtotal is price times quantity');

$item->setQuantity(5);
assertEquals(5,     $item->getQuantity(), 'setQuantity updates the quantity');
assertEquals(22.50, $item->getSubtotal(), 'the subtotal follows the new quantity');

echo PHP_EOL . '== Cart ==' . PHP_EOL;

$cart = new Cart();

assertEquals(0, count($cart->getItems()), 'a new cart is empty');
assertEquals(0, $cart->getTotal(),        'the total of an empty cart is zero');
assertEquals(0, $cart->getItemCount(),    'the item count of an empty cart is zero');

$cart->addItem(1, 'Blue Pen',    2.00, 2);
$cart->addItem(2, 'A4 Notebook', 4.50, 1);

assertEquals(2,    count($cart->getItems()), 'two different products make two lines');
assertEquals(8.50, $cart->getTotal(),        'the total adds both subtotals');
assertEquals(3,    $cart->getItemCount(),    'the item count adds both quantities');

// Adding a product already in the cart must raise its quantity rather than
// create a second line for the same product.
$cart->addItem(1, 'Blue Pen', 2.00, 3);

assertEquals(2,     count($cart->getItems()), 'adding the same product again adds no new line');
assertEquals(5,     $cart->getItems()[1]->getQuantity(), 'the quantities are added together');
assertEquals(14.50, $cart->getTotal(),        'the total reflects the raised quantity');

$cart->updateQuantity(1, 2);
assertEquals(2,    $cart->getItems()[1]->getQuantity(), 'updateQuantity sets an exact quantity');
assertEquals(8.50, $cart->getTotal(),                   'the total follows the new quantity');

// A quantity of zero or less means the customer wants the line gone.
$cart->updateQuantity(1, 0);
assertEquals(1, count($cart->getItems()), 'a quantity of zero removes the line');

$cart->addItem(3, 'Ruler', 1.20, 1);
$cart->updateQuantity(3, -4);
assertEquals(1, count($cart->getItems()), 'a negative quantity also removes the line');

$cart->updateQuantity(999, 5);
assertEquals(1, count($cart->getItems()), 'updating a product not in the cart changes nothing');

$cart->removeItem(2);
assertEquals(0, count($cart->getItems()), 'removeItem removes the line');
assertEquals(0, $cart->getTotal(),        'the total returns to zero');

$cart->addItem(4, 'Eraser', 0.80, 2);
$cart->clear();
assertEquals(0, count($cart->getItems()), 'clear empties the cart');

echo PHP_EOL . '== cart-service ==' . PHP_EOL;

unset($_SESSION['cart']);

$sessionCart = getCart();
assertTrue($sessionCart instanceof Cart, 'getCart creates a Cart when none is stored');

$sessionCart->addItem(5, 'Stapler', 12.00, 1);
saveCart($sessionCart);

$reloaded = getCart();
assertEquals(1,     count($reloaded->getItems()), 'saveCart persists the cart to the session');
assertEquals(12.00, $reloaded->getTotal(),        'the stored total survives the round trip');

unset($_SESSION['cart']);

echo PHP_EOL . '== Order ==' . PHP_EOL;

$order = new Order(10, 3, '12 Jalan Mawar, Kajang', 59.90);

assertEquals(10,        $order->getOrderId(),     'constructor stores the order id');
assertEquals(3,         $order->getUserId(),      'constructor stores the user id');
assertEquals(59.90,     $order->getTotalAmount(), 'constructor casts the total to a float');
assertEquals('Pending', $order->getStatus(),      'a new order defaults to Pending');

$order->setStatus('Preparing');
assertEquals('Preparing', $order->getStatus(), 'setStatus accepts a known status');

// An unknown status must be ignored rather than stored, because the column
// is an ENUM and would reject it at the database.
$order->setStatus('Teleported');
assertEquals('Preparing', $order->getStatus(), 'setStatus ignores an unknown status');

$order->setItems([
    new OrderItem(1, 10, 1, 'Blue Pen',    2, 2.00, 4.00),
    new OrderItem(2, 10, 2, 'A4 Notebook', 1, 4.50, 4.50)
]);
assertEquals(2, count($order->getItems()), 'setItems stores the order lines');

echo PHP_EOL . '== OrderItem ==' . PHP_EOL;

$line = new OrderItem(1, 10, 7, 'A4 Notebook', '3', '4.50', '13.50');

assertEquals(7,             $line->getProductId(),   'constructor stores the product id');
assertEquals('A4 Notebook', $line->getProductName(), 'constructor stores the name');
assertTrue($line->getQuantity()  === 3,     'the quantity is cast to an integer');
assertTrue($line->getUnitPrice() === 4.50,  'the unit price is cast to a float');
assertTrue($line->getSubtotal()  === 13.50, 'the subtotal is cast to a float');

echo PHP_EOL . '== order-service (read only) ==' . PHP_EOL;

$none = getOrdersByUserId(999999);
assertTrue(is_array($none),   'getOrdersByUserId returns an array');
assertEquals(0, count($none), 'a user with no orders returns an empty array');

$all = getAllOrdersForAdmin();
assertTrue(is_array($all), 'getAllOrdersForAdmin returns an array');

// Cancelling an order that does not belong to the customer must be refused,
// otherwise changing the order id in a request would cancel someone else's
// order. The service reports the outcome as an array rather than a bare
// boolean, so the caller has a message to show as well as a result.
$cancelled = cancelCustomerOrder(999999, 999999);

assertTrue(is_array($cancelled),         'cancelCustomerOrder returns a result array');
assertTrue($cancelled['success'] === false,
    'cancelling an order that does not exist is refused');
assertTrue(!empty($cancelled['message']),
    'the refusal carries a message for the customer');

testSummary();