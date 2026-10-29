<?php
/**
 * Test Runner CLI
 * Executes automated unit and business rule tests.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/TaxCalculatorTest.php';

$suite = new TaxCalculatorTest();
$suite->runAll();
