<?php
require_once __DIR__ . '/config.php';
require_login('Farmer');
$page_title = 'Farmer Dashboard';
$fid = current_user_id();

$stats = [];
$s = $pdo->prepare('SELECT COUNT(*) c FROM products WHERE farmer_id = ?');
$s->execute([$fid]); $stats['products'] = (int)$s->fetch()['c'];

$s = $pdo->prepare(
    "SELECT COUNT(DISTINCT o.order_id) c
     FROM orders o JOIN order_items i ON i.order_id = o.order_id
     JOIN products p ON p.product_id = i.product_id
     WHERE p.farmer_id = ? AND o.status = 'Pending'"
);
$s->execute([$fid]); $stats['pending'] = (int)$s->fetch()['c'];

$s = $pdo->prepare(
    "SELECT COALESCE(SUM(i.quantity * i.unit_price),0) t
     FROM orders o JOIN order_items i ON i.order_id = o.order_id
     JOIN products p ON p.product_id = i.product_id
     WHERE p.farmer_id = ? AND o.status = 'Delivered'"
);
$s->execute([$fid]); $stats['sales'] = (float)$s->fetch()['t'];

$stats['unread'] = unread_count($pdo);

// Recent orders containing this farmer's items
$s = $pdo->prepare(
    "SELECT o.order_id, o.order_date, o.status, u.full_name AS buyer_name,
            SUM(i.quantity * i.unit_price) AS my_amount
     FROM orders o
     JOIN order_items i ON i.order_id = o.order_id
     JOIN products p ON p.product_id = i.product_id
     JOIN users u ON u.user_id = o.buyer_id
     WHERE p.farmer_id = ?
     GROUP BY o.order_id, o.order_date, o.status, u.full_name
     ORDER BY o.order_date DESC LIMIT 5"
);
$s->execute([$fid]);
$recent = $s->fetchAll();

include __DIR__ . '/header.php';
?>
<h3 class="mb-3"><i class="bi bi-speedometer2"></i> Farmer Dashboard</h3>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="card text-bg-success shadow-sm"><div class="card-body">
    <div class="fs-3 fw-bold"><?= $stats['products'] ?></div><div>My Products</div></div></div></div>
  <div class="col-6 col-md-3"><div class="card text-bg-warning shadow-sm"><div class="card-body">
    <div class="fs-3 fw-bold"><?= $stats['pending'] ?></div><div>Pending Orders</div></div></div></div>
  <div class="col-6 col-md-3"><div class="card text-bg-primary shadow-sm"><div class="card-body">
    <div class="fs-5 fw-bold"><?= format_money($stats['sales']) ?></div><div>Delivered Sales</div></div></div></div>
  <div class="col-6 col-md-3"><div class="card text-bg-secondary shadow-sm"><div class="card-body">
    <div class="fs-3 fw-bold"><?= $stats['unread'] ?></div><div>Unread Messages</div></div></div></div>
</div>

<div class="mb-3">
  <a href="farmer_product_form.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add New Product</a>
  <a href="farmer_products.php" class="btn btn-outline-success">Manage Products</a>
  <a href="farmer_orders.php" class="btn btn-outline-success">Manage Orders</a>
</div>

<div class="card shadow-sm">
  <div class="card-header bg-white fw-bold">Recent Orders for My Products</div>
  <?php if (!$recent): ?>
    <div class="card-body text-muted">No orders yet. Once buyers order your products, they appear here.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead><tr><th>Order #</th><th>Buyer</th><th>Date</th><th>My Items Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recent as $o): ?>
            <tr>
              <td>#<?= (int)$o['order_id'] ?></td>
              <td><?= e($o['buyer_name']) ?></td>
              <td><?= e(date('d M Y', strtotime($o['order_date']))) ?></td>
              <td><?= format_money($o['my_amount']) ?></td>
              <td><?= status_badge($o['status']) ?></td>
              <td><a class="btn btn-sm btn-outline-success" href="order.php?id=<?= (int)$o['order_id'] ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
