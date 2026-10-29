<?php
/**
 * Deduction Eligibility Service
 * Evaluates Chapter VI-A deductions, applies statutory ceilings, and verifies regime eligibility.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/TaxRuleService.php';

class DeductionEligibilityService {
    private TaxRuleService $taxRuleService;

    public function __construct(?TaxRuleService $taxRuleService = null) {
        $this->taxRuleService = $taxRuleService ?? new TaxRuleService();
    }

    /**
     * Evaluate deductions entered by the user
     * 
     * @param array $deductions Array of records with keys: section_code, amount_entered, (optional) notes
     * @param string $regime 'old' or 'new'
     * @param string $ageCategory 'below_60', 'senior_60_80', 'super_senior_80'
     * @return array Calculated breakdown of deductions
     */
    public function evaluateDeductions(array $deductions, string $regime = 'old', string $ageCategory = 'below_60'): array {
        $isSenior = ($ageCategory === 'senior_60_80' || $ageCategory === 'super_senior_80');
        $regime = strtolower($regime);

        // Group inputs by section
        $grouped = [];
        foreach ($deductions as $item) {
            $code = strtoupper(trim($item['section_code'] ?? ''));
            $amount = max(0.0, (float)($item['amount_entered'] ?? 0.0));
            if (!isset($grouped[$code])) {
                $grouped[$code] = 0.0;
            }
            $grouped[$code] += $amount;
        }

        $evaluated = [];
        $totalEligibleOld = 0.0;
        $totalEligibleNew = 0.0;

        // 1. Section 80C (Statutory ceiling: Rs 1,50,000)
        $entered80c = $grouped['80C'] ?? 0.0;
        $eligible80c = min($entered80c, 150000.0);
        $evaluated['80C'] = [
            'section' => '80C',
            'name' => 'Investments & Savings (PPF, EPF, ELSS, Life Insurance, Tuition)',
            'amount_entered' => $entered80c,
            'eligible_old' => $eligible80c,
            'eligible_new' => 0.0, // Disallowed under New Regime
            'statutory_limit' => 150000.0,
            'allowed_in_new_regime' => false,
            'notes' => $entered80c > 150000.0 ? 'Capped at statutory ceiling of ₹1,50,000' : 'Fully within statutory limit'
        ];
        $totalEligibleOld += $eligible80c;

        // 2. Section 80CCD(1B) - Additional NPS (Statutory ceiling: Rs 50,000)
        $entered80ccd1b = $grouped['80CCD_1B'] ?? $grouped['80CCD(1B)'] ?? 0.0;
        $eligible80ccd1b = min($entered80ccd1b, 50000.0);
        $evaluated['80CCD_1B'] = [
            'section' => '80CCD(1B)',
            'name' => 'Additional Voluntary NPS Contribution',
            'amount_entered' => $entered80ccd1b,
            'eligible_old' => $eligible80ccd1b,
            'eligible_new' => 0.0,
            'statutory_limit' => 50000.0,
            'allowed_in_new_regime' => false,
            'notes' => $entered80ccd1b > 50000.0 ? 'Capped at statutory ceiling of ₹50,000' : 'Within limit'
        ];
        $totalEligibleOld += $eligible80ccd1b;

        // 3. Section 80CCD(2) - Employer NPS Contribution (Allowed in BOTH regimes!)
        $entered80ccd2 = $grouped['80CCD_2'] ?? $grouped['80CCD(2)'] ?? 0.0;
        $evaluated['80CCD_2'] = [
            'section' => '80CCD(2)',
            'name' => 'Employer Contribution to NPS',
            'amount_entered' => $entered80ccd2,
            'eligible_old' => $entered80ccd2,
            'eligible_new' => $entered80ccd2, // Permitted under New Regime!
            'statutory_limit' => null, // Up to 10%/14% of basic
            'allowed_in_new_regime' => true,
            'notes' => 'Permitted under BOTH Old and New Regimes (Section 115BAC)'
        ];
        $totalEligibleOld += $entered80ccd2;
        $totalEligibleNew += $entered80ccd2;

        // 4. Section 80D - Health Insurance (Self & Family)
        $entered80dSelf = $grouped['80D_SELF'] ?? $grouped['80D'] ?? 0.0;
        $limitSelf = $isSenior ? 50000.0 : 25000.0;
        $eligible80dSelf = min($entered80dSelf, $limitSelf);
        $evaluated['80D_SELF'] = [
            'section' => '80D (Self & Family)',
            'name' => 'Health Insurance (Self, Spouse, Children)',
            'amount_entered' => $entered80dSelf,
            'eligible_old' => $eligible80dSelf,
            'eligible_new' => 0.0,
            'statutory_limit' => $limitSelf,
            'allowed_in_new_regime' => false,
            'notes' => $isSenior ? 'Senior citizen limit ₹50,000 applied' : 'Standard limit ₹25,000 applied'
        ];
        $totalEligibleOld += $eligible80dSelf;

        // 5. Section 80D - Health Insurance (Parents)
        $entered80dParents = $grouped['80D_PARENTS'] ?? 0.0;
        // Parents can be senior citizen (up to 50k) or non-senior (25k)
        $limitParents = 50000.0; // Assume senior parents limit as standard ceiling
        $eligible80dParents = min($entered80dParents, $limitParents);
        $evaluated['80D_PARENTS'] = [
            'section' => '80D (Parents)',
            'name' => 'Health Insurance for Parents',
            'amount_entered' => $entered80dParents,
            'eligible_old' => $eligible80dParents,
            'eligible_new' => 0.0,
            'statutory_limit' => $limitParents,
            'allowed_in_new_regime' => false,
            'notes' => 'Max ₹50,000 if parents are senior citizens (₹25,000 otherwise)'
        ];
        $totalEligibleOld += $eligible80dParents;

        // 6. Section 80E - Higher Education Loan Interest (No limit)
        $entered80e = $grouped['80E'] ?? 0.0;
        $evaluated['80E'] = [
            'section' => '80E',
            'name' => 'Interest on Higher Education Loan',
            'amount_entered' => $entered80e,
            'eligible_old' => $entered80e,
            'eligible_new' => 0.0,
            'statutory_limit' => null,
            'allowed_in_new_regime' => false,
            'notes' => 'No monetary upper limit for 8 consecutive years'
        ];
        $totalEligibleOld += $entered80e;

        // 7. Section 80G - Donations
        $entered80g = $grouped['80G'] ?? 0.0;
        $evaluated['80G'] = [
            'section' => '80G',
            'name' => 'Donations to Approved Charitable Funds',
            'amount_entered' => $entered80g,
            'eligible_old' => $entered80g,
            'eligible_new' => 0.0,
            'statutory_limit' => null,
            'allowed_in_new_regime' => false,
            'notes' => 'Subject to 50%/100% eligibility rules'
        ];
        $totalEligibleOld += $entered80g;

        // 8. Section 80TTA / 80TTB - Savings / Deposit Interest
        if ($isSenior) {
            $entered80ttb = $grouped['80TTB'] ?? $grouped['80TTA'] ?? 0.0;
            $eligible80ttb = min($entered80ttb, 50000.0);
            $evaluated['80TTB'] = [
                'section' => '80TTB',
                'name' => 'Interest on Deposits for Senior Citizens',
                'amount_entered' => $entered80ttb,
                'eligible_old' => $eligible80ttb,
                'eligible_new' => 0.0,
                'statutory_limit' => 50000.0,
                'allowed_in_new_regime' => false,
                'notes' => 'Senior citizen interest deduction up to ₹50,000'
            ];
            $totalEligibleOld += $eligible80ttb;
        } else {
            $entered80tta = $grouped['80TTA'] ?? 0.0;
            $eligible80tta = min($entered80tta, 10000.0);
            $evaluated['80TTA'] = [
                'section' => '80TTA',
                'name' => 'Savings Account Interest Deduction',
                'amount_entered' => $entered80tta,
                'eligible_old' => $eligible80tta,
                'eligible_new' => 0.0,
                'statutory_limit' => 10000.0,
                'allowed_in_new_regime' => false,
                'notes' => 'Savings account interest up to ₹10,000'
            ];
            $totalEligibleOld += $eligible80tta;
        }

        // 9. Section 24(b) - Home Loan Interest (Self-occupied property)
        $entered24b = $grouped['24B'] ?? $grouped['24(B)'] ?? 0.0;
        $eligible24b = min($entered24b, 200000.0);
        $evaluated['24B'] = [
            'section' => '24(b)',
            'name' => 'Home Loan Interest (Self-Occupied)',
            'amount_entered' => $entered24b,
            'eligible_old' => $eligible24b,
            'eligible_new' => 0.0,
            'statutory_limit' => 200000.0,
            'allowed_in_new_regime' => false,
            'notes' => 'Capped at ₹2,00,000 for self-occupied house'
        ];
        $totalEligibleOld += $eligible24b;

        return [
            'items' => $evaluated,
            'total_eligible_old' => $totalEligibleOld,
            'total_eligible_new' => $totalEligibleNew,
            'selected_regime_eligible' => ($regime === 'new') ? $totalEligibleNew : $totalEligibleOld
        ];
    }
}
