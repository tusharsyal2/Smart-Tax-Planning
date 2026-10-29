<?php
/**
 * REST API: Current User Context
 * GET /api/v1/auth/me
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../repositories/UserRepository.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isLoggedIn()) {
    jsonResponse(false, 'Unauthenticated', null, ['Please log in to proceed.'], 401);
}

$userRepo = new UserRepository();
$user = $userRepo->findById($_SESSION['user_id']);

if (!$user) {
    jsonResponse(false, 'User Not Found', null, [], 404);
}

unset($user['password_hash']);
jsonResponse(true, 'Current user profile retrieved', ['user' => $user]);
