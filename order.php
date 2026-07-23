<?php
require_once __DIR__ . '/config.php';
require_login();
$page_title = 'Order Details';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT o.*, u.full_name AS buyer_name, u.phone AS buyer_phone
     FROM orders o JOIN users u ON u.user_id = o.buyer_id WHERE o.order_id = ?'
);
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('warning', 'Order not found.');
    header('Location: index.php');
    exit;
}

// Access control: buyer who owns it, farmer with items in it, or admin.
$stmt = $pdo->prepare(
    'SELECT i.*, p.product_name, p.unit, p.farmer_id, u.full_name AS farmer_name
     FROM order_items i
     JOIN products p ON p.product_id = i.product_id
     JOIN users u ON u.user_id = p.farmer_id
     WHERE i.order_id = ?'
);
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$is_owner  = current_user_id() == $order['buyer_id'];
$is_seller = in_array(current_user_id(), array_column($items, 'farmer_id'));
if (!$is_owner && !$is_seller && current_role() !== 'Admin') {
    set_flash('danger', 'You do not have access to that order.');
    header('Location: index.php');
    exit;
}

// Buyer may cancel while order is still Pending.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel'
    && $is_owner && $order['status'] === 'Pending') {
    $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = ?")->execute([$id]);
    // Return reserved stock
    $restock = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE product_id = ?');
    foreach ($items as $it) $restock->execute([(int)$it['quantity'], (int)$it['product_id']]);
    set_flash('info', 'Order cancelled and stock returned to the farmer.');
    header('Location: order.php?id=' . $id);
    exit;
}

// Transaction record(s)
$txn_stmt = $pdo->prepare('SELECT * FROM transactions WHERE order_id = ? ORDER BY txn_id DESC');
$txn_stmt->execute([$id]);
$txns = $txn_stmt->fetchAll();

$statuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered'];
$current_index = array_search($order['status'], $statuses);

include __DIR__ . '/header.php';
?>
<h3 class="mb-1"><i class="bi bi-receipt-cutoff"></i> Order #<?= (int)$order['order_id'] ?></h3>
<p class="text-muted">Placed on <?= e(date('d M Y H:i', strtotime($order['order_date']))) ?> by <?= e($order['buyer_name']) ?></p>

<!-- Status tracker -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <?php if ($order['status'] === 'Cancelled'): ?>
      <div class="alert alert-danger mb-0"><i class="bi bi-x-circle"></i> This order was cancelled.</div>
    <?php else: ?>
      <div class="d-flex justify-content-between text-center">
        <?php foreach ($statuses as $i => $s): $done = $current_index !== false && $i <= $current_index; ?>
          <div class="flex-fill">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center
                        <?= $done ? 'bg-success text-white' : 'bg-light text-muted border' ?>"
                 style="width:42px;height:42px;">
              <i class="bi <?= $done ? 'bi-check-lg' : 'bi-circle' ?>"></i>
            </div>
            <div class="small mt-1 <?= $done ? 'fw-bold text-success' : 'text-muted' ?>"><?= e($s) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold">Items</div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead><tr><th>Product</th><th>Farmer</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= e($it['product_name']) ?></td>
                <td><?= e($it['farmer_name']) ?></td>
                <td><?= (int)$it['quantity'] ?> <?= e($it['unit']) ?></td>
                <td><?= format_money($it['unit_price']) ?></td>
                <td><?= format_money($it['quantity'] * $it['unit_price']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td><?= format_money($order['total_amount']) ?></td></tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white fw-bold">Delivery &amp; Payment</div>
      <div class="card-body small">
        <p><strong>Status:</strong> <?= status_badge($order['status']) ?></p>
        <p><strong>Delivery address:</strong><br><?= nl2br(e($order['delivery_address'])) ?></p>
        <?php foreach ($txns as $t): ?>
          <p class="mb-1"><strong>Payment:</strong> <?= e($t['payment_method']) ?> &mdash;
             <span class="badge bg-<?= $t['payment_status'] === 'Completed' ? 'success' : ($t['payment_status'] === 'Failed' ? 'danger' : 'warning') ?>">
               <?= e($t['payment_status']) ?></span>
             (<?= format_money($t['amount']) ?>)</p>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($is_owner && $order['status'] === 'Pending'): ?>
      <form method="post" onsubmit="return confirm('Cancel this order?');">
        <input type="hidden" name="action" value="cancel">
        <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle"></i> Cancel Order</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
