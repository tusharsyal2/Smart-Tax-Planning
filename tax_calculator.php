<?php
/**
 * Page 5: Tax Calculation Engine
 * Interactive progressive tax slab calculator with full transparency and snapshot saving.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/services/TaxCalculator.php';
require_once __DIR__ . '/services/TaxComparisonService.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/TaxCalculationRepository.php';
require_once __DIR__ . '/repositories/IncomeRepository.php';
require_once __DIR__ . '/repositories/DeductionRepository.php';

requireAuth();

$userId = $_SESSION['user_id'];
$userRepo = new UserRepository();
$calculator = new TaxCalculator();
$comparisonService = new TaxComparisonService();
$calcRepo = new TaxCalculationRepository();
$incomeRepo = new IncomeRepository();
$deductionRepo = new DeductionRepository();

$profile = $userRepo->findById($userId);
$activeFy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';

// Pre-populate defaults from database records if available
$dbIncomeTotals = $incomeRepo->getTotals($userId, $activeFy);
$dbDeductions = $deductionRepo->getByUserAndFy($userId, $activeFy);

$calcResult = null;
$error = null;

// Handle Calculation Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security token mismatch. Please reload and try again.";
    } else {
        $fy = $_POST['financial_year'] ?? '2024-25';
        $regime = strtolower($_POST['tax_regime'] ?? 'new');
        $ageCategory = $_POST['age_category'] ?? ($profile['age_category'] ?? 'below_60');

        $salary = max(0.0, (float)($_POST['salary_income'] ?? 0.0));
        $hp = (float)($_POST['house_property_income'] ?? 0.0);
        $biz = max(0.0, (float)($_POST['business_income'] ?? 0.0));
        $stcg = max(0.0, (float)($_POST['stcg_income'] ?? 0.0));
        $ltcg = max(0.0, (float)($_POST['ltcg_income'] ?? 0.0));
        $other = max(0.0, (float)($_POST['other_income'] ?? 0.0));
        $tds = max(0.0, (float)($_POST['tds_paid'] ?? 0.0));
        $adv = max(0.0, (float)($_POST['advance_tax_paid'] ?? 0.0));

        // Format deductions array
        $deductions = [
            ['section_code' => '80C', 'amount_entered' => (float)($_POST['ded_80c'] ?? 0.0)],
            ['section_code' => '80CCD_1B', 'amount_entered' => (float)($_POST['ded_80ccd1b'] ?? 0.0)],
            ['section_code' => '80CCD_2', 'amount_entered' => (float)($_POST['ded_80ccd2'] ?? 0.0)],
            ['section_code' => '80D_SELF', 'amount_entered' => (float)($_POST['ded_80d_self'] ?? 0.0)],
            ['section_code' => '80D_PARENTS', 'amount_entered' => (float)($_POST['ded_80d_parents'] ?? 0.0)],
            ['section_code' => '24B', 'amount_entered' => (float)($_POST['ded_24b'] ?? 0.0)],
            ['section_code' => '80E', 'amount_entered' => (float)($_POST['ded_80e'] ?? 0.0)],
            ['section_code' => '80TTA', 'amount_entered' => (float)($_POST['ded_80tta'] ?? 0.0)]
        ];

        $params = [
            'financial_year' => $fy,
            'regime' => $regime,
            'age_category' => $ageCategory,
            'salary_income' => $salary,
            'house_property_income' => $hp,
            'business_income' => $biz,
            'stcg_income' => $stcg,
            'ltcg_income' => $ltcg,
            'other_income' => $other,
            'deductions' => $deductions,
            'tds_paid' => $tds,
            'advance_tax_paid' => $adv
        ];

        try {
            $calcResult = $calculator->calculate($params);

            // If user clicked "Save Calculation Snapshot"
            if (!empty($_POST['action']) && $_POST['action'] === 'save_snapshot') {
                $compResult = $comparisonService->compare($params);
                $calcRepo->saveCalculation($userId, [
                    'financial_year' => $fy,
                    'gross_income' => $calcResult['gross_income'],
                    'exemptions_deductions_old' => $compResult['old_regime']['total_eligible_deductions'],
                    'taxable_income_old' => $compResult['old_regime']['taxable_income'],
                    'tax_liability_old' => $compResult['old_regime']['total_tax_liability'],
                    'exemptions_deductions_new' => $compResult['new_regime']['total_eligible_deductions'],
                    'taxable_income_new' => $compResult['new_regime']['taxable_income'],
                    'tax_liability_new' => $compResult['new_regime']['total_tax_liability'],
                    'recommended_regime' => $compResult['recommended_regime'],
                    'potential_savings' => $compResult['savings_amount'],
                    'taxes_paid' => $calcResult['total_taxes_paid'],
                    'remaining_payable_or_refund' => $calcResult['remaining_tax_payable'] > 0 ? $calcResult['remaining_tax_payable'] : -$calcResult['potential_refund'],
                    'effective_tax_rate' => $calcResult['effective_tax_rate'],
                    'rule_version' => $calcResult['rule_version'],
                    'input_snapshot_json' => $params,
                    'result_snapshot_json' => $calcResult
                ]);
                setFlash('success', 'Calculation snapshot saved successfully into your calculation history.');
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Initial default calculation using profile records
if (!$calcResult && $dbIncomeTotals['total_gross'] > 0) {
    try {
        $initParams = [
            'financial_year' => $activeFy,
            'regime' => $profile['default_regime'] ?? 'new',
            'age_category' => $profile['age_category'] ?? 'below_60',
            'salary_income' => $dbIncomeTotals['salary'],
            'house_property_income' => $dbIncomeTotals['house_property'],
            'business_income' => $dbIncomeTotals['business_profession'],
            'stcg_income' => $dbIncomeTotals['capital_gains_stcg'],
            'ltcg_income' => $dbIncomeTotals['capital_gains_ltcg'],
            'other_income' => $dbIncomeTotals['other_sources'],
            'deductions' => $dbDeductions,
            'tds_paid' => $dbIncomeTotals['total_tds'],
            'advance_tax_paid' => $dbIncomeTotals['total_advance_tax']
        ];
        $calcResult = $calculator->calculate($initParams);
    } catch (Exception $e) {
        // Ignored for initial display
    }
}

$pageTitle = "Tax Calculation Engine";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Income Tax Calculation Engine</h3>
        <p class="text-muted small mb-0">Progressive slab calculations with Section 87A rebates and statutory deductions.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= baseUrl('regime_comparison.php') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left-right me-1"></i> Compare Regimes Side-by-Side
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Column: Input Form -->
    <div class="col-lg-6">
        <div class="glass-card p-4 bg-white border shadow-sm">
            <form method="POST" action="<?= baseUrl('tax_calculator.php') ?>" id="calcForm">
                <?= csrfField() ?>

                <div class="row g-3 mb-3 pb-3 border-bottom">
                    <div class="col-md-6">
                        <label for="financial_year" class="form-label small fw-semibold">Financial Year</label>
                        <select class="form-select form-select-sm" id="financial_year" name="financial_year">
                            <option value="2024-25" <?= ($_POST['financial_year'] ?? $activeFy) === '2024-25' ? 'selected' : '' ?>>FY 2024-25 (AY 2025-26)</option>
                            <option value="2023-24" <?= ($_POST['financial_year'] ?? $activeFy) === '2023-24' ? 'selected' : '' ?>>FY 2023-24 (AY 2024-25)</option>
                            <option value="2025-26" <?= ($_POST['financial_year'] ?? $activeFy) === '2025-26' ? 'selected' : '' ?>>FY 2025-26 (AY 2026-27)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="tax_regime" class="form-label small fw-semibold">Tax Regime</label>
                        <select class="form-select form-select-sm" id="tax_regime" name="tax_regime">
                            <option value="new" <?= (strtolower($_POST['tax_regime'] ?? $profile['default_regime'] ?? 'new') === 'new') ? 'selected' : '' ?>>New Tax Regime (Sec 115BAC)</option>
                            <option value="old" <?= (strtolower($_POST['tax_regime'] ?? $profile['default_regime'] ?? '') === 'old') ? 'selected' : '' ?>>Old Tax Regime (With Deductions)</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label for="age_category" class="form-label small fw-semibold">Age Group</label>
                        <select class="form-select form-select-sm" id="age_category" name="age_category">
                            <option value="below_60" <?= ($_POST['age_category'] ?? $profile['age_category'] ?? '') === 'below_60' ? 'selected' : '' ?>>Individual Below 60 Years</option>
                            <option value="senior_60_80" <?= ($_POST['age_category'] ?? $profile['age_category'] ?? '') === 'senior_60_80' ? 'selected' : '' ?>>Senior Citizen (60 - 80 Years)</option>
                            <option value="super_senior_80" <?= ($_POST['age_category'] ?? $profile['age_category'] ?? '') === 'super_senior_80' ? 'selected' : '' ?>>Super Senior Citizen (80+ Years)</option>
                        </select>
                    </div>
                </div>

                <!-- Income Heads -->
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-wallet2 text-primary me-1"></i> Gross Income Components (₹)</h6>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Gross Salary Income</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="salary_income" value="<?= e($_POST['salary_income'] ?? $dbIncomeTotals['salary'] ?? 0) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Income from Other Sources (Interest)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="other_income" value="<?= e($_POST['other_income'] ?? $dbIncomeTotals['other_sources'] ?? 0) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">House Property Income / (Loss)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="house_property_income" value="<?= e($_POST['house_property_income'] ?? $dbIncomeTotals['house_property'] ?? 0) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Business / Professional Income</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="business_income" value="<?= e($_POST['business_income'] ?? $dbIncomeTotals['business_profession'] ?? 0) ?>">
                    </div>
                </div>

                <!-- Deductions Section -->
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-piggy-bank text-primary me-1"></i> Eligible Deductions (₹)</h6>
                <div class="p-2 mb-3 bg-light rounded border small text-muted">
                    <i class="bi bi-info-circle text-primary me-1"></i> Note: Chapter VI-A deductions (except Employer NPS 80CCD(2)) apply primarily to the <strong>Old Regime</strong>.
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Section 80C (PPF, EPF, ELSS, Life Ins) [Max 1.5L]</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="ded_80c" value="<?= e($_POST['ded_80c'] ?? 0) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Section 80CCD(1B) NPS Voluntary [Max 50k]</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="ded_80ccd1b" value="<?= e($_POST['ded_80ccd1b'] ?? 0) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Section 80D Health Insurance (Self/Family)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="ded_80d_self" value="<?= e($_POST['ded_80d_self'] ?? 0) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Section 80D Health Insurance (Parents)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="ded_80d_parents" value="<?= e($_POST['ded_80d_parents'] ?? 0) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Section 24(b) Home Loan Interest [Max 2L]</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="ded_24b" value="<?= e($_POST['ded_24b'] ?? 0) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Section 80CCD(2) Employer NPS (Both Regimes)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="ded_80ccd2" value="<?= e($_POST['ded_80ccd2'] ?? 0) ?>">
                    </div>
                </div>

                <!-- Taxes Already Paid -->
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-credit-card text-primary me-1"></i> Taxes Paid / Deducted (₹)</h6>
                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">TDS Deducted by Employer / Deductors</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="tds_paid" value="<?= e($_POST['tds_paid'] ?? $dbIncomeTotals['total_tds'] ?? 0) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Advance Tax Self-Assessment Paid</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="advance_tax_paid" value="<?= e($_POST['advance_tax_paid'] ?? $dbIncomeTotals['total_advance_tax'] ?? 0) ?>">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="action" value="calculate" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-cpu me-1"></i> Calculate Tax Liability
                    </button>
                    <button type="submit" name="action" value="save_snapshot" class="btn btn-outline-success">
                        <i class="bi bi-bookmark-check me-1"></i> Save to History
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Transparent Calculation Breakdown -->
    <div class="col-lg-6">
        <?php if (!$calcResult): ?>
            <div class="glass-card p-5 bg-white text-center border h-100 d-flex flex-column justify-content-center">
                <i class="bi bi-calculator text-muted fs-1 mb-3"></i>
                <h5 class="fw-bold">Ready to Calculate</h5>
                <p class="text-muted small">Enter your financial figures on the left and click "Calculate Tax Liability" to view the complete step-by-step breakdown.</p>
            </div>
        <?php else: ?>
            <div class="glass-card p-4 bg-white border shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div>
                        <h5 class="fw-bold mb-0">Tax Calculation Breakdown</h5>
                        <div class="text-muted small">
                            Regime: <strong class="text-uppercase text-primary"><?= e($calcResult['regime']) ?></strong> &bull; FY <?= e($calcResult['financial_year']) ?>
                        </div>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                        Effective Rate: <strong><?= e($calcResult['effective_tax_rate']) ?>%</strong>
                    </span>
                </div>

                <!-- Step Summary Table -->
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-financial">
                        <tbody>
                            <tr>
                                <td>Gross Annual Income</td>
                                <td class="text-end fw-semibold"><?= formatInr($calcResult['gross_income']) ?></td>
                            </tr>
                            <tr>
                                <td>Standard Deduction (Salary)</td>
                                <td class="text-end text-success">- <?= formatInr($calcResult['standard_deduction']) ?></td>
                            </tr>
                            <tr>
                                <td>Gross Total Income (GTI)</td>
                                <td class="text-end fw-bold"><?= formatInr($calcResult['gross_total_income']) ?></td>
                            </tr>
                            <tr>
                                <td>Chapter VI-A Deductions Applied</td>
                                <td class="text-end text-success">- <?= formatInr($calcResult['applicable_deductions']) ?></td>
                            </tr>
                            <tr class="table-light">
                                <td class="fw-bold">Net Taxable Income (Sec 288A)</td>
                                <td class="text-end fw-bold text-primary"><?= formatInr($calcResult['taxable_income']) ?></td>
                            </tr>
                            <tr>
                                <td>Progressive Slab Tax</td>
                                <td class="text-end"><?= formatInr($calcResult['slab_tax']) ?></td>
                            </tr>
                            <tr>
                                <td>Rebate under Section 87A</td>
                                <td class="text-end text-success">- <?= formatInr($calcResult['rebate_87a']) ?></td>
                            </tr>
                            <?php if ($calcResult['surcharge'] > 0): ?>
                                <tr>
                                    <td>Surcharge (<?= ($calcResult['surcharge_rate'] * 100) ?>%)</td>
                                    <td class="text-end text-danger">+ <?= formatInr($calcResult['surcharge']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td>Health &amp; Education Cess (4%)</td>
                                <td class="text-end">+ <?= formatInr($calcResult['cess']) ?></td>
                            </tr>
                            <tr class="table-primary fw-bold">
                                <td>Total Estimated Tax Liability</td>
                                <td class="text-end fs-6"><?= formatInr($calcResult['total_tax_liability']) ?></td>
                            </tr>
                            <tr>
                                <td>Taxes Paid (TDS &amp; Advance Tax)</td>
                                <td class="text-end text-muted">- <?= formatInr($calcResult['total_taxes_paid']) ?></td>
                            </tr>
                            <tr class="<?= ($calcResult['remaining_tax_payable'] > 0) ? 'table-danger' : 'table-success' ?> fw-bold">
                                <td><?= ($calcResult['remaining_tax_payable'] > 0) ? 'Remaining Tax Payable' : 'Estimated Potential Refund' ?></td>
                                <td class="text-end fs-6">
                                    <?= ($calcResult['remaining_tax_payable'] > 0) ? formatInr($calcResult['remaining_tax_payable']) : formatInr($calcResult['potential_refund']) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Progressive Slabs Detailed Table -->
                <h6 class="fw-bold text-dark mt-4 mb-2 small text-uppercase letter-spacing">
                    <i class="bi bi-layers text-primary me-1"></i> Progressive Slab Breakdown
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small">
                        <thead class="table-light">
                            <tr>
                                <th>Income Slab</th>
                                <th>Rate</th>
                                <th>Taxable in Slab</th>
                                <th class="text-end">Tax in Slab</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calcResult['slabs_breakdown'] as $sb): ?>
                                <tr>
                                    <td><?= e($sb['range']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($sb['rate']) ?></span></td>
                                    <td><?= formatInr($sb['taxable_amount']) ?></td>
                                    <td class="text-end fw-semibold"><?= formatInr($sb['tax']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-2 bg-light rounded text-muted small mt-3" style="font-size:0.75rem;">
                    <strong>Statutory Reference:</strong> <?= e($calcResult['rule_source']) ?>. intermediate figures rounded per statutory rounding rules (Sec 288A &amp; 288B).
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
