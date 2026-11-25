<?php
/**
 * File: manage-payments.php
 * Module: Payment Administration
 * Description: Lists every payment recorded against an order and lets an
 *              administrator correct its status. Access control is handled
 *              by the shared auth-guard middleware and the page is drawn
 *              with the shared header and footer, so it behaves and looks
 *              the same as every other administration screen.
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

$allowedStatuses = ['Pending', 'Paid', 'Failed', 'Refunded'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $status    = $_POST['payment_status'] ?? '';

    // The status is checked against a defined list rather than accepted as
    // submitted, so a crafted request cannot store a value the column does
    // not allow.
    if ($paymentId > 0 && in_array($status, $allowedStatuses, true)) {
        $stmt = $conn->prepare(
            "UPDATE payments SET payment_status = ? WHERE payment_id = ?"
        );
        $stmt->bind_param('si', $status, $paymentId);

        if ($stmt->execute()) {
            $message = 'Payment status updated.';
        } else {
            $message = 'Unable to update the payment status.';
            $isError = true;
        }

        $stmt->close();
    } else {
        $message = 'That payment reference or status was not recognised.';
        $isError = true;
    }
}

$sql = "SELECT p.payment_id, p.order_id, u.name AS customer_name,
               p.amount, p.payment_method, p.payment_status, p.created_at
        FROM payments p
        JOIN orders o ON p.order_id = o.order_id
        JOIN users u ON o.user_id = u.user_id
        ORDER BY p.payment_id DESC";

$result = $conn->query($sql);

$pageTitle = 'Manage Payments';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Manage Payments</h1>
<p class="page-subtitle">Every payment recorded against an order</p>

<?php if ($message !== ''): ?>
    <div class="<?= $isError ? 'message-error' : 'message-success' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<table class="data-table">
    <thead>
    <tr>
        <th>Payment ID</th>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Amount</th>
        <th>Method</th>
        <th>Status</th>
        <th>Update</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$row['payment_id'] ?></td>
            <td><?= (int)$row['order_id'] ?></td>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td>RM <?= number_format((float)$row['amount'], 2) ?></td>
            <td><?= htmlspecialchars($row['payment_method']) ?></td>
            <td><?= htmlspecialchars($row['payment_status']) ?></td>
            <td>
                <form method="post" class="inline-form">
                    <input type="hidden" name="payment_id"
                           value="<?= (int)$row['payment_id'] ?>">

                    <select name="payment_status">
                        <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?= $status ?>"
                                <?= $row['payment_status'] === $status ? 'selected' : '' ?>>
                                <?= $status ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary btn-small">Save</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="7">No payment records found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<p><a class="link-back" href="admin-dashboard.php">Back to Dashboard</a></p>

<?php include __DIR__ . '/../includes/footer.php'; ?>