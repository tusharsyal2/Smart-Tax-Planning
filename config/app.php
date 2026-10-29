<?php
/**
 * Application Core Configuration and Helper Functions
 * Smart Tax Planning Assistance
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session parameters before starting session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Simple .env parser
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            $val = trim($val, "\"'");
            if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Load .env from project root
$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
loadEnv($envPath);

function env($key, $default = null) {
    $val = getenv($key);
    if ($val === false) {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    if (strtolower($val) === 'true') return true;
    if (strtolower($val) === 'false') return false;
    return $val;
}

// Detect Base URL dynamically so it works in both PHP built-in server (e.g. localhost:8000)
// and XAMPP Apache alias (e.g. localhost/smart-tax-planning)
function baseUrl($path = '') {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Normalize script directory
    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        $scriptDir = '';
    } else {
        // If nested under XAMPP alias (e.g. /smart-tax-planning/admin), keep alias
        $pos = strpos($scriptDir, '/smart-tax-planning');
        if ($pos !== false) {
            $scriptDir = substr($scriptDir, 0, $pos + strlen('/smart-tax-planning'));
        } else {
            // Running at document root (e.g. php -S 127.0.0.1:8000), any subfolder like /admin is within app
            $scriptDir = '';
        }
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = $scheme . '://' . $host . $scriptDir;

    $path = ltrim($path, '/');
    return $path ? rtrim($base, '/') . '/' . $path : rtrim($base, '/');
}

/**
 * Format number into Indian Currency format (e.g., ₹12,50,000 or ₹12,50,000.50)
 */
function formatInr($number, $includeDecimal = false) {
    if (!is_numeric($number)) {
        return '₹0';
    }
    $negative = $number < 0;
    $number = abs($number);

    if ($includeDecimal) {
        $formatted = number_format($number, 2, '.', '');
        list($integerPart, $decimalPart) = explode('.', $formatted);
    } else {
        $integerPart = (string)round($number);
        $decimalPart = '';
    }

    $len = strlen($integerPart);
    if ($len > 3) {
        $lastThree = substr($integerPart, -3);
        $remaining = substr($integerPart, 0, $len - 3);
        $remaining = preg_replace('/(\d)(?=(\d{2})+(?!\d))/', '$1,', $remaining);
        $formattedInteger = $remaining . ',' . $lastThree;
    } else {
        $formattedInteger = $integerPart;
    }

    $result = '₹' . ($negative ? '-' : '') . $formattedInteger;
    if ($includeDecimal && $decimalPart !== '') {
        $result .= '.' . $decimalPart;
    }
    return $result;
}

function formatRupee($number, $includeDecimal = false) {
    return formatInr($number, $includeDecimal);
}

/**
 * CSRF Protection
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    $token = csrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Security: prevent CSV formula injection
 */
function sanitizeForCsv($value) {
    if ($value === null || $value === '') {
        return '';
    }
    $str = (string)$value;
    $firstChar = substr($str, 0, 1);
    if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"])) {
        return "'" . $str;
    }
    return $str;
}

/**
 * Safe HTML escaping
 */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Session Flash Messages
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Authentication Helpers
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user'
    ];
}

function requireAuth() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please sign in to access your dashboard and tax planner.');
        header('Location: ' . baseUrl('login.php'));
        exit;
    }
}

function requireAdmin() {
    requireAuth();
    if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
        http_response_code(403);
        echo "<!DOCTYPE html><html><head><title>Access Denied</title><link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'></head><body class='p-5 text-center'><div class='container card p-5 shadow-sm max-w-md mx-auto'><h3>403 - Forbidden</h3><p class='text-muted'>You do not have administrative privileges to access this area.</p><a href='" . baseUrl('dashboard.php') . "' class='btn btn-primary mt-3'>Return to Dashboard</a></div></body></html>";
        exit;
    }
}

/**
 * Standard JSON response for API endpoints
 */
function jsonResponse($success, $message = '', $data = null, $errors = [], $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => (bool)$success,
        'message' => (string)$message,
        'data' => $data,
        'errors' => $errors,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
