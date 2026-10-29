<?php
/**
 * REST API: User Registration
 * POST /api/v1/auth/register
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
$name = trim($input['name'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';

$errors = [];
if (empty($name)) $errors[] = 'Full name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long.';

if (!empty($errors)) {
    jsonResponse(false, 'Validation Failed', null, $errors, 422);
}

$userRepo = new UserRepository();
$auditRepo = new AuditRepository();

if ($userRepo->findByEmail($email)) {
    jsonResponse(false, 'Conflict', null, ['An account with this email already exists.'], 409);
}

try {
    $userId = $userRepo->create([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'employment_category' => $input['employment_category'] ?? 'salaried',
        'age_category' => $input['age_category'] ?? 'below_60',
        'state' => $input['state'] ?? 'Delhi',
        'financial_year' => $input['financial_year'] ?? '2024-25',
        'default_regime' => 'new'
    ]);

    $auditRepo->log($userId, 'API_REGISTER', 'users', $userId);

    jsonResponse(true, 'User registered successfully', ['user_id' => $userId], [], 201);
} catch (Exception $e) {
    jsonResponse(false, 'Registration Failed', null, [$e->getMessage()], 500);
}
