<?php
require_once __DIR__ . '/config.php';
require_login('Admin');
$page_title = 'Manage Users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($uid && $uid !== (int)current_user_id()) { // admin cannot act on own account
        if ($action === 'toggle') {
            $pdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE user_id = ?')->execute([$uid]);
            set_flash('success', 'User status updated.');
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role != 'Admin'")->execute([$uid]);
            set_flash('info', 'User and their related records deleted.');
        }
    }
    header('Location: admin_users.php');
    exit;
}

$role_filter = $_GET['role'] ?? '';
$sql = 'SELECT * FROM users';
$params = [];
if (in_array($role_filter, ['Farmer','Buyer','Admin'], true)) {
    $sql .= ' WHERE role = ?';
    $params[] = $role_filter;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><i class="bi bi-people"></i> Manage Users</h3>
  <form method="get" class="d-flex gap-2">
    <select name="role" class="form-select" onchange="this.form.submit()">
      <option value="">All roles</option>
      <?php foreach (['Farmer','Buyer','Admin'] as $r): ?>
        <option value="<?= $r ?>" <?= $role_filter === $r ? 'selected' : '' ?>><?= $r ?>s</option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="table-responsive">
  <table class="table align-middle bg-white shadow-sm">
    <thead class="table-success">
      <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Joined</th><th style="width:160px;">Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= (int)$u['user_id'] ?></td>
          <td><?= e($u['full_name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><?= e($u['phone']) ?></td>
          <td><span class="badge bg-<?= ['Admin'=>'dark','Farmer'=>'success','Buyer'=>'primary'][$u['role']] ?>"><?= e($u['role']) ?></span></td>
          <td><span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? 'Active' : 'Deactivated' ?></span></td>
          <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
          <td>
            <?php if ((int)$u['user_id'] !== (int)current_user_id()): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                <input type="hidden" name="action" value="toggle">
                <button class="btn btn-sm btn-outline-warning" title="Activate/Deactivate">
                  <i class="bi bi-person-<?= $u['is_active'] ? 'dash' : 'check' ?>"></i></button>
              </form>
              <?php if ($u['role'] !== 'Admin'): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this user and all their data?');">
                  <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-muted small">You</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/footer.php'; ?>
