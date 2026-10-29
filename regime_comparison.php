<?php
/**
 * Page 6: Old vs New Tax Regime Comparison
 * Side-by-side comparative analysis with Breakeven Deduction calculations.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/services/TaxComparisonService.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/IncomeRepository.php';
require_once __DIR__ . '/repositories/DeductionRepository.php';

requireAuth();

$userId = $_SESSION['user_id'];
$userRepo = new UserRepository();
$incomeRepo = new IncomeRepository();
$deductionRepo = new DeductionRepository();
$comparisonService = new TaxComparisonService();

$profile = $userRepo->findById($userId);
$activeFy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';
$_SESSION['active_fy'] = $activeFy;

// Load user's actual database records
$incomeTotals = $incomeRepo->getTotals($userId, $activeFy);
$deductions = $deductionRepo->getByUserAndFy($userId, $activeFy);

$hasIncome = ($incomeTotals['total_gross'] > 0);

// Default inputs from DB
$calcParams = [
    'financial_year' => $activeFy,
    'age_category' => $profile['age_category'] ?? 'below_60',
    'salary_income' => $incomeTotals['salary'] ?? 0.0,
    'house_property_income' => $incomeTotals['house_property'] ?? 0.0,
    'business_income' => $incomeTotals['business_profession'] ?? 0.0,
    'stcg_income' => $incomeTotals['capital_gains_stcg'] ?? 0.0,
    'ltcg_income' => $incomeTotals['capital_gains_ltcg'] ?? 0.0,
    'other_income' => $incomeTotals['other_sources'] ?? 0.0,
    'deductions' => $deductions,
    'tds_paid' => $incomeTotals['total_tds'] ?? 0.0,
    'advance_tax_paid' => $incomeTotals['total_advance_tax'] ?? 0.0
];

$comparison = null;
if ($hasIncome) {
    $comparison = $comparisonService->compare($calcParams);
}

$pageTitle = "Old vs New Tax Regime Comparison";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Old vs New Tax Regime Comparison</h3>
        <p class="text-muted small mb-0">Evaluate both regimes under Finance (No. 2) Act 2024 provisions using your validated financials.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width: 150px;" onchange="location.href='?fy='+this.value">
            <option value="2024-25" <?= $activeFy === '2024-25' ? 'selected' : '' ?>>FY 2024-25</option>
            <option value="2023-24" <?= $activeFy === '2023-24' ? 'selected' : '' ?>>FY 2023-24</option>
            <option value="2025-26" <?= $activeFy === '2025-26' ? 'selected' : '' ?>>FY 2025-26</option>
        </select>
        <a href="<?= baseUrl('tax_simulator.php') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-sliders me-1"></i> What-If Simulator
        </a>
    </div>
</div>

<?php if (!$hasIncome): ?>
    <div class="glass-card p-5 bg-white text-center border">
        <i class="bi bi-arrow-left-right text-muted fs-1 mb-3"></i>
        <h4 class="fw-bold">No Income Records Found for FY <?= e($activeFy) ?></h4>
        <p class="text-muted small max-w-md mx-auto mb-4">
            To compare the old and new tax regimes, please add your salary or other income details first.
        </p>
        <a href="<?= baseUrl('income.php') ?>" class="btn btn-primary px-4">
            <i class="bi bi-plus-circle me-1"></i> Add Income Records
        </a>
    </div>
<?php else: ?>
    <?php
        $old = $comparison['old_regime'];
        $new = $comparison['new_regime'];
        $isNewOptimal = ($comparison['recommended_regime'] === 'new');
        $isOldOptimal = ($comparison['recommended_regime'] === 'old');
    ?>

    <!-- Prominent Recommendation Banner -->
    <div class="glass-card p-4 mb-4 border bg-white shadow-sm">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-subtle text-success fs-3">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">
                        Recommended: <span class="text-uppercase text-success"><?= e($comparison['recommended_regime']) ?> Tax Regime</span>
                    </h5>
                    <div class="text-muted small"><?= $comparison['explanation'] ?></div>
                </div>
            </div>
            <div class="text-md-end">
                <div class="text-muted small">Estimated Annual Savings</div>
                <div class="fs-3 fw-bold text-success"><?= formatInr($comparison['savings_amount']) ?></div>
            </div>
        </div>
    </div>

    <!-- Side-by-Side Comparison Cards -->
    <div class="row g-4 mb-4">
        <!-- Old Regime Card -->
        <div class="col-lg-6">
            <div class="regime-box <?= $isOldOptimal ? 'optimal' : '' ?> h-100 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Old Tax Regime</h4>
                    <span class="badge bg-light text-dark border">With Chapter VI-A</span>
                </div>
                <p class="text-muted small">High slab rates but permits Section 80C, 80D, 80CCD, HRA, and Home Loan deductions.</p>

                <div class="p-3 bg-light rounded-3 mb-3 text-center">
                    <div class="text-muted small text-uppercase fw-semibold">Old Regime Tax Liability</div>
                    <div class="fs-2 fw-bold <?= $isOldOptimal ? 'text-success' : 'text-danger' ?>">
                        <?= formatInr($old['total_tax_liability']) ?>
                    </div>
                    <div class="text-muted small">Effective Rate: <strong><?= e($old['effective_tax_rate']) ?>%</strong></div>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Standard Deduction</span>
                        <span class="fw-semibold"><?= formatInr($old['standard_deduction']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Eligible Chapter VI-A Deductions</span>
                        <span class="fw-semibold text-success"><?= formatInr($old['applicable_deductions']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Net Taxable Income</span>
                        <span class="fw-bold"><?= formatInr($old['taxable_income']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Tax Before Rebate</span>
                        <span><?= formatInr($old['slab_tax']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Section 87A Rebate (up to ₹5L income)</span>
                        <span class="text-success"><?= formatInr($old['rebate_87a']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Health &amp; Education Cess (4%)</span>
                        <span><?= formatInr($old['cess']) ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- New Regime Card -->
        <div class="col-lg-6">
            <div class="regime-box <?= $isNewOptimal ? 'optimal' : '' ?> h-100 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">New Tax Regime</h4>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Sec 115BAC</span>
                </div>
                <p class="text-muted small">Lower progressive slabs, ₹75,000 standard deduction, and Section 87A rebate up to ₹7 Lakhs.</p>

                <div class="p-3 bg-light rounded-3 mb-3 text-center">
                    <div class="text-muted small text-uppercase fw-semibold">New Regime Tax Liability</div>
                    <div class="fs-2 fw-bold <?= $isNewOptimal ? 'text-success' : 'text-danger' ?>">
                        <?= formatInr($new['total_tax_liability']) ?>
                    </div>
                    <div class="text-muted small">Effective Rate: <strong><?= e($new['effective_tax_rate']) ?>%</strong></div>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Standard Deduction</span>
                        <span class="fw-semibold text-success"><?= formatInr($new['standard_deduction']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Eligible Deductions (80CCD(2) only)</span>
                        <span class="fw-semibold"><?= formatInr($new['applicable_deductions']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Net Taxable Income</span>
                        <span class="fw-bold"><?= formatInr($new['taxable_income']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Tax Before Rebate</span>
                        <span><?= formatInr($new['slab_tax']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Section 87A Rebate (up to ₹7L income)</span>
                        <span class="text-success"><?= formatInr($new['rebate_87a']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Health &amp; Education Cess (4%)</span>
                        <span><?= formatInr($new['cess']) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Breakeven Deduction Indicator -->
    <div class="glass-card p-4 bg-white border shadow-sm mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="fw-bold mb-1"><i class="bi bi-bullseye text-primary me-2"></i>Breakeven Deduction Point</h5>
                <p class="text-muted small mb-0">
                    To make the <strong>Old Tax Regime</strong> beneficial over the New Regime for your annual income, your total eligible Chapter VI-A deductions must be at least <strong><?= formatInr($comparison['breakeven_deduction']) ?></strong>.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <span class="badge bg-primary fs-6 px-3 py-2">
                    Breakeven: <?= formatInr($comparison['breakeven_deduction']) ?>
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
