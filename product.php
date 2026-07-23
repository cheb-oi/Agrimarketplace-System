<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT p.*, u.full_name AS farmer_name, u.phone AS farmer_phone, u.user_id AS farmer_user_id
     FROM products p JOIN users u ON u.user_id = p.farmer_id
     WHERE p.product_id = ?'
);
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    set_flash('warning', 'Product not found.');
    header('Location: index.php');
    exit;
}
$page_title = $p['product_name'];
include __DIR__ . '/header.php';
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php">Marketplace</a></li>
    <li class="breadcrumb-item active"><?= e($p['product_name']) ?></li>
  </ol>
</nav>

<div class="row g-4">
  <div class="col-md-5">
    <?php if ($p['image_url']): ?>
      <img src="<?= e($p['image_url']) ?>" class="img-fluid rounded shadow-sm" alt="<?= e($p['product_name']) ?>">
    <?php else: ?>
      <div class="d-flex align-items-center justify-content-center bg-light rounded" style="height:300px;">
        <i class="bi bi-image text-muted" style="font-size:5rem;"></i>
      </div>
    <?php endif; ?>
  </div>
  <div class="col-md-7">
    <span class="badge bg-secondary"><?= e($p['category']) ?></span>
    <span class="badge bg-<?= $p['status'] === 'Available' ? 'success' : 'secondary' ?>"><?= e($p['status']) ?></span>
    <h3 class="mt-2"><?= e($p['product_name']) ?></h3>
    <h4 class="text-success"><?= format_money($p['price']) ?> <small class="text-muted">per <?= e($p['unit']) ?></small></h4>
    <p><?= nl2br(e($p['description'])) ?></p>
    <table class="table table-sm w-auto">
      <tr><th class="pe-3">Available quantity</th><td><?= (int)$p['quantity'] ?> <?= e($p['unit']) ?></td></tr>
      <tr><th class="pe-3">Farmer</th><td><?= e($p['farmer_name']) ?></td></tr>
      <tr><th class="pe-3">Listed on</th><td><?= e(date('d M Y', strtotime($p['listed_at']))) ?></td></tr>
    </table>

    <?php if (current_role() === 'Buyer' && $p['status'] === 'Available' && $p['quantity'] > 0): ?>
      <form method="post" action="cart.php" class="row g-2 align-items-end" style="max-width:340px;">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
        <div class="col-6">
          <label class="form-label">Quantity (<?= e($p['unit']) ?>)</label>
          <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?= (int)$p['quantity'] ?>">
        </div>
        <div class="col-6">
          <button class="btn btn-success w-100"><i class="bi bi-cart-plus"></i> Add to Cart</button>
        </div>
      </form>
      <a class="btn btn-outline-secondary btn-sm mt-3" href="messages.php?to=<?= (int)$p['farmer_user_id'] ?>">
        <i class="bi bi-chat-dots"></i> Message Farmer
      </a>
    <?php elseif (!is_logged_in()): ?>
      <div class="alert alert-info py-2"><a href="login.php">Log in</a> as a buyer to order this product.</div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
