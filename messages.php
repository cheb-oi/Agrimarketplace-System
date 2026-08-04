<?php
require_once __DIR__ . '/config.php';
require_login();
if (current_role() === 'Admin') { header('Location: admin_dashboard.php'); exit; }
$page_title = 'Messages';
$me = current_user_id();

// Send a message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to      = (int)($_POST['to'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $chk = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND role IN ('Farmer','Buyer') AND is_active = 1");
    $chk->execute([$to]);
    if ($to && $to !== $me && $content !== '' && $chk->fetch()) {
        $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)')
            ->execute([$me, $to, $content]);
    }
    header('Location: messages.php?to=' . $to);
    exit;
}

$to = (int)($_GET['to'] ?? 0);

// Conversation partners (anyone I've exchanged messages with)
$stmt = $pdo->prepare(
    "SELECT u.user_id, u.full_name, u.role,
            MAX(m.sent_at) AS last_at,
            SUM(CASE WHEN m.receiver_id = :me1 AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread
     FROM messages m
     JOIN users u ON u.user_id = IF(m.sender_id = :me2, m.receiver_id, m.sender_id)
     WHERE m.sender_id = :me3 OR m.receiver_id = :me4
     GROUP BY u.user_id, u.full_name, u.role
     ORDER BY last_at DESC"
);
$stmt->execute(['me1' => $me, 'me2' => $me, 'me3' => $me, 'me4' => $me]);
$partners = $stmt->fetchAll();

// Selected conversation
$thread = [];
$partner = null;
if ($to) {
    $stmt = $pdo->prepare('SELECT user_id, full_name, role FROM users WHERE user_id = ?');
    $stmt->execute([$to]);
    $partner = $stmt->fetch();
    if ($partner) {
        // Mark incoming as read
        $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?')
            ->execute([$to, $me]);
        $stmt = $pdo->prepare(
            'SELECT * FROM messages
             WHERE (sender_id = :me1 AND receiver_id = :to1) OR (sender_id = :to2 AND receiver_id = :me2)
             ORDER BY sent_at ASC'
        );
        $stmt->execute(['me1' => $me, 'to1' => $to, 'to2' => $to, 'me2' => $me]);
        $thread = $stmt->fetchAll();
    }
}
include __DIR__ . '/header.php';
?>
<h3 class="mb-3"><i class="bi bi-chat-dots"></i> Messages</h3>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-bold">Conversations</div>
      <div class="list-group list-group-flush">
        <?php if (!$partners && !$partner): ?>
          <div class="list-group-item text-muted small">
            No conversations yet. Open a product and click "Message Farmer" to start one.
          </div>
        <?php endif; ?>
        <?php foreach ($partners as $c): ?>
          <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                    <?= $c['user_id'] == $to ? 'active' : '' ?>"
             href="messages.php?to=<?= (int)$c['user_id'] ?>">
            <span><?= e($c['full_name']) ?> <small class="text-muted">(<?= e($c['role']) ?>)</small></span>
            <?php if ($c['unread']): ?><span class="badge bg-danger"><?= (int)$c['unread'] ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card shadow-sm">
      <?php if (!$partner): ?>
        <div class="card-body text-muted">Select a conversation on the left, or message a farmer from a product page.</div>
      <?php else: ?>
        <div class="card-header bg-white fw-bold">
          Chat with <?= e($partner['full_name']) ?> <small class="text-muted">(<?= e($partner['role']) ?>)</small>
        </div>
        <div class="card-body" style="max-height:420px;overflow-y:auto;" id="chatbox">
          <?php if (!$thread): ?>
            <p class="text-muted">No messages yet. Say hello!</p>
          <?php endif; ?>
          <?php foreach ($thread as $m): $mine = $m['sender_id'] == $me; ?>
            <div class="d-flex <?= $mine ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
              <div class="p-2 rounded-3 <?= $mine ? 'bg-success text-white' : 'bg-light border' ?>" style="max-width:75%;">
                <div><?= nl2br(e($m['content'])) ?></div>
                <div class="small <?= $mine ? 'text-white-50' : 'text-muted' ?> text-end">
                  <?= e(date('d M H:i', strtotime($m['sent_at']))) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="card-footer bg-white">
          <form method="post" class="d-flex gap-2">
            <input type="hidden" name="to" value="<?= (int)$partner['user_id'] ?>">
            <input type="text" name="content" class="form-control" placeholder="Type a message..." required autofocus>
            <button class="btn btn-success"><i class="bi bi-send"></i></button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
  const box = document.getElementById('chatbox');
  if (box) box.scrollTop = box.scrollHeight;
</script>
<?php include __DIR__ . '/footer.php'; ?>
