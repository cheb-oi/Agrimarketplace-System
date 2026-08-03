<?php
require_once __DIR__ . '/config.php';
require_login('Farmer');
$fid = current_user_id();

$categories = ['Cereals','Vegetables','Fruits','Dairy','Poultry','Livestock','Tubers','Other'];
$units      = ['kg','g','litre','ml','bunch','tray','bag','crate','piece','head'];

// Edit mode?
$editing = false;
$product = ['product_name' => '', 'description' => '', 'price' => '', 'quantity' => '',
            'unit' => 'kg', 'category' => 'Other', 'image_url' => null];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ? AND farmer_id = ?');
    $stmt->execute([$id, $fid]);
    $found = $stmt->fetch();
    if (!$found) {
        set_flash('danger', 'Product not found or not yours.');
        header('Location: farmer_products.php');
        exit;
    }
    $product = $found;
    $editing = true;
}
$page_title = $editing ? 'Edit Product' : 'Add Product';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['product_name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $qty   = (int)($_POST['quantity'] ?? -1);
    $unit  = $_POST['unit'] ?? 'kg';
    $cat   = $_POST['category'] ?? 'Other';

    if ($name === '') $errors[] = 'Product name is required.';
    if ($price <= 0)  $errors[] = 'Price must be greater than zero.';
    if ($qty < 0)     $errors[] = 'Quantity cannot be negative.';
    if (!in_array($unit, $units, true)) $unit = 'kg';
    if (!in_array($cat, $categories, true)) $cat = 'Other';

    // Optional image upload
    $image_url = $product['image_url'];
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
            $errors[] = 'Image must be a JPG, PNG, GIF, or WEBP file.';
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Image must be smaller than 3 MB.';
        } else {
            $dir = __DIR__ . '/uploads';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $fname = 'uploads/prod_' . $fid . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/' . $fname)) {
                $image_url = $fname;
            } else {
                $errors[] = 'Failed to save the uploaded image.';
            }
        }
    }

    if (!$errors) {
        if ($editing) {
            $stmt = $pdo->prepare(
                'UPDATE products SET product_name=?, description=?, price=?, quantity=?, unit=?, category=?, image_url=?
                 WHERE product_id=? AND farmer_id=?'
            );
            $stmt->execute([$name, $desc, $price, $qty, $unit, $cat, $image_url, $id, $fid]);
            set_flash('success', 'Product updated.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO products (farmer_id, product_name, description, price, quantity, unit, category, image_url)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$fid, $name, $desc, $price, $qty, $unit, $cat, $image_url]);
            set_flash('success', 'Product listed on the marketplace.');
        }
        header('Location: farmer_products.php');
        exit;
    }
    // Keep submitted values on error
    $product = array_merge($product, [
        'product_name' => $name, 'description' => $desc, 'price' => $price,
        'quantity' => $qty, 'unit' => $unit, 'category' => $cat, 'image_url' => $image_url,
    ]);
}
include __DIR__ . '/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h4 class="card-title mb-3">
          <i class="bi bi-<?= $editing ? 'pencil-square' : 'plus-circle' ?>"></i> <?= e($page_title) ?>
        </h4>
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger py-2"><?= e($err) ?></div>
        <?php endforeach; ?>
        <form method="post" enctype="multipart/form-data">
          <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input type="text" name="product_name" class="form-control" value="<?= e($product['product_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= e($product['description']) ?></textarea>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Price (KES)</label>
              <input type="number" step="0.01" min="0.01" name="price" class="form-control" value="<?= e($product['price']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Quantity</label>
              <input type="number" min="0" name="quantity" class="form-control" value="<?= e($product['quantity']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Unit</label>
              <select name="unit" class="form-select">
                <?php foreach ($units as $u): ?>
                  <option value="<?= e($u) ?>" <?= $product['unit'] === $u ? 'selected' : '' ?>><?= e($u) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-select">
              <?php foreach ($categories as $c): ?>
                <option value="<?= e($c) ?>" <?= $product['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Product Image (optional, max 3 MB)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <?php if ($product['image_url']): ?>
              <div class="form-text">Current image: <?= e($product['image_url']) ?> (uploading a new one replaces it)</div>
            <?php endif; ?>
          </div>
          <button class="btn btn-success"><?= $editing ? 'Save Changes' : 'List Product' ?></button>
          <a href="farmer_products.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
