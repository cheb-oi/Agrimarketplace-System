<?php
require_once __DIR__ . '/config.php';
require_login('Admin');
$page_title = 'Moderate Products';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($pid && $action === 'delete') {
        $pdo->prepare('DELETE FROM products WHERE product_id = ?')->execute([$pid]);
        set_flash('info', 'Product removed from the marketplace.');
    } elseif ($pid && $action === 'toggle') {
        $pdo->prepare(
            "UPDATE products SET status = IF(status = 'Available', 'Unavailable', 'Available') WHERE product_id = ?"
        )->execute([$pid]);
        set_flash('success', 'Product visibility updated.');
    }
    header('Location: admin_products.php');
    exit;
}

$products = $pdo->query(
    'SELECT p.*, u.full_name AS farmer_name
     FROM products p JOIN users u ON u.user_id = p.farmer_id
     ORDER BY p.listed_at DESC'
)->fetchAll();

include __DIR__ . '/header.php';
?>
<h3 class="mb-3"><i class="bi bi-box-seam"></i> Product Moderation</h3>

<div class="table-responsive">
  <table class="table align-middle bg-white shadow-sm">
    <thead class="table-success">
      <tr><th>ID</th><th>Product</th><th>Farmer</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th style="width:130px;">Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td><?= (int)$p['product_id'] ?></td>
          <td><a href="product.php?id=<?= (int)$p['product_id'] ?>"><?= e($p['product_name']) ?></a></td>
          <td><?= e($p['farmer_name']) ?></td>
          <td><?= e($p['category']) ?></td>
          <td><?= format_money($p['price']) ?> / <?= e($p['unit']) ?></td>
          <td><?= (int)$p['quantity'] ?></td>
          <td><span class="badge bg-<?= $p['status'] === 'Available' ? 'success' : 'secondary' ?>"><?= e($p['status']) ?></span></td>
          <td>
            <form method="post" class="d-inline">
              <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
              <input type="hidden" name="action" value="toggle">
              <button class="btn btn-sm btn-outline-warning" title="Hide/Show"><i class="bi bi-eye-slash"></i></button>
            </form>
            <form method="post" class="d-inline" onsubmit="return confirm('Remove this product?');">
              <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/footer.php'; ?>
