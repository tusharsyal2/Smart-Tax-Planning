<?php
/**
 * REST API: Tax Calculation Endpoint
 * POST /api/v1/tax/calculate
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../services/TaxCalculator.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method Not Allowed', null, ['Method must be POST'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$calculator = new TaxCalculator();

try {
    $result = $calculator->calculate($input);
    jsonResponse(true, 'Tax calculation completed', $result);
} catch (InvalidArgumentException $e) {
    jsonResponse(false, 'Invalid Parameters', null, [$e->getMessage()], 422);
} catch (Exception $e) {
    jsonResponse(false, 'Computation Error', null, [$e->getMessage()], 500);
}
