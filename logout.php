<?php
/**
 * Logout Handler
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/repositories/AuditRepository.php';

if (isLoggedIn()) {
    $audit = new AuditRepository();
    $audit->log($_SESSION['user_id'], 'USER_LOGOUT', 'users', $_SESSION['user_id']);
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header('Location: ' . baseUrl('login.php?logged_out=1'));
exit;
