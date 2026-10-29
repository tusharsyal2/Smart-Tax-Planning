<?php
/**
 * REST API: Income Records Management
 * GET/POST/DELETE /api/v1/income/index.php
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../repositories/IncomeRepository.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isLoggedIn()) {
    jsonResponse(false, 'Unauthenticated', null, ['Authentication required.'], 401);
}

$userId = $_SESSION['user_id'];
$repo = new IncomeRepository();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $fy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';
    $records = $repo->getByUserAndFy($userId, $fy);
    $totals = $repo->getTotals($userId, $fy);
    jsonResponse(true, 'Income records retrieved', ['records' => $records, 'totals' => $totals]);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $gross = max(0.0, (float)($input['gross_amount'] ?? 0.0));
    $cat = $input['category'] ?? 'salary';

    if ($gross <= 0) {
        jsonResponse(false, 'Validation Error', null, ['Gross amount must be positive.'], 422);
    }

    $id = $repo->create($userId, [
        'financial_year' => $input['financial_year'] ?? '2024-25',
        'category' => $cat,
        'sub_category' => $input['sub_category'] ?? null,
        'gross_amount' => $gross,
        'adjustments' => (float)($input['adjustments'] ?? 0.0),
        'tds_deducted' => (float)($input['tds_deducted'] ?? 0.0),
        'advance_tax_paid' => (float)($input['advance_tax_paid'] ?? 0.0),
        'description' => $input['description'] ?? '',
        'income_date' => $input['income_date'] ?? date('Y-m-d')
    ]);

    jsonResponse(true, 'Income record created', ['id' => $id], [], 201);
} elseif ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Validation Error', null, ['Record ID required.'], 422);
    }
    $repo->delete($id, $userId);
    jsonResponse(true, 'Income record removed');
} else {
    jsonResponse(false, 'Method Not Allowed', null, [], 405);
}
