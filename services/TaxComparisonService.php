<?php
/**
 * Tax Regime Comparison Service
 * Compares Old vs New Tax Regimes side-by-side and calculates breakeven deduction points.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/TaxCalculator.php';

class TaxComparisonService {
    private TaxCalculator $calculator;

    public function __construct(?TaxCalculator $calculator = null) {
        $this->calculator = $calculator ?? new TaxCalculator();
    }

    /**
     * Compare Old vs New Tax Regime with identical financial inputs
     */
    public function compare(array $params): array {
        // Run Old Regime calculation
        $oldParams = array_merge($params, ['regime' => 'old']);
        $oldResult = $this->calculator->calculate($oldParams);

        // Run New Regime calculation
        $newParams = array_merge($params, ['regime' => 'new']);
        $newResult = $this->calculator->calculate($newParams);

        $oldTax = (float)$oldResult['total_tax_liability'];
        $newTax = (float)$newResult['total_tax_liability'];

        $difference = round(abs($oldTax - $newTax), 2);

        if ($newTax < $oldTax) {
            $recommended = 'new';
            $savings = $oldTax - $newTax;
            $explanation = "Based on your entered income and deductions, the <strong>New Tax Regime</strong> produces a lower estimated tax liability by <strong>" . formatInr($savings) . "</strong>. Under the revised New Regime, lower tax slabs and higher standard deduction make it more beneficial for your current deduction profile.";
        } elseif ($oldTax < $newTax) {
            $recommended = 'old';
            $savings = $newTax - $oldTax;
            $explanation = "Based on your entered investments and deductions, the <strong>Old Tax Regime</strong> produces a lower estimated tax liability by <strong>" . formatInr($savings) . "</strong>. Your significant Chapter VI-A deductions (e.g. 80C, 80D, home loan interest) sufficiently reduce your taxable income to beat the New Regime rates.";
        } else {
            $recommended = 'equal';
            $savings = 0.0;
            $explanation = "Both tax regimes yield the exact same estimated tax liability of <strong>" . formatInr($oldTax) . "</strong> based on the data provided.";
        }

        // Calculate Breakeven Deduction Point for Old Regime to match New Regime
        $breakevenDeduction = $this->calculateBreakevenDeductions($params, $newTax);

        return [
            'financial_year' => $params['financial_year'] ?? '2024-25',
            'recommended_regime' => $recommended,
            'savings_amount' => $savings,
            'tax_difference' => $difference,
            'explanation' => $explanation,
            'breakeven_deduction' => $breakevenDeduction,
            'old_regime' => $oldResult,
            'new_regime' => $newResult,
            'compared_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Compute approximate total deductions required under Old Regime to match New Regime tax
     */
    private function calculateBreakevenDeductions(array $baseParams, float $targetNewTax): float {
        // If new tax is already 0 (e.g. income under 7.75L with standard deduction),
        // old regime needs taxable income <= 5L (which gives 0 tax with 87A rebate)
        $grossIncome = (float)($baseParams['salary_income'] ?? 0.0) 
                     + (float)($baseParams['other_income'] ?? 0.0)
                     + (float)($baseParams['business_income'] ?? 0.0);

        if ($grossIncome <= 500000.0) {
            return 0.0;
        }

        // Standard deduction in old regime is Rs 50,000
        $stdOld = min(50000.0, (float)($baseParams['salary_income'] ?? 0.0));

        // Binary search to find required Chapter VI-A deduction
        $low = 0.0;
        $high = $grossIncome;
        $bestDeduction = $high;

        for ($i = 0; $i < 30; $i++) {
            $mid = ($low + $high) / 2;
            $testParams = array_merge($baseParams, [
                'regime' => 'old',
                'deductions' => [
                    ['section_code' => '80C', 'amount_entered' => min(150000.0, $mid)],
                    ['section_code' => '80CCD_1B', 'amount_entered' => min(50000.0, max(0.0, $mid - 150000.0))],
                    ['section_code' => '24B', 'amount_entered' => min(200000.0, max(0.0, $mid - 200000.0))],
                    ['section_code' => '80D_SELF', 'amount_entered' => max(0.0, $mid - 400000.0)]
                ]
            ]);

            try {
                $res = $this->calculator->calculate($testParams);
                $oldTaxMid = (float)$res['total_tax_liability'];
                if ($oldTaxMid <= $targetNewTax) {
                    $bestDeduction = $mid;
                    $high = $mid; // Try to find lower deduction that still matches
                } else {
                    $low = $mid;
                }
            } catch (Exception $e) {
                break;
            }
        }

        return round($bestDeduction, 0);
    }
}
