<?php
/**
 * REST API: User Login
 * POST /api/v1/auth/login
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../repositories/UserRepository.php';
require_once __DIR__ . '/../../../repositories/AuditRepository.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method Not Allowed', null, ['Method must be POST'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = strtolower(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(false, 'Validation Failed', null, ['Email and password are required.'], 422);
}

$userRepo = new UserRepository();
$auditRepo = new AuditRepository();

$user = $userRepo->findByEmail($email);
if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(false, 'Unauthorized', null, ['Invalid email or password.'], 401);
}

if ($user['status'] !== 'active') {
    jsonResponse(false, 'Forbidden', null, ['Account is suspended or inactive.'], 403);
}

// Session regeneration & login
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['active_fy'] = '2024-25';

$userRepo->updateLastLogin($user['id']);
$auditRepo->log($user['id'], 'API_LOGIN', 'users', $user['id']);

jsonResponse(true, 'Login successful', [
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ]
]);
