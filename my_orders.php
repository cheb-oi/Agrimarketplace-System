<?php
require_once __DIR__ . '/config.php';
require_login('Buyer');
$page_title = 'My Orders';

$stmt = $pdo->prepare(
    'SELECT o.*, (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.order_id) AS item_count
     FROM orders o WHERE o.buyer_id = ? ORDER BY o.order_date DESC'
);
$stmt->execute([current_user_id()]);
$orders = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>
<h3 class="mb-3"><i class="bi bi-receipt"></i> My Orders</h3>

<?php if (!$orders): ?>
  <div class="alert alert-info">You have not placed any orders yet. <a href="index.php">Browse the marketplace</a>.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle bg-white shadow-sm">
      <thead class="table-success">
        <tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td>#<?= (int)$o['order_id'] ?></td>
            <td><?= e(date('d M Y H:i', strtotime($o['order_date']))) ?></td>
            <td><?= (int)$o['item_count'] ?></td>
            <td><?= format_money($o['total_amount']) ?></td>
            <td><?= status_badge($o['status']) ?></td>
            <td><a class="btn btn-sm btn-outline-success" href="order.php?id=<?= (int)$o['order_id'] ?>">Track / View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
