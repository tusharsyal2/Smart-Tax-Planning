<?php
/**
 * Tax Planning Simulator Service
 * Real-time "What-If" scenario simulation engine for financial modeling.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/TaxComparisonService.php';

class TaxSimulationService {
    private TaxComparisonService $comparisonService;

    public function __construct(?TaxComparisonService $comparisonService = null) {
        $this->comparisonService = $comparisonService ?? new TaxComparisonService();
    }

    /**
     * Run What-If simulation comparing base profile with simulated changes
     *
     * @param array $baseParams Original financial parameters
     * @param array $adjustments Changes to apply
     * @return array Side-by-side comparison of Base vs Scenario
     */
    public function simulate(array $baseParams, array $adjustments): array {
        // Base case calculation
        $baseComparison = $this->comparisonService->compare($baseParams);

        // Build scenario parameters by applying adjustments
        $scenarioParams = $baseParams;

        // Salary adjustment (either delta or absolute)
        if (isset($adjustments['salary_delta'])) {
            $scenarioParams['salary_income'] = max(0.0, ($baseParams['salary_income'] ?? 0.0) + (float)$adjustments['salary_delta']);
        } elseif (isset($adjustments['salary_income'])) {
            $scenarioParams['salary_income'] = max(0.0, (float)$adjustments['salary_income']);
        }

        // Other income adjustment
        if (isset($adjustments['other_income_delta'])) {
            $scenarioParams['other_income'] = max(0.0, ($baseParams['other_income'] ?? 0.0) + (float)$adjustments['other_income_delta']);
        } elseif (isset($adjustments['other_income'])) {
            $scenarioParams['other_income'] = max(0.0, (float)$adjustments['other_income']);
        }

        // Deductions adjustments
        $baseDeductions = $baseParams['deductions'] ?? [];
        $scenarioDeductions = [];
        $seenSections = [];

        foreach ($baseDeductions as $d) {
            $sec = strtoupper($d['section_code'] ?? '');
            $amt = (float)($d['amount_entered'] ?? 0.0);
            if (isset($adjustments['deductions'][$sec])) {
                $amt += (float)$adjustments['deductions'][$sec];
                $amt = max(0.0, $amt);
            }
            $scenarioDeductions[] = ['section_code' => $sec, 'amount_entered' => $amt];
            $seenSections[$sec] = true;
        }

        // If new deductions added in scenario
        if (!empty($adjustments['deductions']) && is_array($adjustments['deductions'])) {
            foreach ($adjustments['deductions'] as $sec => $extraAmt) {
                $sec = strtoupper($sec);
                if (empty($seenSections[$sec])) {
                    $scenarioDeductions[] = ['section_code' => $sec, 'amount_entered' => max(0.0, (float)$extraAmt)];
                }
            }
        }
        $scenarioParams['deductions'] = $scenarioDeductions;

        // Scenario calculation
        $scenarioComparison = $this->comparisonService->compare($scenarioParams);

        // Deltas computation
        $baseOldTax = (float)$baseComparison['old_regime']['total_tax_liability'];
        $scenOldTax = (float)$scenarioComparison['old_regime']['total_tax_liability'];
        $deltaOldTax = $scenOldTax - $baseOldTax;

        $baseNewTax = (float)$baseComparison['new_regime']['total_tax_liability'];
        $scenNewTax = (float)$scenarioComparison['new_regime']['total_tax_liability'];
        $deltaNewTax = $scenNewTax - $baseNewTax;

        $baseTaxableOld = (float)$baseComparison['old_regime']['taxable_income'];
        $scenTaxableOld = (float)$scenarioComparison['old_regime']['taxable_income'];

        return [
            'base' => $baseComparison,
            'scenario' => $scenarioComparison,
            'adjustments_applied' => $adjustments,
            'deltas' => [
                'taxable_income_old_delta' => $scenTaxableOld - $baseTaxableOld,
                'tax_liability_old_delta' => $deltaOldTax,
                'tax_liability_new_delta' => $deltaNewTax,
                'base_optimal_regime' => $baseComparison['recommended_regime'],
                'scenario_optimal_regime' => $scenarioComparison['recommended_regime'],
                'optimal_regime_changed' => ($baseComparison['recommended_regime'] !== $scenarioComparison['recommended_regime'])
            ],
            'simulated_at' => date('Y-m-d H:i:s')
        ];
    }
}
