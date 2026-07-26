<?php
require_once __DIR__ . '/config.php';
require_login('Admin');
$page_title = 'All Orders';

$status_filter = $_GET['status'] ?? '';
$statuses = ['Pending','Confirmed','Shipped','Delivered','Cancelled'];

$sql = 'SELECT o.*, u.full_name AS buyer_name,
        (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.order_id) AS item_count
        FROM orders o JOIN users u ON u.user_id = o.buyer_id';
$params = [];
if (in_array($status_filter, $statuses, true)) {
    $sql .= ' WHERE o.status = ?';
    $params[] = $status_filter;
}
$sql .= ' ORDER BY o.order_date DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><i class="bi bi-receipt"></i> All Orders</h3>
  <form method="get">
    <select name="status" class="form-select" onchange="this.form.submit()">
      <option value="">All statuses</option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="table-responsive">
  <table class="table align-middle bg-white shadow-sm">
    <thead class="table-success">
      <tr><th>Order #</th><th>Buyer</th><th>Items</th><th>Total</th><th>Date</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td>#<?= (int)$o['order_id'] ?></td>
          <td><?= e($o['buyer_name']) ?></td>
          <td><?= (int)$o['item_count'] ?></td>
          <td><?= format_money($o['total_amount']) ?></td>
          <td><?= e(date('d M Y H:i', strtotime($o['order_date']))) ?></td>
          <td><?= status_badge($o['status']) ?></td>
          <td><a class="btn btn-sm btn-outline-success" href="order.php?id=<?= (int)$o['order_id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?>
        <tr><td colspan="7" class="text-muted">No orders found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/footer.php'; ?>
