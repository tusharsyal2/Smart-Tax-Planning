<?php
/**
 * REST API: Regime Comparison Endpoint
 * POST /api/v1/tax/compare
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../services/TaxComparisonService.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method Not Allowed', null, ['Method must be POST'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$comparisonService = new TaxComparisonService();

try {
    $result = $comparisonService->compare($input);
    jsonResponse(true, 'Regime comparison generated', $result);
} catch (Exception $e) {
    jsonResponse(false, 'Comparison Error', null, [$e->getMessage()], 500);
}
