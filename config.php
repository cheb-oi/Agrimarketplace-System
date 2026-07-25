<?php
/**
 * FARMER-BUYER AGRICULTURAL MARKETPLACE MANAGEMENT SYSTEM
 * Central configuration: database connection, session, auth helpers.
 * Included by every page.
 */
session_start();

// ---- Database (XAMPP defaults) ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'farmer_marketplace');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'AgriMarket');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage())
        . '<br>Make sure MySQL is running in XAMPP and database.sql has been imported.');
}

// ---- Helpers ----
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

/** Redirect to login unless logged in (optionally restrict to a role). */
function require_login($role = null) {
    if (!is_logged_in()) {
        header('Location: ' . base_path() . 'login.php');
        exit;
    }
    if ($role !== null && current_role() !== $role) {
        header('Location: ' . base_path() . 'index.php');
        exit;
    }
}

/** All pages live in one folder, so base path is always ./ */
function base_path() {
    return './';
}

/** Flash messages (set on one request, shown on the next). */
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/** Unread message count for the navbar badge. */
function unread_count(PDO $pdo) {
    if (!is_logged_in()) return 0;
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM messages WHERE receiver_id = ? AND is_read = 0');
    $stmt->execute([current_user_id()]);
    return (int)$stmt->fetch()['c'];
}

/** Bootstrap badge colour per order status. */
function status_badge($status) {
    $map = [
        'Pending'   => 'warning',
        'Confirmed' => 'info',
        'Shipped'   => 'primary',
        'Delivered' => 'success',
        'Cancelled' => 'danger',
    ];
    $colour = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $colour . '">' . e($status) . '</span>';
}

function format_money($amount) {
    return 'KES ' . number_format((float)$amount, 2);
}
