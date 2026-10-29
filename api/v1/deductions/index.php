<?php
/**
 * REST API: Deductions Management
 * GET/POST/DELETE /api/v1/deductions/index.php
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../repositories/DeductionRepository.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isLoggedIn()) {
    jsonResponse(false, 'Unauthenticated', null, ['Authentication required.'], 401);
}

$userId = $_SESSION['user_id'];
$repo = new DeductionRepository();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $fy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';
    $records = $repo->getByUserAndFy($userId, $fy);
    jsonResponse(true, 'Deduction records retrieved', ['records' => $records]);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $amount = max(0.0, (float)($input['amount_entered'] ?? 0.0));
    $sec = strtoupper(trim($input['section_code'] ?? '80C'));

    if ($amount <= 0) {
        jsonResponse(false, 'Validation Error', null, ['Amount must be greater than zero.'], 422);
    }

    $id = $repo->create($userId, [
        'financial_year' => $input['financial_year'] ?? '2024-25',
        'section_code' => $sec,
        'category' => $input['category'] ?? $sec,
        'amount_entered' => $amount,
        'verification_status' => $input['verification_status'] ?? 'pending_verification',
        'notes' => $input['notes'] ?? null
    ]);

    jsonResponse(true, 'Deduction record created', ['id' => $id], [], 201);
} elseif ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Validation Error', null, ['Record ID required.'], 422);
    }
    $repo->delete($id, $userId);
    jsonResponse(true, 'Deduction record removed');
} else {
    jsonResponse(false, 'Method Not Allowed', null, [], 405);
}
