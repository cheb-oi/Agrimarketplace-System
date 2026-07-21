<?php
require_once __DIR__ . '/config.php';
$page_title = 'Marketplace';

// --- Search, filter, sort (Objective 2) ---
$search   = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$sort     = $_GET['sort'] ?? 'newest';

$categories = ['Cereals','Vegetables','Fruits','Dairy','Poultry','Livestock','Tubers','Other'];

$sql = "SELECT p.*, u.full_name AS farmer_name
        FROM products p
        JOIN users u ON u.user_id = p.farmer_id
        WHERE p.status = 'Available' AND p.quantity > 0 AND u.is_active = 1";
$params = [];

if ($search !== '') {
    $sql .= ' AND (p.product_name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (in_array($category, $categories, true)) {
    $sql .= ' AND p.category = ?';
    $params[] = $category;
}
$sql .= match ($sort) {
    'price_asc'  => ' ORDER BY p.price ASC',
    'price_desc' => ' ORDER BY p.price DESC',
    default      => ' ORDER BY p.listed_at DESC',
};

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>
<div class="p-4 mb-4 rounded-3" style="background: linear-gradient(120deg,#1B2416,#263320); color:#F3EEE0;">
  <h2 class="fw-bold" style="color:#F3EEE0;">Agricultural Marketplace</h2>
  <p class="mb-0" style="color:#EAE2CC;">Buy fresh produce directly from farmers &mdash; no middlemen, fair prices.</p>
</div>

<form class="row g-2 mb-4" method="get">
  <div class="col-md-5">
    <input type="text" name="q" class="form-control" placeholder="Search products..." value="<?= e($search) ?>">
  </div>
  <div class="col-md-3">
    <select name="category" class="form-select">
      <option value="">All categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <select name="sort" class="form-select">
      <option value="newest"     <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
      <option value="price_asc"  <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
    </select>
  </div>
  <div class="col-md-2">
    <button class="btn btn-success w-100">Search</button>
  </div>
</form>

<?php if (!$products): ?>
  <div class="alert alert-info">No products found. Try a different search or category.</div>
<?php endif; ?>

<div class="row g-4">
  <?php foreach ($products as $p): ?>
    <div class="col-sm-6 col-md-4 col-lg-3">
      <div class="card card-product h-100 shadow-sm">
        <?php if ($p['image_url']): ?>
          <img src="<?= e($p['image_url']) ?>" class="card-img-top" alt="<?= e($p['product_name']) ?>">
        <?php else: ?>
          <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height:180px;">
            <i class="bi bi-image"></i>
          </div>
        <?php endif; ?>
        <div class="card-body d-flex flex-column">
          <span class="badge bg-secondary align-self-start mb-1"><?= e($p['category']) ?></span>
          <h6 class="card-title mb-1"><?= e($p['product_name']) ?></h6>
          <div class="fw-bold text-success"><?= format_money($p['price']) ?> / <?= e($p['unit']) ?></div>
          <div class="small text-muted mb-2">
            <?= (int)$p['quantity'] ?> <?= e($p['unit']) ?> available &middot; by <?= e($p['farmer_name']) ?>
          </div>
          <a href="product.php?id=<?= (int)$p['product_id'] ?>" class="btn btn-outline-success btn-sm mt-auto">View Details</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
