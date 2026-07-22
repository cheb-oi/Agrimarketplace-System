<?php
require_once __DIR__ . '/config.php';
require_login('Admin');
$page_title = 'Admin Dashboard';

$stats = [
    'farmers'  => (int)$pdo->query("SELECT COUNT(*) c FROM users WHERE role = 'Farmer'")->fetch()['c'],
    'buyers'   => (int)$pdo->query("SELECT COUNT(*) c FROM users WHERE role = 'Buyer'")->fetch()['c'],
    'products' => (int)$pdo->query('SELECT COUNT(*) c FROM products')->fetch()['c'],
    'orders'   => (int)$pdo->query('SELECT COUNT(*) c FROM orders')->fetch()['c'],
    'sales'    => (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) t FROM orders WHERE status = 'Delivered'")->fetch()['t'],
    'pending'  => (int)$pdo->query("SELECT COUNT(*) c FROM orders WHERE status = 'Pending'")->fetch()['c'],
];

$by_status = $pdo->query(
    'SELECT status, COUNT(*) c FROM orders GROUP BY status'
)->fetchAll(PDO::FETCH_KEY_PAIR);

$recent_orders = $pdo->query(
    'SELECT o.order_id, o.order_date, o.total_amount, o.status, u.full_name AS buyer_name
     FROM orders o JOIN users u ON u.user_id = o.buyer_id
     ORDER BY o.order_date DESC LIMIT 8'
)->fetchAll();

include __DIR__ . '/header.php';
?>
<h3 class="mb-3"><i class="bi bi-speedometer2"></i> Administrative Dashboard</h3>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-2"><div class="card text-bg-success shadow-sm"><div class="card-body p-3">
    <div class="fs-4 fw-bold"><?= $stats['farmers'] ?></div><div class="small">Farmers</div></div></div></div>
  <div class="col-6 col-md-2"><div class="card text-bg-primary shadow-sm"><div class="card-body p-3">
    <div class="fs-4 fw-bold"><?= $stats['buyers'] ?></div><div class="small">Buyers</div></div></div></div>
  <div class="col-6 col-md-2"><div class="card text-bg-secondary shadow-sm"><div class="card-body p-3">
    <div class="fs-4 fw-bold"><?= $stats['products'] ?></div><div class="small">Products</div></div></div></div>
  <div class="col-6 col-md-2"><div class="card text-bg-info shadow-sm"><div class="card-body p-3">
    <div class="fs-4 fw-bold"><?= $stats['orders'] ?></div><div class="small">Orders</div></div></div></div>
  <div class="col-6 col-md-2"><div class="card text-bg-warning shadow-sm"><div class="card-body p-3">
    <div class="fs-4 fw-bold"><?= $stats['pending'] ?></div><div class="small">Pending</div></div></div></div>
  <div class="col-6 col-md-2"><div class="card text-bg-dark shadow-sm"><div class="card-body p-3">
    <div class="fs-6 fw-bold"><?= format_money($stats['sales']) ?></div><div class="small">Delivered Sales</div></div></div></div>
</div>

<div class="row g-4">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold">Orders by Status</div>
      <ul class="list-group list-group-flush">
        <?php foreach (['Pending','Confirmed','Shipped','Delivered','Cancelled'] as $s): ?>
          <li class="list-group-item d-flex justify-content-between">
            <?= status_badge($s) ?><span class="fw-bold"><?= (int)($by_status[$s] ?? 0) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold">Recent Orders</div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead><tr><th>#</th><th>Buyer</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($recent_orders as $o): ?>
              <tr>
                <td><?= (int)$o['order_id'] ?></td>
                <td><?= e($o['buyer_name']) ?></td>
                <td><?= e(date('d M Y', strtotime($o['order_date']))) ?></td>
                <td><?= format_money($o['total_amount']) ?></td>
                <td><?= status_badge($o['status']) ?></td>
                <td><a class="btn btn-sm btn-outline-success" href="order.php?id=<?= (int)$o['order_id'] ?>">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
