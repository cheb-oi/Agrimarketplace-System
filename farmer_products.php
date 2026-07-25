<?php
require_once __DIR__ . '/config.php';
require_login('Farmer');
$page_title = 'My Products';
$fid = current_user_id();

// Delete / toggle status actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    // Verify ownership
    $chk = $pdo->prepare('SELECT product_id FROM products WHERE product_id = ? AND farmer_id = ?');
    $chk->execute([$pid, $fid]);
    if ($chk->fetch()) {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM products WHERE product_id = ?')->execute([$pid]);
            set_flash('info', 'Product deleted.');
        } elseif ($action === 'toggle') {
            $pdo->prepare(
                "UPDATE products SET status = IF(status = 'Available', 'Unavailable', 'Available') WHERE product_id = ?"
            )->execute([$pid]);
            set_flash('success', 'Listing status updated.');
        }
    }
    header('Location: farmer_products.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM products WHERE farmer_id = ? ORDER BY listed_at DESC');
$stmt->execute([$fid]);
$products = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><i class="bi bi-box-seam"></i> My Products</h3>
  <a href="farmer_product_form.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Product</a>
</div>

<?php if (!$products): ?>
  <div class="alert alert-info">You have not listed any products yet.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table align-middle bg-white shadow-sm">
      <thead class="table-success">
        <tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Listed</th><th style="width:220px;">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><?= e($p['product_name']) ?></td>
            <td><?= e($p['category']) ?></td>
            <td><?= format_money($p['price']) ?> / <?= e($p['unit']) ?></td>
            <td><?= (int)$p['quantity'] ?> <?= e($p['unit']) ?></td>
            <td><span class="badge bg-<?= $p['status'] === 'Available' ? 'success' : 'secondary' ?>"><?= e($p['status']) ?></span></td>
            <td><?= e(date('d M Y', strtotime($p['listed_at']))) ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="farmer_product_form.php?id=<?= (int)$p['product_id'] ?>">
                <i class="bi bi-pencil"></i> Edit</a>
              <form method="post" class="d-inline">
                <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                <input type="hidden" name="action" value="toggle">
                <button class="btn btn-sm btn-outline-secondary" title="Show/hide listing">
                  <i class="bi bi-eye<?= $p['status'] === 'Available' ? '-slash' : '' ?>"></i></button>
              </form>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this product permanently?');">
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
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
