<?php
/**
 * Tax Calculation Engine
 * High-precision, progressive tax engine implementing Indian Income Tax rules.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/TaxRuleService.php';
require_once __DIR__ . '/DeductionEligibilityService.php';

class TaxCalculator {
    private TaxRuleService $ruleService;
    private DeductionEligibilityService $deductionService;

    public function __construct(
        ?TaxRuleService $ruleService = null,
        ?DeductionEligibilityService $deductionService = null
    ) {
        $this->ruleService = $ruleService ?? new TaxRuleService();
        $this->deductionService = $deductionService ?? new DeductionEligibilityService();
    }

    /**
     * Calculate comprehensive tax breakdown
     *
     * @param array $params Contains:
     *   - financial_year: string (e.g. '2024-25')
     *   - regime: string ('old' or 'new')
     *   - age_category: string ('below_60', 'senior_60_80', 'super_senior_80')
     *   - salary_income: float
     *   - house_property_income: float
     *   - business_income: float
     *   - stcg_income: float (Short-term capital gains)
     *   - ltcg_income: float (Long-term capital gains)
     *   - other_income: float
     *   - deductions: array
     *   - tds_paid: float
     *   - advance_tax_paid: float
     * @return array Complete transparent calculation breakdown
     */
    public function calculate(array $params): array {
        $fy = $params['financial_year'] ?? '2024-25';
        $regime = strtolower($params['regime'] ?? 'new');
        $ageCategory = $params['age_category'] ?? 'below_60';

        // Load tax rule set and progressive slabs
        $ruleSet = $this->ruleService->getRuleSet($fy, $regime, 'individual');
        if (!$ruleSet) {
            throw new InvalidArgumentException("No tax rule set found for Financial Year '{$fy}' under '{$regime}' regime.");
        }

        // 1. Gross Income Components
        $salary = max(0.0, (float)($params['salary_income'] ?? 0.0));
        $houseProperty = (float)($params['house_property_income'] ?? 0.0);
        $business = max(0.0, (float)($params['business_income'] ?? 0.0));
        $stcg = max(0.0, (float)($params['stcg_income'] ?? 0.0));
        $ltcg = max(0.0, (float)($params['ltcg_income'] ?? 0.0));
        $otherIncome = max(0.0, (float)($params['other_income'] ?? 0.0));

        // Total Gross Income
        $grossIncome = $salary + $houseProperty + $business + $stcg + $ltcg + $otherIncome;

        // 2. Standard Deduction on Salary
        $stdDeductionConfig = (float)($ruleSet['standard_deduction'] ?? 50000.0);
        // Standard deduction is limited to actual salary income
        $standardDeduction = ($salary > 0) ? min($salary, $stdDeductionConfig) : 0.0;
        $netSalary = max(0.0, $salary - $standardDeduction);

        // 3. Gross Total Income (GTI)
        $gti = $netSalary + $houseProperty + $business + $stcg + $ltcg + $otherIncome;
        $gti = max(0.0, $gti);

        // 4. Chapter VI-A Deductions
        $rawDeductions = $params['deductions'] ?? [];
        $deductionEval = $this->deductionService->evaluateDeductions($rawDeductions, $regime, $ageCategory);
        $totalEligibleDeductions = ($regime === 'new') 
            ? $deductionEval['total_eligible_new'] 
            : $deductionEval['total_eligible_old'];

        // Deductions cannot exceed GTI (excluding special rate capital gains)
        $applicableDeductions = min($gti, $totalEligibleDeductions);

        // 5. Net Taxable Income (Rounded to nearest 10 per Section 288A)
        $taxableIncomeUnrounded = max(0.0, $gti - $applicableDeductions);
        $taxableIncome = round($taxableIncomeUnrounded / 10) * 10;

        // 6. Progressive Slab Tax Calculation
        $slabBreakdown = [];
        $slabTax = 0.0;
        $slabs = $ruleSet['slabs'] ?? [];

        // Adjust lower slab exemption if Senior/Super Senior under Old Regime
        if ($regime === 'old') {
            if ($ageCategory === 'senior_60_80') {
                // Senior Citizens: Nil up to Rs 3 Lakhs, 5% between 3L and 5L
                $slabs = [
                    ['slab_order' => 1, 'lower_bound' => 0.0, 'upper_bound' => 300000.0, 'rate' => 0.0, 'conditions' => 'Up to Rs 3 Lakhs: Nil'],
                    ['slab_order' => 2, 'lower_bound' => 300000.0, 'upper_bound' => 500000.0, 'rate' => 0.05, 'conditions' => 'Rs 3 Lakhs to Rs 5 Lakhs: 5%'],
                    ['slab_order' => 3, 'lower_bound' => 500000.0, 'upper_bound' => 1000000.0, 'rate' => 0.20, 'conditions' => 'Rs 5 Lakhs to Rs 10 Lakhs: 20%'],
                    ['slab_order' => 4, 'lower_bound' => 1000000.0, 'upper_bound' => null, 'rate' => 0.30, 'conditions' => 'Above Rs 10 Lakhs: 30%']
                ];
            } elseif ($ageCategory === 'super_senior_80') {
                // Super Senior: Nil up to Rs 5 Lakhs, 20% between 5L and 10L
                $slabs = [
                    ['slab_order' => 1, 'lower_bound' => 0.0, 'upper_bound' => 500000.0, 'rate' => 0.0, 'conditions' => 'Up to Rs 5 Lakhs: Nil'],
                    ['slab_order' => 2, 'lower_bound' => 500000.0, 'upper_bound' => 1000000.0, 'rate' => 0.20, 'conditions' => 'Rs 5 Lakhs to Rs 10 Lakhs: 20%'],
                    ['slab_order' => 3, 'lower_bound' => 1000000.0, 'upper_bound' => null, 'rate' => 0.30, 'conditions' => 'Above Rs 10 Lakhs: 30%']
                ];
            }
        }

        foreach ($slabs as $slab) {
            $lower = (float)$slab['lower_bound'];
            $upper = $slab['upper_bound'] !== null ? (float)$slab['upper_bound'] : null;
            $rate = (float)$slab['rate'];

            if ($taxableIncome > $lower) {
                $taxableInSlab = ($upper !== null) 
                    ? min($taxableIncome, $upper) - $lower 
                    : $taxableIncome - $lower;

                $taxForSlab = $taxableInSlab * $rate;
                $slabTax += $taxForSlab;

                $slabBreakdown[] = [
                    'order' => $slab['slab_order'] ?? 1,
                    'range' => ($upper !== null) 
                        ? formatInr($lower) . ' to ' . formatInr($upper) 
                        : 'Above ' . formatInr($lower),
                    'rate' => ($rate * 100) . '%',
                    'taxable_amount' => $taxableInSlab,
                    'tax' => $taxForSlab
                ];
            } else {
                $slabBreakdown[] = [
                    'order' => $slab['slab_order'] ?? 1,
                    'range' => ($upper !== null) 
                        ? formatInr($lower) . ' to ' . formatInr($upper) 
                        : 'Above ' . formatInr($lower),
                    'rate' => ($rate * 100) . '%',
                    'taxable_amount' => 0.0,
                    'tax' => 0.0
                ];
            }
        }

        // 7. Section 87A Tax Rebate
        $rebateLimit = (float)$ruleSet['rebate_87a_limit'];
        $rebateMax = (float)$ruleSet['rebate_87a_max'];
        $rebateApplied = 0.0;
        $marginalRelief87A = 0.0;

        if ($taxableIncome <= $rebateLimit) {
            $rebateApplied = min($slabTax, $rebateMax);
        } elseif ($regime === 'new' && $taxableIncome > $rebateLimit) {
            // Marginal Relief for Section 87A in New Regime (Finance Act 2023/2024 proviso):
            // When taxable income slightly exceeds 7L (e.g. 7.15L), tax cannot exceed income over 7L!
            $excessIncomeOver7L = $taxableIncome - $rebateLimit;
            if ($slabTax > $excessIncomeOver7L) {
                $marginalRelief87A = $slabTax - $excessIncomeOver7L;
                $rebateApplied = $marginalRelief87A;
            }
        }

        $taxAfterRebate = max(0.0, $slabTax - $rebateApplied);

        // 8. Surcharge & Marginal Relief
        $surcharge = 0.0;
        $surchargeRate = 0.0;

        if ($taxableIncome > 5000000.0) {
            if ($taxableIncome <= 10000000.0) {
                $surchargeRate = 0.10;
                $surcharge = $taxAfterRebate * $surchargeRate;
                // Marginal relief at 50 Lakhs
                $taxAt50L = $this->calculateTaxOnAmount(5000000.0, $slabs);
                $maxTaxWithSurcharge = $taxAt50L + ($taxableIncome - 5000000.0);
                if (($taxAfterRebate + $surcharge) > $maxTaxWithSurcharge) {
                    $surcharge = max(0.0, $maxTaxWithSurcharge - $taxAfterRebate);
                }
            } elseif ($taxableIncome <= 20000000.0) {
                $surchargeRate = 0.15;
                $surcharge = $taxAfterRebate * $surchargeRate;
            } elseif ($taxableIncome <= 50000000.0) {
                $surchargeRate = 0.25;
                $surcharge = $taxAfterRebate * $surchargeRate;
            } else {
                // Above 5 Cr: 37% in Old Regime, capped at 25% in New Regime
                $surchargeRate = ($regime === 'new') ? 0.25 : 0.37;
                $surcharge = $taxAfterRebate * $surchargeRate;
            }
        }

        // 9. Health & Education Cess (4%)
        $cessRate = (float)($ruleSet['cess_rate'] ?? 0.04);
        $cess = ($taxAfterRebate + $surcharge) * $cessRate;

        // 10. Total Tax Liability (Rounded to nearest 10 per Section 288B)
        $totalTaxLiabilityUnrounded = $taxAfterRebate + $surcharge + $cess;
        $totalTaxLiability = round($totalTaxLiabilityUnrounded / 10) * 10;

        // 11. Advance Tax & TDS Credit
        $tdsPaid = max(0.0, (float)($params['tds_paid'] ?? 0.0));
        $advanceTaxPaid = max(0.0, (float)($params['advance_tax_paid'] ?? 0.0));
        $totalTaxesPaid = $tdsPaid + $advanceTaxPaid;

        // 12. Net Payable or Refund
        $balance = $totalTaxLiability - $totalTaxesPaid;
        $remainingTaxPayable = ($balance > 0) ? $balance : 0.0;
        $potentialRefund = ($balance < 0) ? abs($balance) : 0.0;

        // 13. Effective Tax Rate
        $effectiveTaxRate = ($grossIncome > 0) 
            ? round(($totalTaxLiability / $grossIncome) * 100, 2) 
            : 0.0;

        return [
            'financial_year' => $fy,
            'regime' => $regime,
            'age_category' => $ageCategory,
            'rule_version' => $ruleSet['rule_version'] ?? '1.0',
            'gross_income' => $grossIncome,
            'salary_income' => $salary,
            'standard_deduction' => $standardDeduction,
            'net_salary' => $netSalary,
            'house_property_income' => $houseProperty,
            'business_income' => $business,
            'stcg_income' => $stcg,
            'ltcg_income' => $ltcg,
            'other_income' => $otherIncome,
            'gross_total_income' => $gti,
            'total_eligible_deductions' => $totalEligibleDeductions,
            'applicable_deductions' => $applicableDeductions,
            'deduction_details' => $deductionEval['items'],
            'taxable_income' => $taxableIncome,
            'slab_tax' => $slabTax,
            'slabs_breakdown' => $slabBreakdown,
            'rebate_87a' => $rebateApplied,
            'marginal_relief_87a' => $marginalRelief87A,
            'tax_after_rebate' => $taxAfterRebate,
            'surcharge' => $surcharge,
            'surcharge_rate' => $surchargeRate,
            'cess' => $cess,
            'cess_rate' => $cessRate,
            'total_tax_liability' => $totalTaxLiability,
            'tds_paid' => $tdsPaid,
            'advance_tax_paid' => $advanceTaxPaid,
            'total_taxes_paid' => $totalTaxesPaid,
            'remaining_tax_payable' => $remainingTaxPayable,
            'potential_refund' => $potentialRefund,
            'effective_tax_rate' => $effectiveTaxRate,
            'calculated_at' => date('Y-m-d H:i:s'),
            'rule_source' => $ruleSet['source_reference'] ?? 'Income Tax Act 1961'
        ];
    }

    /**
     * Helper to compute slab tax on a specific income
     */
    private function calculateTaxOnAmount(float $income, array $slabs): float {
        $tax = 0.0;
        foreach ($slabs as $slab) {
            $lower = (float)$slab['lower_bound'];
            $upper = $slab['upper_bound'] !== null ? (float)$slab['upper_bound'] : null;
            $rate = (float)$slab['rate'];
            if ($income > $lower) {
                $portion = ($upper !== null) ? min($income, $upper) - $lower : $income - $lower;
                $tax += ($portion * $rate);
            }
        }
        return $tax;
    }
}
