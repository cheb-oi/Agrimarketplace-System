<?php
require_once __DIR__ . '/config.php';
require_login('Farmer');
$page_title = 'Manage Orders';
$fid = current_user_id();

$allowed_transitions = [
    'Pending'   => ['Confirmed', 'Cancelled'],
    'Confirmed' => ['Shipped', 'Cancelled'],
    'Shipped'   => ['Delivered'],
];

// Status update (only for orders containing this farmer's products)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id'] ?? 0);
    $new = $_POST['new_status'] ?? '';

    $chk = $pdo->prepare(
        'SELECT o.status FROM orders o
         JOIN order_items i ON i.order_id = o.order_id
         JOIN products p ON p.product_id = i.product_id
         WHERE o.order_id = ? AND p.farmer_id = ? LIMIT 1'
    );
    $chk->execute([$oid, $fid]);
    $row = $chk->fetch();

    if ($row && in_array($new, $allowed_transitions[$row['status']] ?? [], true)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE order_id = ?')->execute([$new, $oid]);
        if ($new === 'Delivered') {
            $pdo->prepare(
                "UPDATE transactions SET payment_status = 'Completed', paid_at = NOW()
                 WHERE order_id = ? AND payment_status = 'Pending'"
            )->execute([$oid]);
        }
        if ($new === 'Cancelled') {
            // Return stock for all items in the order
            $items = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
            $items->execute([$oid]);
            $restock = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE product_id = ?');
            foreach ($items->fetchAll() as $it) $restock->execute([(int)$it['quantity'], (int)$it['product_id']]);
        }
        set_flash('success', "Order #$oid marked as $new.");
    } else {
        set_flash('danger', 'Invalid status change.');
    }
    header('Location: farmer_orders.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT o.order_id, o.order_date, o.status, o.delivery_address, u.full_name AS buyer_name, u.user_id AS buyer_id,
            SUM(i.quantity * i.unit_price) AS my_amount,
            GROUP_CONCAT(CONCAT(p.product_name, ' x', i.quantity) SEPARATOR ', ') AS item_list
     FROM orders o
     JOIN order_items i ON i.order_id = o.order_id
     JOIN products p ON p.product_id = i.product_id
     JOIN users u ON u.user_id = o.buyer_id
     WHERE p.farmer_id = ?
     GROUP BY o.order_id, o.order_date, o.status, o.delivery_address, u.full_name, u.user_id
     ORDER BY o.order_date DESC"
);
$stmt->execute([$fid]);
$orders = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>
<h3 class="mb-3"><i class="bi bi-truck"></i> Orders for My Products</h3>

<?php if (!$orders): ?>
  <div class="alert alert-info">No orders for your products yet.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle bg-white shadow-sm">
      <thead class="table-success">
        <tr><th>Order #</th><th>Buyer</th><th>My Items</th><th>Amount</th><th>Date</th><th>Status</th><th style="width:210px;">Update Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><a href="order.php?id=<?= (int)$o['order_id'] ?>">#<?= (int)$o['order_id'] ?></a></td>
            <td><?= e($o['buyer_name']) ?><br>
                <a class="small" href="messages.php?to=<?= (int)$o['buyer_id'] ?>"><i class="bi bi-chat-dots"></i> Message</a></td>
            <td class="small"><?= e($o['item_list']) ?></td>
            <td><?= format_money($o['my_amount']) ?></td>
            <td><?= e(date('d M Y', strtotime($o['order_date']))) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td>
              <?php $next = $allowed_transitions[$o['status']] ?? []; ?>
              <?php if ($next): ?>
                <form method="post" class="d-flex gap-1">
                  <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                  <select name="new_status" class="form-select form-select-sm">
                    <?php foreach ($next as $n): ?>
                      <option value="<?= e($n) ?>"><?= e($n) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-success">Update</button>
                </form>
              <?php else: ?>
                <span class="text-muted small">No further action</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
