<?php
/**
 * Role Switcher for Demonstration & Academic Review
 * Allows seamless switching between User view and Administrator view.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/AuditRepository.php';

requireAuth();

$userRepo = new UserRepository();
$auditRepo = new AuditRepository();

$target = $_GET['to'] ?? $_GET['role'] ?? 'admin';

if ($target === 'admin') {
    // Elevate current session or switch to administrator account
    $admin = $userRepo->findByEmail('admin@smarttax.local');
    if ($admin) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_email'] = $admin['email'];
        $_SESSION['user_role'] = 'admin';
        $auditRepo->log($admin['id'], 'ROLE_SWITCH_ADMIN', 'users', $admin['id']);
        setFlash('success', 'Switched to Administrator Mode. Full admin privileges enabled.');
        header('Location: ' . baseUrl('admin/dashboard.php'));
        exit;
    } else {
        // Fallback: update current user role to admin
        $_SESSION['user_role'] = 'admin';
        setFlash('success', 'Your role has been elevated to Administrator.');
        header('Location: ' . baseUrl('admin/dashboard.php'));
        exit;
    }
} else {
    // Switch to Salaried Demo user
    $demo = $userRepo->findByEmail('salaried.demo@smarttax.local');
    if ($demo) {
        $_SESSION['user_id'] = $demo['id'];
        $_SESSION['user_name'] = $demo['name'];
        $_SESSION['user_email'] = $demo['email'];
        $_SESSION['user_role'] = 'user';
        $auditRepo->log($demo['id'], 'ROLE_SWITCH_USER', 'users', $demo['id']);
        setFlash('info', 'Switched to Regular User View (Aarav Sharma - Salaried).');
        header('Location: ' . baseUrl('dashboard.php'));
        exit;
    } else {
        $_SESSION['user_role'] = 'user';
        setFlash('info', 'Switched to Standard User Mode.');
        header('Location: ' . baseUrl('dashboard.php'));
        exit;
    }
}
