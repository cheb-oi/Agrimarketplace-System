<?php
require_once __DIR__ . '/config.php';
$page_title = 'Sign in';
$errors = [];

// ---- Login handling (unchanged logic) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (!$user['is_active']) {
            $errors[] = 'Your account has been deactivated. Contact the administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']   = (int)$user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $dest = ['Farmer' => 'farmer_dashboard.php', 'Admin' => 'admin_dashboard.php'][$user['role']] ?? 'index.php';
            header('Location: ' . $dest);
            exit;
        }
    } else {
        $errors[] = 'Invalid email or password.';
    }
}

// ---- Register errors round-tripped from register.php ----
$reg_errors = $_SESSION['reg_errors'] ?? [];
$reg_old    = $_SESSION['reg_old'] ?? [];
unset($_SESSION['reg_errors'], $_SESSION['reg_old']);

$active_tab = ($reg_errors || ($_GET['tab'] ?? '') === 'register') ? 'register' : 'login';
$flash = get_flash();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(APP_NAME) ?> — Sign in</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,500&family=Work+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root{
    --soil:#1B2416; --soil-2:#263320; --gold:#E3A857; --gold-dim:#B98A44;
    --sage:#7C9473; --buyer:#4E7C9E; --cream:#F3EEE0; --cream-2:#EAE2CC;
    --ink:#23291D; --error:#B3462C; --radius:2px;
    --serif:'Fraunces', Georgia, serif;
    --sans:'Work Sans', 'Segoe UI', system-ui, sans-serif;
    --mono:'IBM Plex Mono', Consolas, monospace;
  }
  *{ box-sizing:border-box; }
  body{ margin:0; min-height:100vh; font-family:var(--sans); color:var(--ink); background:var(--soil); }
  .stage{ display:grid; grid-template-columns:1.1fr 1fr; min-height:100vh; }

  /* ---------- Brand panel ---------- */
  .brand-panel{
    position:relative;
    background:
      radial-gradient(circle at 15% 20%, rgba(227,168,87,0.10), transparent 40%),
      repeating-linear-gradient(115deg, transparent 0 22px, rgba(124,148,115,0.07) 22px 23px),
      linear-gradient(160deg, var(--soil) 0%, var(--soil-2) 100%);
    color:var(--cream); padding:4.5rem 3.5rem 3rem;
    display:flex; flex-direction:column; justify-content:space-between; overflow:hidden;
  }
  .brand-mark{
    display:flex; align-items:center; gap:.6rem;
    font-family:var(--mono); font-size:.75rem; letter-spacing:.14em; text-transform:uppercase; color:var(--gold);
  }
  .brand-mark::before{
    content:""; width:8px; height:8px; background:var(--gold); border-radius:50%;
    box-shadow:0 0 0 3px rgba(227,168,87,0.18);
  }
  .brand-hero h1{
    font-family:var(--serif); font-weight:600; font-size:clamp(2.4rem, 4vw, 3.4rem);
    line-height:1.05; margin:2.2rem 0 1.1rem; max-width:11ch;
  }
  .brand-hero h1 em{ font-style:italic; font-weight:500; color:var(--gold); }
  .brand-hero p{ max-width:38ch; font-size:1.02rem; line-height:1.55; color:var(--cream-2); }

  .role-legend{
    display:flex; gap:1.75rem; margin-top:2.2rem;
    font-family:var(--mono); font-size:.72rem; color:var(--cream-2);
  }
  .role-legend div{ display:flex; align-items:center; gap:.5rem; }
  .swatch{ width:10px; height:10px; border-radius:2px; }

  /* ---------- Auth panel ---------- */
  .auth-panel{ background:var(--cream); display:flex; align-items:center; justify-content:center; padding:2.5rem 2rem; }
  .ticket-card{
    position:relative; width:100%; max-width:420px; background:#fff;
    border:1px solid #DED3B4; box-shadow:0 22px 46px -30px rgba(27,36,22,0.45);
  }

  .tabs{ display:flex; border-bottom:1px solid var(--cream-2); }
  .tab{
    flex:1; padding:1rem 0; background:none; border:none;
    font-family:var(--sans); font-weight:600; font-size:.92rem; color:#9C917A; cursor:pointer; position:relative;
    transition:color .15s ease; text-align:center;
  }
  .tab.active{ color:var(--ink); }
  .tab.active::after{
    content:""; position:absolute; left:1.5rem; right:1.5rem; bottom:-1px; height:2px; background:var(--gold);
  }
  .tab:focus-visible, button:focus-visible, input:focus-visible{ outline:2px solid var(--buyer); outline-offset:2px; }

  .panel{ display:none; padding:1.9rem 1.75rem 2.1rem; flex-direction:column; gap:1.1rem; }
  .panel.active{ display:flex; }
  .panel h2{ font-family:var(--serif); font-size:1.5rem; font-weight:600; margin:0 0 .15rem; }
  .panel .sub{ margin:0 0 .3rem; font-size:.86rem; color:#75705E; }

  label{
    font-size:.76rem; font-weight:600; letter-spacing:.03em; text-transform:uppercase;
    color:#5B5644; display:block; margin-bottom:.35rem;
  }
  .field{ display:flex; flex-direction:column; }
  input[type=text], input[type=email], input[type=password], input[type=tel]{
    width:100%; padding:.68rem .8rem; font-family:var(--sans); font-size:.94rem;
    border:1px solid #D8CBA3; border-radius:var(--radius); background:#FCFAF3; color:var(--ink);
  }
  input::placeholder{ color:#B0A688; }
  .row-2{ display:grid; grid-template-columns:1fr 1fr; gap:.9rem; }

  .stamp-row{ display:flex; gap:.8rem; }
  .stamp{
    flex:1; border:1.5px dashed #C9BC93; background:#FCFAF3; border-radius:var(--radius);
    padding:.75rem .5rem; text-align:center; cursor:pointer;
    font-family:var(--mono); font-size:.78rem; letter-spacing:.06em; text-transform:uppercase; color:#8B8266;
    transition:border-color .15s ease, color .15s ease, transform .1s ease;
  }
  .stamp:hover{ transform:translateY(-1px); }
  .stamp.selected{ border-style:solid; color:#fff; }
  .stamp.selected[data-role="Farmer"]{ background:var(--sage); border-color:var(--sage); }
  .stamp.selected[data-role="Buyer"]{ background:var(--buyer); border-color:var(--buyer); }

  .submit-btn{
    margin-top:.4rem; padding:.85rem 1rem; border:none; border-radius:var(--radius);
    background:var(--ink); color:var(--cream);
    font-family:var(--sans); font-weight:600; font-size:.94rem; cursor:pointer; transition:background .15s ease;
  }
  .submit-btn:hover{ background:#111710; }

  .aux-row{ display:flex; justify-content:space-between; align-items:center; font-size:.82rem; }
  .aux-row button{
    color:var(--gold-dim); background:none; border:none; padding:0; font:inherit; cursor:pointer;
  }
  .aux-row button:hover{ text-decoration:underline; }

  .form-msg{ font-size:.82rem; padding:.6rem .75rem; border-radius:var(--radius); }
  .form-msg.success{ background:#EAF1E4; color:#3D5A2C; }
  .form-msg.error{ background:#F6E4DD; color:var(--error); }

  @media (max-width: 860px){
    .stage{ grid-template-columns:1fr; }
    .brand-panel{ padding:2.6rem 1.6rem 2rem; }
    .brand-hero h1{ max-width:none; }
    .auth-panel{ padding:1.6rem 1.2rem 2.4rem; }
  }
  @media (prefers-reduced-motion: reduce){ .ticker-track{ animation:none; } }
</style>
</head>
<body>

<div class="stage">

  <aside class="brand-panel">
    <div>
      <div class="brand-mark"><?= e(APP_NAME) ?></div>
      <div class="brand-hero">
        <h1>Sell it fresh.<br>Buy it <em>direct.</em></h1>
        <p>A marketplace that puts Kenyan farmers and buyers in the same room &mdash; no brokers, no waiting, no guesswork on price.</p>
      </div>
    </div>
    <div>
      <div class="role-legend">
        <div><span class="swatch" style="background:var(--sage)"></span> Farmer accounts</div>
        <div><span class="swatch" style="background:var(--buyer)"></span> Buyer accounts</div>
      </div>
    </div>
  </aside>

  <main class="auth-panel">
    <div class="ticket-card">

      <div class="tabs" role="tablist">
        <button class="tab <?= $active_tab === 'login' ? 'active' : '' ?>" data-tab="login" type="button">Sign in</button>
        <button class="tab <?= $active_tab === 'register' ? 'active' : '' ?>" data-tab="register" type="button">Create account</button>
      </div>

      <!-- ---------- SIGN IN ---------- -->
      <form class="panel <?= $active_tab === 'login' ? 'active' : '' ?>" id="panel-login" method="post" action="login.php" novalidate>
        <div>
          <h2>Welcome back</h2>
          <p class="sub">Sign in to manage your listings, orders and messages.</p>
        </div>
        <?php if ($flash): ?>
          <div class="form-msg <?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
          <div class="form-msg error"><?= e($err) ?></div>
        <?php endforeach; ?>
        <div class="field">
          <label for="loginEmail">Email address</label>
          <input type="email" id="loginEmail" name="email" placeholder="you@example.com"
                 value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label for="loginPassword">Password</label>
          <input type="password" id="loginPassword" name="password" placeholder="********" required>
        </div>
        <button type="submit" class="submit-btn">Sign in</button>
        <div class="aux-row">
          <span></span>
          <span style="color:#B0A688">New here? <button type="button" data-switch="register">Create an account</button></span>
        </div>
      </form>

      <!-- ---------- CREATE ACCOUNT ---------- -->
      <form class="panel <?= $active_tab === 'register' ? 'active' : '' ?>" id="panel-register" method="post" action="register.php" novalidate>
        <div>
          <h2>Join the marketplace</h2>
          <p class="sub">Choose your role, then set up your account.</p>
        </div>
        <?php foreach ($reg_errors as $err): ?>
          <div class="form-msg error"><?= e($err) ?></div>
        <?php endforeach; ?>
        <div class="field">
          <label>I am a...</label>
          <div class="stamp-row" role="group" aria-label="Account role">
            <button type="button" class="stamp <?= ($reg_old['role'] ?? '') === 'Farmer' ? 'selected' : '' ?>" data-role="Farmer">Farmer</button>
            <button type="button" class="stamp <?= ($reg_old['role'] ?? '') === 'Buyer' ? 'selected' : '' ?>" data-role="Buyer">Buyer</button>
          </div>
          <input type="hidden" name="role" id="roleInput" value="<?= e($reg_old['role'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="regName">Full name</label>
          <input type="text" id="regName" name="full_name" placeholder="Jane Wanjiru"
                 value="<?= e($reg_old['full_name'] ?? '') ?>" required>
        </div>
        <div class="row-2">
          <div class="field">
            <label for="regEmail">Email address</label>
            <input type="email" id="regEmail" name="email" placeholder="you@example.com"
                   value="<?= e($reg_old['email'] ?? '') ?>" required>
          </div>
          <div class="field">
            <label for="regPhone">Phone</label>
            <input type="tel" id="regPhone" name="phone" placeholder="07XXXXXXXX"
                   value="<?= e($reg_old['phone'] ?? '') ?>">
          </div>
        </div>
        <div class="row-2">
          <div class="field">
            <label for="regPassword">Password</label>
            <input type="password" id="regPassword" name="password" placeholder="Min. 6 characters" required>
          </div>
          <div class="field">
            <label for="regConfirm">Confirm password</label>
            <input type="password" id="regConfirm" name="confirm" placeholder="Repeat password" required>
          </div>
        </div>
        <button type="submit" class="submit-btn">Create account</button>
        <div class="aux-row">
          <span></span>
          <span style="color:#B0A688">Already registered? <button type="button" data-switch="login">Sign in</button></span>
        </div>
      </form>

<script>
  // Tab switching (Sign in / Create account)
  function showTab(name) {
    document.querySelectorAll('.tab').forEach(function (t) {
      t.classList.toggle('active', t.dataset.tab === name);
    });
    document.getElementById('panel-login').classList.toggle('active', name === 'login');
    document.getElementById('panel-register').classList.toggle('active', name === 'register');
  }
  document.querySelectorAll('.tab').forEach(function (t) {
    t.addEventListener('click', function () { showTab(t.dataset.tab); });
  });
  document.querySelectorAll('[data-switch]').forEach(function (b) {
    b.addEventListener('click', function () { showTab(b.dataset.switch); });
  });

  // Role stamp selector
  document.querySelectorAll('.stamp').forEach(function (s) {
    s.addEventListener('click', function () {
      document.querySelectorAll('.stamp').forEach(function (o) { o.classList.remove('selected'); });
      s.classList.add('selected');
      document.getElementById('roleInput').value = s.dataset.role;
    });
  });
</script>
</body>
</html>
