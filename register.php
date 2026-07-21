<?php
/**
 * Registration handler (logic only).
 * The registration FORM lives on login.php ("Create account" tab).
 * On validation errors, input + errors are round-tripped back via session.
 */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?tab=register');
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$role      = $_POST['role'] ?? '';
$password  = $_POST['password'] ?? '';
$confirm   = $_POST['confirm'] ?? '';

$errors = [];
if ($full_name === '') $errors[] = 'Full name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
if (!in_array($role, ['Farmer', 'Buyer'], true)) $errors[] = 'Please choose whether you are a Farmer or a Buyer.';
if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
if ($password !== $confirm) $errors[] = 'Passwords do not match.';

if (!$errors) {
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'An account with that email already exists.';
    }
}

if ($errors) {
    $_SESSION['reg_errors'] = $errors;
    $_SESSION['reg_old']    = ['full_name' => $full_name, 'email' => $email, 'phone' => $phone, 'role' => $role];
    header('Location: login.php?tab=register');
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO users (full_name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$full_name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $phone]);
set_flash('success', 'Account created successfully. You can now sign in.');
header('Location: login.php');
exit;
