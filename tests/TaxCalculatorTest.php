<?php
/**
 * Automated Test Suite: Tax Calculation Engine & Business Rules
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../services/TaxCalculator.php';
require_once __DIR__ . '/../services/TaxComparisonService.php';
require_once __DIR__ . '/../services/DeductionEligibilityService.php';
require_once __DIR__ . '/../services/TaxSimulationService.php';

class TaxCalculatorTest {
    private TaxCalculator $calculator;
    private TaxComparisonService $comparison;
    private DeductionEligibilityService $deductions;
    private TaxSimulationService $simulator;
    private int $assertions = 0;
    private int $failures = 0;

    public function __construct() {
        $this->calculator = new TaxCalculator();
        $this->comparison = new TaxComparisonService($this->calculator);
        $this->deductions = new DeductionEligibilityService();
        $this->simulator = new TaxSimulationService($this->comparison);
    }

    public function runAll(): void {
        echo "\n=======================================================\n";
        echo " Running Smart Tax Planning Automated Test Suite\n";
        echo "=======================================================\n\n";

        $this->testZeroIncomeCase();
        $this->testNewRegime87ARebateThreshold();
        $this->testOldRegime87ARebateThreshold();
        $this->testFY2024_25RevisedStandardDeduction();
        $this->testFY2023_24StandardDeduction();
        $this->testSection80CCeilingEnforcement();
        $this->testSection80CCD1BCeilingEnforcement();
        $this->testEmployerNPSAllowedInBothRegimes();
        $this->testOldVsNewRegimeComparisonCalculation();
        $this->testBreakevenDeductionCalculation();
        $this->testSimulationWhatIfDeltas();
        $this->testHealthAndEducationCessFourPercent();
        $this->testCsvFormulaInjectionSanitization();
        $this->testPasswordHashAndVerify();

        echo "\n-------------------------------------------------------\n";
        echo " Test Summary: {$this->assertions} assertions evaluated.\n";
        if ($this->failures === 0) {
            echo " [ALL TESTS PASSED SUCCESSFULLY]\n";
        } else {
            echo " [FAILURES DETECTED: {$this->failures} failed]\n";
        }
        echo "-------------------------------------------------------\n\n";
    }

    private function assert($condition, string $message): void {
        $this->assertions++;
        if ($condition) {
            echo "  [PASS] {$message}\n";
        } else {
            $this->failures++;
            echo "  [FAIL] {$message}\n";
        }
    }

    public function testZeroIncomeCase(): void {
        $res = $this->calculator->calculate([
            'financial_year' => '2024-25',
            'regime' => 'new',
            'salary_income' => 0.0,
            'other_income' => 0.0
        ]);
        $this->assert($res['total_tax_liability'] === 0.0, "Zero income yields exactly ₹0 tax liability");
        $this->assert($res['taxable_income'] === 0.0, "Zero income yields ₹0 taxable income");
    }

    public function testNewRegime87ARebateThreshold(): void {
        // Under FY 2024-25 New Regime: Standard deduction is ₹75,000.
        // Gross salary of ₹7,75,000 results in Net Taxable Income = ₹7,00,000.
        // Slab tax on 7L is (0 to 3L nil) + (3L to 7L @ 5% = ₹20,000).
        // Section 87A rebate covers up to ₹25,000 -> Tax liability is ₹0!
        $res = $this->calculator->calculate([
            'financial_year' => '2024-25',
            'regime' => 'new',
            'salary_income' => 775000.0
        ]);

        $this->assert($res['taxable_income'] === 700000.0, "Gross 7.75L with 75k std deduction produces 7.0L taxable income");
        $this->assert($res['rebate_87a'] === 20000.0, "Section 87A rebate of ₹20,000 is applied in full");
        $this->assert($res['total_tax_liability'] === 0.0, "Total tax liability under New Regime up to ₹7.75L salary is ₹0");
    }

    public function testOldRegime87ARebateThreshold(): void {
        // Under Old Regime: Standard deduction is ₹50,000.
        // Gross salary of ₹5,50,000 -> Net Taxable Income = ₹5,00,000.
        // Slab tax is (2.5L to 5L @ 5% = ₹12,500).
        // Section 87A rebate covers ₹12,500 -> Tax is ₹0!
        $res = $this->calculator->calculate([
            'financial_year' => '2024-25',
            'regime' => 'old',
            'salary_income' => 550000.0
        ]);

        $this->assert($res['taxable_income'] === 500000.0, "Old regime 5.5L salary results in 5.0L taxable income");
        $this->assert($res['rebate_87a'] === 12500.0, "Section 87A rebate of ₹12,500 is applied under Old Regime");
        $this->assert($res['total_tax_liability'] === 0.0, "Total tax under Old Regime up to ₹5.5L salary is ₹0");
    }

    public function testFY2024_25RevisedStandardDeduction(): void {
        // Finance (No. 2) Act 2024 increased New Regime standard deduction to ₹75,000
        $res = $this->calculator->calculate([
            'financial_year' => '2024-25',
            'regime' => 'new',
            'salary_income' => 1000000.0
        ]);
        $this->assert($res['standard_deduction'] === 75000.0, "FY 2024-25 New Regime applies revised ₹75,000 standard deduction");
    }

    public function testFY2023_24StandardDeduction(): void {
        // FY 2023-24 New Regime standard deduction was ₹50,000
        $res = $this->calculator->calculate([
            'financial_year' => '2023-24',
            'regime' => 'new',
            'salary_income' => 1000000.0
        ]);
        $this->assert($res['standard_deduction'] === 50000.0, "FY 2023-24 New Regime applies ₹50,000 standard deduction");
    }

    public function testSection80CCeilingEnforcement(): void {
        $eval = $this->deductions->evaluateDeductions([
            ['section_code' => '80C', 'amount_entered' => 220000.0]
        ], 'old');

        $this->assert($eval['items']['80C']['eligible_old'] === 150000.0, "Section 80C entered ₹2,20,000 is correctly capped at statutory ₹1,50,000 ceiling");
        $this->assert($eval['items']['80C']['eligible_new'] === 0.0, "Section 80C is disallowed in New Regime");
    }

    public function testSection80CCD1BCeilingEnforcement(): void {
        $eval = $this->deductions->evaluateDeductions([
            ['section_code' => '80CCD_1B', 'amount_entered' => 85000.0]
        ], 'old');

        $this->assert($eval['items']['80CCD_1B']['eligible_old'] === 50000.0, "Section 80CCD(1B) NPS entered ₹85,000 is correctly capped at ₹50,000");
    }

    public function testEmployerNPSAllowedInBothRegimes(): void {
        $eval = $this->deductions->evaluateDeductions([
            ['section_code' => '80CCD_2', 'amount_entered' => 60000.0]
        ], 'new');

        $this->assert($eval['items']['80CCD_2']['eligible_new'] === 60000.0, "Section 80CCD(2) employer NPS is permitted under New Regime");
    }

    public function testOldVsNewRegimeComparisonCalculation(): void {
        $comp = $this->comparison->compare([
            'financial_year' => '2024-25',
            'salary_income' => 1200000.0,
            'deductions' => [
                ['section_code' => '80C', 'amount_entered' => 150000.0],
                ['section_code' => '80CCD_1B', 'amount_entered' => 50000.0],
                ['section_code' => '80D_SELF', 'amount_entered' => 25000.0]
            ]
        ]);

        $this->assert(in_array($comp['recommended_regime'], ['new', 'old', 'equal']), "Comparison identifies optimal regime validly");
        $this->assert($comp['savings_amount'] >= 0.0, "Savings amount is non-negative");
        $this->assert(strlen($comp['explanation']) > 20, "Provides detailed narrative explanation");
    }

    public function testBreakevenDeductionCalculation(): void {
        $comp = $this->comparison->compare([
            'financial_year' => '2024-25',
            'salary_income' => 1200000.0
        ]);
        $this->assert($comp['breakeven_deduction'] > 0.0, "Breakeven deduction for 12L salary is calculated positively");
    }

    public function testSimulationWhatIfDeltas(): void {
        $sim = $this->simulator->simulate([
            'financial_year' => '2024-25',
            'salary_income' => 1000000.0
        ], [
            'salary_delta' => 200000.0
        ]);

        $this->assert($sim['scenario']['old_regime']['salary_income'] === 1200000.0, "Simulator increments base salary by ₹2,00,000 to ₹12,00,000");
        $this->assert(isset($sim['deltas']['tax_liability_new_delta']), "Simulator outputs new regime delta tax");
    }

    public function testHealthAndEducationCessFourPercent(): void {
        // At 15L taxable income under New Regime FY 24-25:
        // Slabs: 0-3L (0), 3-7L (20k), 7-10L (30k), 10-12L (30k), 12-15L (60k) -> Sum = ₹1,40,000.
        // Cess @ 4% = ₹5,600.
        $res = $this->calculator->calculate([
            'financial_year' => '2024-25',
            'regime' => 'new',
            'salary_income' => 1575000.0 // 15.75L - 75k std ded = 15L taxable
        ]);

        $this->assert($res['cess'] === 5600.0, "Health & Education cess is exactly 4% (₹5,600 on ₹1,40,000 slab tax)");
    }

    public function testCsvFormulaInjectionSanitization(): void {
        $dangerousPayload = "=SUM(A1:A10)";
        $sanitized = sanitizeForCsv($dangerousPayload);
        $this->assert(str_starts_with($sanitized, "'"), "Dangerous spreadsheet prefix '=' is neutralized with single quote prefix");

        $safeText = "Regular Salary";
        $this->assert(sanitizeForCsv($safeText) === "Regular Salary", "Normal text is left unaltered in CSV sanitizer");
    }

    public function testPasswordHashAndVerify(): void {
        $plain = "SecureTestPassword@123";
        $hash = password_hash($plain, PASSWORD_BCRYPT);
        $this->assert(password_verify($plain, $hash), "Password bcrypt hashing and verification operate correctly");
        $this->assert(!password_verify("WrongPassword", $hash), "Incorrect password verification fails securely");
    }
}
