<?php
require_once __DIR__ . '/config.php';
require_login('Admin');
$page_title = 'Reports';

// ---- CSV export ----
if (isset($_GET['export']) && $_GET['export'] === 'sales') {
    $rows = $pdo->query(
        "SELECT o.order_id, u.full_name AS buyer, o.order_date, o.status, o.total_amount,
                COALESCE(t.payment_method,'-') AS payment_method, COALESCE(t.payment_status,'-') AS payment_status
         FROM orders o
         JOIN users u ON u.user_id = o.buyer_id
         LEFT JOIN transactions t ON t.order_id = o.order_id
         ORDER BY o.order_date DESC"
    )->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sales_report_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Order ID','Buyer','Order Date','Status','Total (KES)','Payment Method','Payment Status']);
    foreach ($rows as $r) fputcsv($out, $r);
    fclose($out);
    exit;
}

// ---- On-screen report ----
$monthly = $pdo->query(
    "SELECT DATE_FORMAT(order_date, '%Y-%m') AS month,
            COUNT(*) AS orders,
            SUM(CASE WHEN status = 'Delivered' THEN total_amount ELSE 0 END) AS delivered_sales
     FROM orders GROUP BY month ORDER BY month DESC LIMIT 12"
)->fetchAll();

$top_products = $pdo->query(
    'SELECT p.product_name, u.full_name AS farmer, SUM(i.quantity) AS units_sold,
            SUM(i.quantity * i.unit_price) AS revenue
     FROM order_items i
     JOIN products p ON p.product_id = i.product_id
     JOIN users u ON u.user_id = p.farmer_id
     JOIN orders o ON o.order_id = i.order_id
     WHERE o.status != \'Cancelled\'
     GROUP BY p.product_id, p.product_name, u.full_name
     ORDER BY revenue DESC LIMIT 10'
)->fetchAll();

include __DIR__ . '/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><i class="bi bi-graph-up"></i> Reports</h3>
  <a class="btn btn-success" href="admin_report.php?export=sales">
    <i class="bi bi-download"></i> Export Sales Report (CSV)</a>
</div>

<div class="row g-4">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold">Monthly Orders &amp; Delivered Sales</div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Month</th><th>Orders</th><th>Delivered Sales</th></tr></thead>
          <tbody>
            <?php foreach ($monthly as $m): ?>
              <tr>
                <td><?= e($m['month']) ?></td>
                <td><?= (int)$m['orders'] ?></td>
                <td><?= format_money($m['delivered_sales']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$monthly): ?><tr><td colspan="3" class="text-muted">No data yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold">Top Products by Revenue</div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Product</th><th>Farmer</th><th>Units Sold</th><th>Revenue</th></tr></thead>
          <tbody>
            <?php foreach ($top_products as $t): ?>
              <tr>
                <td><?= e($t['product_name']) ?></td>
                <td><?= e($t['farmer']) ?></td>
                <td><?= (int)$t['units_sold'] ?></td>
                <td><?= format_money($t['revenue']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$top_products): ?><tr><td colspan="4" class="text-muted">No data yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
