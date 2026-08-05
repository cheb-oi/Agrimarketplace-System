<?php
require_once __DIR__ . '/config.php';
require_login('Buyer');
$page_title = 'Checkout';

$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    set_flash('info', 'Your cart is empty.');
    header('Location: index.php');
    exit;
}

// Load current product rows for the cart
$ids  = array_map('intval', array_keys($cart));
$in   = implode(',', $ids);
$rows = $pdo->query("SELECT * FROM products WHERE product_id IN ($in)")->fetchAll();

$items = [];
$total = 0;
foreach ($rows as $r) {
    $q = min((int)$cart[$r['product_id']]['quantity'], (int)$r['quantity']);
    if ($q < 1) continue;
    $items[] = ['p' => $r, 'q' => $q, 'sub' => $q * $r['price']];
    $total += $q * $r['price'];
}
if (!$items) {
    set_flash('warning', 'The items in your cart are no longer available.');
    header('Location: cart.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['delivery_address'] ?? '');
    $method  = $_POST['payment_method'] ?? '';
    if ($address === '') $errors[] = 'Delivery address is required.';
    if (!in_array($method, ['M-Pesa', 'Card', 'Cash on Delivery'], true)) $errors[] = 'Select a payment method.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // 1. Create order
            $stmt = $pdo->prepare(
                "INSERT INTO orders (buyer_id, total_amount, status, delivery_address) VALUES (?, ?, 'Pending', ?)"
            );
            $stmt->execute([current_user_id(), $total, $address]);
            $order_id = (int)$pdo->lastInsertId();

            // 2. Create order items + decrement stock
            $item_stmt  = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)'
            );
            $stock_stmt = $pdo->prepare(
                'UPDATE products SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?'
            );
            foreach ($items as $it) {
                $item_stmt->execute([$order_id, $it['p']['product_id'], $it['q'], $it['p']['price']]);
                $stock_stmt->execute([$it['q'], $it['p']['product_id'], $it['q']]);
                if ($stock_stmt->rowCount() === 0) {
                    throw new RuntimeException('Insufficient stock for ' . $it['p']['product_name']);
                }
            }

            // 3. Record transaction (payment gateway reserved for future integration)
            $txn = $pdo->prepare(
                "INSERT INTO transactions (order_id, amount, payment_method, payment_status) VALUES (?, ?, ?, 'Pending')"
            );
            $txn->execute([$order_id, $total, $method]);

            $pdo->commit();
            $_SESSION['cart'] = [];
            set_flash('success', "Order #$order_id placed successfully. You can track its status below.");
            header('Location: order.php?id=' . $order_id);
            exit;
        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = 'Order failed: ' . $ex->getMessage();
        }
    }
}
include __DIR__ . '/header.php';
?>
<h3 class="mb-3"><i class="bi bi-bag-check"></i> Checkout</h3>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="row g-4">
  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold">Order Summary</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($items as $it): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span><?= e($it['p']['product_name']) ?> &times; <?= $it['q'] ?> <?= e($it['p']['unit']) ?></span>
            <span><?= format_money($it['sub']) ?></span>
          </li>
        <?php endforeach; ?>
        <li class="list-group-item d-flex justify-content-between fw-bold">
          <span>Total</span><span><?= format_money($total) ?></span>
        </li>
      </ul>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold">Delivery &amp; Payment</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Delivery Address</label>
            <textarea name="delivery_address" class="form-control" rows="3" required
              placeholder="Town, estate, street / landmark"><?= e($_POST['delivery_address'] ?? '') ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select" required>
              <option value="Cash on Delivery">Cash on Delivery</option>
              <option value="M-Pesa">M-Pesa (pay on confirmation)</option>
              <option value="Card">Card (pay on confirmation)</option>
            </select>
            <div class="form-text">Online payment gateway integration is planned for a future release.</div>
          </div>
          <button class="btn btn-success w-100"><i class="bi bi-check2-circle"></i> Place Order</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
