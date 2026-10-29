<?php
/**
 * Tax-Saving Recommendation Engine
 * Identifies personalized tax-planning avenues based on actual marginal tax rates and statutory limits.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/TaxCalculator.php';

class TaxRecommendationService {
    private TaxCalculator $calculator;

    public function __construct(?TaxCalculator $calculator = null) {
        $this->calculator = $calculator ?? new TaxCalculator();
    }

    /**
     * Generate personalized tax recommendations
     */
    public function generateRecommendations(array $params): array {
        $regime = strtolower($params['regime'] ?? 'new');
        $ageCategory = $params['age_category'] ?? 'below_60';
        $isSenior = ($ageCategory === 'senior_60_80' || $ageCategory === 'super_senior_80');

        // Current calculation baseline
        $baseCalc = $this->calculator->calculate($params);
        $taxableIncome = (float)$baseCalc['taxable_income'];
        $grossIncome = (float)$baseCalc['gross_income'];

        // Determine marginal tax rate (applicable slab rate + 4% cess)
        $marginalRate = 0.0;
        if ($regime === 'old') {
            if ($taxableIncome > 1000000.0) {
                $marginalRate = 0.30;
            } elseif ($taxableIncome > 500000.0) {
                $marginalRate = 0.20;
            } elseif ($taxableIncome > 250000.0 && $taxableIncome > 500000.0) {
                $marginalRate = 0.05;
            }
        } else {
            // New Regime FY 24-25
            if ($taxableIncome > 1500000.0) {
                $marginalRate = 0.30;
            } elseif ($taxableIncome > 1200000.0) {
                $marginalRate = 0.20;
            } elseif ($taxableIncome > 1000000.0) {
                $marginalRate = 0.15;
            } elseif ($taxableIncome > 700000.0) {
                $marginalRate = 0.10;
            }
        }
        $effectiveMultiplier = $marginalRate * 1.04; // Slabs + 4% Health & Education cess

        // Existing deductions entered
        $rawDeductions = $params['deductions'] ?? [];
        $dedMap = [];
        foreach ($rawDeductions as $d) {
            $code = strtoupper(trim($d['section_code'] ?? ''));
            $amt = (float)($d['amount_entered'] ?? 0.0);
            $dedMap[$code] = ($dedMap[$code] ?? 0.0) + $amt;
        }

        $recommendations = [];

        // 1. Section 80C Opportunity
        $curr80C = $dedMap['80C'] ?? 0.0;
        $gap80C = max(0.0, 150000.0 - $curr80C);
        $taxImpact80C = round($gap80C * $effectiveMultiplier, 2);

        $recommendations[] = [
            'id' => 'rec_80c',
            'title' => 'Maximize Section 80C Tax Savings',
            'section' => 'Section 80C',
            'applicable_regime' => 'Old Tax Regime only',
            'is_applicable_in_current_regime' => ($regime === 'old'),
            'current_amount' => $curr80C,
            'statutory_limit' => 150000.0,
            'additional_eligible_gap' => $gap80C,
            'estimated_tax_savings' => ($regime === 'old') ? $taxImpact80C : 0.0,
            'options' => [
                ['name' => 'PPF (Public Provident Fund)', 'lock_in' => '15 Years', 'risk' => 'Zero (Sovereign guarantee)', 'returns' => '~7.1% p.a. (Tax-free under EEE)'],
                ['name' => 'ELSS Mutual Funds', 'lock_in' => '3 Years (Shortest)', 'risk' => 'Market-linked / Equity', 'returns' => 'Potential wealth creation (LTCG rules apply)'],
                ['name' => 'EPF (Voluntary PF)', 'lock_in' => 'Retirement', 'risk' => 'Very Low (Govt backed)', 'returns' => '~8.25% p.a.'],
                ['name' => 'Tax-Saving Fixed Deposits', 'lock_in' => '5 Years', 'risk' => 'Very Low (DICGC insured)', 'returns' => 'Fixed 6.5% - 7.5% p.a.']
            ],
            'caution' => 'Investments under Section 80C carry mandatory lock-in periods. Do not invest emergency funds solely for tax savings.',
            'verification_required' => 'Eligibility requires submission of investment deposit receipts and Form 16 reconciliation.'
        ];

        // 2. Section 80CCD(1B) - Additional NPS
        $currNps = $dedMap['80CCD_1B'] ?? 0.0;
        $gapNps = max(0.0, 50000.0 - $currNps);
        $taxImpactNps = round($gapNps * $effectiveMultiplier, 2);

        $recommendations[] = [
            'id' => 'rec_80ccd1b',
            'title' => 'Additional NPS Contribution under Section 80CCD(1B)',
            'section' => 'Section 80CCD(1B)',
            'applicable_regime' => 'Old Tax Regime only',
            'is_applicable_in_current_regime' => ($regime === 'old'),
            'current_amount' => $currNps,
            'statutory_limit' => 50000.0,
            'additional_eligible_gap' => $gapNps,
            'estimated_tax_savings' => ($regime === 'old') ? $taxImpactNps : 0.0,
            'options' => [
                ['name' => 'NPS Tier-1 Account', 'lock_in' => 'Till age 60', 'risk' => 'Moderate / Equity + Debt mix', 'returns' => 'Market-linked pension fund']
            ],
            'caution' => 'NPS funds are locked in until retirement age of 60. At maturity, up to 60% can be withdrawn tax-free, with 40% mandatory annuity.',
            'verification_required' => 'Tier 1 PRAN statement is mandatory for claim.'
        ];

        // 3. Section 80CCD(2) - Employer NPS Contribution (Works in BOTH Regimes!)
        $currEmpNps = $dedMap['80CCD_2'] ?? 0.0;
        $basicSalaryEstimate = ($params['salary_income'] ?? 0.0) * 0.40; // Assume basic ~40% of CTC
        $potentialEmpNps = round($basicSalaryEstimate * 0.10, 2);
        $taxImpactEmpNps = round($potentialEmpNps * $effectiveMultiplier, 2);

        $recommendations[] = [
            'id' => 'rec_80ccd2',
            'title' => 'Employer NPS Contribution under Section 80CCD(2)',
            'section' => 'Section 80CCD(2)',
            'applicable_regime' => 'BOTH Old and New Regimes',
            'is_applicable_in_current_regime' => true,
            'current_amount' => $currEmpNps,
            'statutory_limit' => $potentialEmpNps,
            'additional_eligible_gap' => max(0.0, $potentialEmpNps - $currEmpNps),
            'estimated_tax_savings' => $taxImpactEmpNps,
            'options' => [
                ['name' => 'Corporate NPS Salary Restructuring', 'lock_in' => 'Till retirement', 'risk' => 'Moderate', 'returns' => 'Deduction directly reduces gross taxable salary']
            ],
            'caution' => 'Requires employer corporate NPS tie-up. Up to 10% of (Basic + DA) for private sector employees and 14% for government employees.',
            'verification_required' => 'Must be reflected on Form 16 Part B issued by employer.'
        ];

        // 4. Section 80D - Health Insurance (Self & Parents)
        $curr80DSelf = $dedMap['80D_SELF'] ?? 0.0;
        $limitSelf = $isSenior ? 50000.0 : 25000.0;
        $gap80DSelf = max(0.0, $limitSelf - $curr80DSelf);

        $curr80DParents = $dedMap['80D_PARENTS'] ?? 0.0;
        $limitParents = 50000.0; // Senior parents
        $gap80DParents = max(0.0, $limitParents - $curr80DParents);

        $total80DGap = $gap80DSelf + $gap80DParents;
        $taxImpact80D = round($total80DGap * $effectiveMultiplier, 2);

        $recommendations[] = [
            'id' => 'rec_80d',
            'title' => 'Comprehensive Health Insurance under Section 80D',
            'section' => 'Section 80D',
            'applicable_regime' => 'Old Tax Regime only',
            'is_applicable_in_current_regime' => ($regime === 'old'),
            'current_amount' => $curr80DSelf + $curr80DParents,
            'statutory_limit' => $limitSelf + $limitParents,
            'additional_eligible_gap' => $total80DGap,
            'estimated_tax_savings' => ($regime === 'old') ? $taxImpact80D : 0.0,
            'options' => [
                ['name' => 'Self & Family Health Cover', 'lock_in' => 'Annual renewal', 'risk' => 'None (Protection)', 'returns' => 'Cashless hospitalization coverage'],
                ['name' => 'Parents Health Cover (Senior Citizen)', 'lock_in' => 'Annual renewal', 'risk' => 'None', 'returns' => 'Up to ₹50,000 deduction for parents aged 60+'],
                ['name' => 'Preventive Health Checkup', 'lock_in' => 'Annual', 'risk' => 'None', 'returns' => 'Up to ₹5,000 within the overall limit']
            ],
            'caution' => 'Premiums paid in cash are NOT eligible for tax deduction (except preventive health check-up). Pay via banking channels.',
            'verification_required' => 'Section 80D tax certificate from the insurance company.'
        ];

        // 5. Section 80TTA / 80TTB - Bank Interest Deduction
        $recInterest = [
            'id' => 'rec_interest',
            'title' => $isSenior ? 'Senior Citizen Deposit Interest (Section 80TTB)' : 'Savings Account Interest (Section 80TTA)',
            'section' => $isSenior ? 'Section 80TTB' : 'Section 80TTA',
            'applicable_regime' => 'Old Tax Regime only',
            'is_applicable_in_current_regime' => ($regime === 'old'),
            'current_amount' => $dedMap[$isSenior ? '80TTB' : '80TTA'] ?? 0.0,
            'statutory_limit' => $isSenior ? 50000.0 : 10000.0,
            'additional_eligible_gap' => max(0.0, ($isSenior ? 50000.0 : 10000.0) - ($dedMap[$isSenior ? '80TTB' : '80TTA'] ?? 0.0)),
            'estimated_tax_savings' => round(min(10000.0, $params['other_income'] ?? 0.0) * $effectiveMultiplier, 2),
            'options' => [
                ['name' => 'Bank Savings Interest', 'lock_in' => 'None (Liquid)', 'risk' => 'Very Low', 'returns' => 'Deductible up to statutory threshold']
            ],
            'caution' => 'Must be included in gross income under Other Sources first before claiming deduction.',
            'verification_required' => 'Bank account interest statements / Form 26AS.'
        ];
        $recommendations[] = $recInterest;

        return [
            'financial_year' => $params['financial_year'] ?? '2024-25',
            'current_regime' => $regime,
            'marginal_tax_rate' => ($marginalRate * 100) . '%',
            'recommendations' => $recommendations,
            'disclaimer' => 'These recommendations are calculated for tax planning and educational purposes based on prevailing provisions of the Income Tax Act 1961. Estimated tax savings depend on final taxable income, applicable slabs, and verified documentation. Consult a qualified Chartered Accountant before making financial commitments.'
        ];
    }
}
