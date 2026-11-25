<?php
/**
 * File: manage-shipping.php
 * Module: Shipping Administration
 * Description: Lists the shipping record for every order and lets an
 *              administrator set its status and tracking number. Access
 *              control is handled by the shared auth-guard middleware and
 *              the page is drawn with the shared header and footer, so it
 *              matches every other administration screen.
 *
 * Author: Member 4
 * Created: 14/11/2026
 * Last Modified: 20/11/2026
 */

require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db-config.php';

// Access control is delegated to the middleware rather than repeated here.
requireAdmin();

$conn    = getConnection();
$message = '';
$isError = false;

$allowedStatuses = [
    'Pending', 'Preparing', 'Shipped', 'Completed', 'Cancelled'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingId = (int)($_POST['shipping_id'] ?? 0);
    $status     = $_POST['shipping_status'] ?? '';
    $tracking   = trim($_POST['tracking_number'] ?? '');

    // The status is checked against a defined list and the tracking number
    // against the column width, so neither can reach the database in a
    // form it cannot store.
    if ($shippingId > 0
        && in_array($status, $allowedStatuses, true)
        && strlen($tracking) <= 100
    ) {
        $stmt = $conn->prepare(
            "UPDATE shipping
             SET shipping_status = ?, tracking_number = ?
             WHERE shipping_id = ?"
        );
        $stmt->bind_param('ssi', $status, $tracking, $shippingId);

        if ($stmt->execute()) {
            $message = 'Shipping details updated.';
        } else {
            $message = 'Unable to update the shipping details.';
            $isError = true;
        }

        $stmt->close();
    } else {
        $message = 'Check the status and keep the tracking number '
                 . 'under 100 characters.';
        $isError = true;
    }
}

$sql = "SELECT s.shipping_id, s.order_id, u.name AS customer_name,
               s.shipping_address, s.shipping_status, s.tracking_number
        FROM shipping s
        JOIN orders o ON s.order_id = o.order_id
        JOIN users u ON o.user_id = u.user_id
        ORDER BY s.shipping_id DESC";

$result = $conn->query($sql);

$pageTitle = 'Manage Shipping';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Manage Shipping</h1>
<p class="page-subtitle">Parcels to prepare and send</p>

<?php if ($message !== ''): ?>
    <div class="<?= $isError ? 'message-error' : 'message-success' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<table class="data-table">
    <thead>
    <tr>
        <th>Shipping ID</th>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Address</th>
        <th>Status</th>
        <th>Tracking Number</th>
        <th>Update</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$row['shipping_id'] ?></td>
            <td><?= (int)$row['order_id'] ?></td>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td><?= nl2br(htmlspecialchars($row['shipping_address'])) ?></td>
            <td><?= htmlspecialchars($row['shipping_status']) ?></td>
            <td>
                <?= htmlspecialchars($row['tracking_number'] ?? '') ?: 'Not available' ?>
            </td>
            <td>
                <form method="post" class="inline-form">
                    <input type="hidden" name="shipping_id"
                           value="<?= (int)$row['shipping_id'] ?>">

                    <select name="shipping_status">
                        <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?= $status ?>"
                                <?= $row['shipping_status'] === $status ? 'selected' : '' ?>>
                                <?= $status ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" name="tracking_number"
                           class="cell-input"
                           maxlength="100"
                           value="<?= htmlspecialchars($row['tracking_number'] ?? '') ?>"
                           placeholder="Tracking number">

                    <button type="submit" class="btn btn-primary btn-small">Save</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="7">No shipping records found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<p><a class="link-back" href="admin-dashboard.php">Back to Dashboard</a></p>

<?php include __DIR__ . '/../includes/footer.php'; ?>