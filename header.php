<?php
// Expects config.php already included and optional $page_title set.
$page_title = $page_title ?? APP_NAME;
$unread = is_logged_in() ? unread_count($pdo) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> | <?= e(APP_NAME) ?></title>
<link href="assets/app.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-agri navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php"><?= e(APP_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Marketplace</a></li>
        <?php if (current_role() === 'Buyer'): ?>
          <li class="nav-item"><a class="nav-link" href="cart.php"><i class="bi bi-cart3"></i> Cart
            <?php $n = array_sum(array_column($_SESSION['cart'] ?? [], 'quantity')); if ($n): ?>
              <span class="badge bg-warning text-dark"><?= $n ?></span>
            <?php endif; ?></a></li>
          <li class="nav-item"><a class="nav-link" href="my_orders.php">My Orders</a></li>
        <?php elseif (current_role() === 'Farmer'): ?>
          <li class="nav-item"><a class="nav-link" href="farmer_dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="farmer_products.php">My Products</a></li>
          <li class="nav-item"><a class="nav-link" href="farmer_orders.php">Orders</a></li>
        <?php elseif (current_role() === 'Admin'): ?>
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_users.php">Users</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_products.php">Products</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_orders.php">Orders</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_report.php">Reports</a></li>
        <?php endif; ?>
        <?php if (is_logged_in() && current_role() !== 'Admin'): ?>
          <li class="nav-item"><a class="nav-link" href="messages.php"><i class="bi bi-chat-dots"></i> Messages
            <?php if ($unread): ?><span class="badge bg-danger"><?= $unread ?></span><?php endif; ?></a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if (is_logged_in()): ?>
          <li class="nav-item"><span class="nav-link">
            <?= e($_SESSION['full_name']) ?> (<?= e(current_role()) ?>)</span></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4 flex-grow-1">
<?php if ($flash = get_flash()): ?>
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
