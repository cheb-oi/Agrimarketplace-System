<?php
require_once __DIR__ . '/config.php';
require_login('Buyer');
$page_title = 'Shopping Cart';

$_SESSION['cart'] = $_SESSION['cart'] ?? []; // [product_id => ['quantity' => n]]

// --- Cart actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid    = (int)($_POST['product_id'] ?? 0);

    if ($action === 'add' && $pid) {
        $stmt = $pdo->prepare("SELECT quantity FROM products WHERE product_id = ? AND status = 'Available'");
        $stmt->execute([$pid]);
        $row = $stmt->fetch();
        if ($row) {
            $want = max(1, (int)($_POST['quantity'] ?? 1));
            $have = ($_SESSION['cart'][$pid]['quantity'] ?? 0) + $want;
            $_SESSION['cart'][$pid]['quantity'] = min($have, (int)$row['quantity']);
            set_flash('success', 'Product added to cart.');
        }
    } elseif ($action === 'update' && $pid && isset($_SESSION['cart'][$pid])) {
        $q = (int)($_POST['quantity'] ?? 1);
        if ($q <= 0) unset($_SESSION['cart'][$pid]);
        else $_SESSION['cart'][$pid]['quantity'] = $q;
    } elseif ($action === 'remove' && $pid) {
        unset($_SESSION['cart'][$pid]);
        set_flash('info', 'Item removed from cart.');
    }
    header('Location: cart.php');
    exit;
}

// --- Load cart contents ---
$items = [];
$total = 0;
if ($_SESSION['cart']) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $in  = implode(',', $ids);
    $rows = $pdo->query(
        "SELECT p.*, u.full_name AS farmer_name
         FROM products p JOIN users u ON u.user_id = p.farmer_id
         WHERE p.product_id IN ($in)"
    )->fetchAll();
    foreach ($rows as $r) {
        $q = min((int)$_SESSION['cart'][$r['product_id']]['quantity'], (int)$r['quantity']);
        $sub = $q * $r['price'];
        $total += $sub;
        $items[] = ['p' => $r, 'q' => $q, 'sub' => $sub];
    }
}
include __DIR__ . '/header.php';
?>
<h3 class="mb-3"><i class="bi bi-cart3"></i> Shopping Cart</h3>

<?php if (!$items): ?>
  <div class="alert alert-info">Your cart is empty. <a href="index.php">Browse the marketplace</a>.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle bg-white shadow-sm">
      <thead class="table-success">
        <tr><th>Product</th><th>Farmer</th><th>Unit Price</th><th style="width:140px;">Quantity</th><th>Subtotal</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): $p = $it['p']; ?>
          <tr>
            <td><?= e($p['product_name']) ?></td>
            <td><?= e($p['farmer_name']) ?></td>
            <td><?= format_money($p['price']) ?> / <?= e($p['unit']) ?></td>
            <td>
              <form method="post" class="d-flex gap-1">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                <input type="number" name="quantity" class="form-control form-control-sm"
                       value="<?= $it['q'] ?>" min="1" max="<?= (int)$p['quantity'] ?>">
                <button class="btn btn-sm btn-outline-secondary" title="Update"><i class="bi bi-arrow-repeat"></i></button>
              </form>
            </td>
            <td><?= format_money($it['sub']) ?></td>
            <td>
              <form method="post">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td colspan="2"><?= format_money($total) ?></td></tr>
      </tfoot>
    </table>
  </div>
  <div class="d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Continue Shopping</a>
    <a href="checkout.php" class="btn btn-success"><i class="bi bi-bag-check"></i> Proceed to Checkout</a>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
