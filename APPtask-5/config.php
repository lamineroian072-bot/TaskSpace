<?php
// config.php - Database connection & authentication helpers

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'boardinghouse_db');

function getDB()
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // List of credential combinations to attempt (Primary config first, then common XAMPP defaults)
    $attempts = [
        ['host' => DB_HOST, 'user' => DB_USER, 'pass' => DB_PASS],
        ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
        ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
        ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'root'],
        ['host' => 'localhost', 'user' => 'root', 'pass' => 'root'],
        ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'admin'],
        ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'sql'],
    ];

    $lastError = '';

    foreach ($attempts as $cfg) {
        try {
            $dsn = "mysql:host={$cfg['host']};charset=utf8mb4";
            $pdoInstance = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Ensure database exists and select it
            $pdoInstance->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdoInstance->exec("USE `" . DB_NAME . "`;");

            $pdo = $pdoInstance;
            return $pdo;
        } catch (PDOException $e) {
            $lastError = $e->getMessage();
            continue;
        }
    }

    // If all connection attempts fail, show a styled helpful error page
    die("<div style='font-family:sans-serif; padding:30px; color:#ef4444; background:#1e293b; border-radius:12px; max-width:600px; margin:40px auto; border:1px solid #334155;'>
        <h2 style='margin-top:0;'>⚠️ Database Connection Error</h2>
        <p style='color:#cbd5e1;'>" . htmlspecialchars($lastError) . "</p>
        <p style='color:#94a3b8; font-size:0.9rem;'>Please verify that the MySQL service is started in your XAMPP Control Panel.</p>
    </div>");
}

// Session initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Authentication Helpers ───────────────────────────────────────────────────

function isLoggedIn()
{
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function currentUser()
{
    return $_SESSION['user'] ?? null;
}

function isAdmin()
{
    return isLoggedIn() && (currentUser()['role'] ?? '') === 'admin';
}

function isTenant()
{
    return isLoggedIn() && (currentUser()['role'] ?? '') === 'tenant';
}

function requireAuth()
{
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Please log in to access this page.";
        header('Location: login.php');
        exit;
    }
}

function requireAdmin()
{
    requireAuth();
    if (!isAdmin()) {
        $_SESSION['flash_error'] = "Access denied! Admin privileges required.";
        header('Location: index.php');
        exit;
    }
}

// Helper: Escape HTML string safely
function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper: Format price in PHP Pesos
function formatMoney($amount)
{
    return '₱' . number_format((float) $amount, 2);
}