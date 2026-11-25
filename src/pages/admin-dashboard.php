<?php
/**
 * File: admin-dashboard.php
 * Module: Administration
 * Description: Landing page for administrators. Brings every management
 *              function together on one screen and shows how much work is
 *              waiting in each, so an administrator can see what needs
 *              attention without opening each page in turn.
 *
 * Author: Member 4
 * Created: 20/11/2026
 */

require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db-config.php';

requireAdmin();

/**
 * Counts the rows matching a condition, returning 0 if the query fails.
 *
 * A dashboard figure is informational, so a counting error must not take
 * the whole page down with it.
 *
 * @param mysqli $conn Open database connection
 * @param string $sql  A query selecting a single count column named c
 * @return int The count, or 0 when it could not be read
 */
function dashboardCount($conn, $sql)
{
    try {
        $result = $conn->query($sql);
        if (!$result) {
            return 0;
        }
        $row = $result->fetch_assoc();
        return (int)($row['c'] ?? 0);
    } catch (Throwable $e) {
        error_log('Dashboard count failed: ' . $e->getMessage());
        return 0;
    }
}

$conn = getConnection();

$ordersWaiting = dashboardCount($conn,
    "SELECT COUNT(*) AS c FROM orders WHERE status IN ('Pending', 'Preparing')");

$paymentsUnpaid = dashboardCount($conn,
    "SELECT COUNT(*) AS c FROM payments WHERE payment_status = 'Pending'");

$parcelsToSend = dashboardCount($conn,
    "SELECT COUNT(*) AS c FROM shipping WHERE shipping_status IN ('Pending', 'Preparing')");

$productsLow = dashboardCount($conn,
    "SELECT COUNT(*) AS c FROM products WHERE stock_quantity < 10");

$conn->close();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Admin Dashboard</h1>
<p class="page-subtitle">Everything waiting for your attention</p>

<div class="dashboard-grid">

    <div class="card">
        <h2 class="card-title">Orders</h2>
        <p class="text-muted"><?= $ordersWaiting ?> order(s) not yet shipped</p>
        <a class="btn btn-primary" href="manage-orders.php">Manage Orders</a>
    </div>

    <div class="card">
        <h2 class="card-title">Payments</h2>
        <p class="text-muted"><?= $paymentsUnpaid ?> payment(s) still pending</p>
        <a class="btn btn-primary" href="manage-payments.php">Manage Payments</a>
    </div>

    <div class="card">
        <h2 class="card-title">Shipping</h2>
        <p class="text-muted"><?= $parcelsToSend ?> parcel(s) to prepare</p>
        <a class="btn btn-primary" href="manage-shipping.php">Manage Shipping</a>
    </div>

    <div class="card">
        <h2 class="card-title">Products</h2>
        <p class="text-muted"><?= $productsLow ?> product(s) low on stock</p>
        <a class="btn btn-primary" href="product-list.php">Manage Products</a>
    </div>

    <div class="card">
        <h2 class="card-title">Categories</h2>
        <p class="text-muted">Organise the catalogue</p>
        <a class="btn btn-secondary" href="manage-category.php">Manage Categories</a>
    </div>

    <div class="card">
        <h2 class="card-title">Users</h2>
        <p class="text-muted">Customer and administrator accounts</p>
        <a class="btn btn-secondary" href="manage-users.php">Manage Users</a>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>