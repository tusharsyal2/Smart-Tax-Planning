<?php
/**
 * Page 10: Tax Planning Simulator
 * Interactive "What-If" sandbox for modeling salary hikes, investments, and regime shifts.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/services/TaxSimulationService.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/IncomeRepository.php';
require_once __DIR__ . '/repositories/DeductionRepository.php';
require_once __DIR__ . '/repositories/TaxCalculationRepository.php';

requireAuth();

$userId = $_SESSION['user_id'];
$userRepo = new UserRepository();
$incomeRepo = new IncomeRepository();
$deductionRepo = new DeductionRepository();
$calcRepo = new TaxCalculationRepository();
$simService = new TaxSimulationService();

$profile = $userRepo->findById($userId);
$activeFy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';
$_SESSION['active_fy'] = $activeFy;

$incomeTotals = $incomeRepo->getTotals($userId, $activeFy);
$deductions = $deductionRepo->getByUserAndFy($userId, $activeFy);

// Base parameters
$baseParams = [
    'financial_year' => $activeFy,
    'regime' => $profile['default_regime'] ?? 'new',
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

// Adjustments (from POST or query)
$salaryDelta = (float)($_POST['salary_delta'] ?? 0.0);
$extra80c = (float)($_POST['extra_80c'] ?? 0.0);
$extraNps = (float)($_POST['extra_nps'] ?? 0.0);
$extra80d = (float)($_POST['extra_80d'] ?? 0.0);
$extraHomeLoan = (float)($_POST['extra_24b'] ?? 0.0);

$adjustments = [
    'salary_delta' => $salaryDelta,
    'deductions' => [
        '80C' => $extra80c,
        '80CCD_1B' => $extraNps,
        '80D_SELF' => $extra80d,
        '24B' => $extraHomeLoan
    ]
];

// Run simulation
$simResult = $simService->simulate($baseParams, $adjustments);

// Save Scenario if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['save_scenario_name'])) {
    if (validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $scenarioName = trim($_POST['save_scenario_name']);
        $calcRepo->saveScenario($userId, $activeFy, $scenarioName, $adjustments, $simResult);
        setFlash('success', "Scenario '{$scenarioName}' saved to your planning history.");
    }
}

$pageTitle = "Tax Planning Simulator";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Tax Planning Simulator (What-If Sandbox)</h3>
        <p class="text-muted small mb-0">Model salary appraisal increments or new investments without altering your baseline records.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width: 140px;" onchange="location.href='?fy='+this.value">
            <option value="2024-25" <?= $activeFy === '2024-25' ? 'selected' : '' ?>>FY 2024-25</option>
            <option value="2023-24" <?= $activeFy === '2023-24' ? 'selected' : '' ?>>FY 2023-24</option>
            <option value="2025-26" <?= $activeFy === '2025-26' ? 'selected' : '' ?>>FY 2025-26</option>
        </select>
    </div>
</div>

<div class="row g-4">
    <!-- Simulator Controls Column -->
    <div class="col-lg-5">
        <div class="glass-card p-4 bg-white border shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h5 class="fw-bold mb-0"><i class="bi bi-sliders text-primary me-2"></i>Scenario Inputs</h5>
                <a href="<?= baseUrl('tax_simulator.php') ?>" class="btn btn-sm btn-light text-muted">Reset</a>
            </div>

            <form method="POST" action="<?= baseUrl('tax_simulator.php') ?>">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Annual Salary Adjustment (₹)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-graph-up-arrow"></i></span>
                        <input type="number" step="1000" class="form-control" name="salary_delta" value="<?= e($salaryDelta) ?>" placeholder="+200000 (Hike) or -100000">
                    </div>
                    <div class="form-text" style="font-size:0.75rem;">Current Baseline Salary: <?= formatInr($incomeTotals['salary']) ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Additional Section 80C Investment (₹)</label>
                    <input type="number" step="5000" class="form-control form-control-sm" name="extra_80c" value="<?= e($extra80c) ?>" placeholder="e.g. 50000 (PPF/ELSS)">
                    <div class="form-text" style="font-size:0.75rem;">Subject to statutory ceiling of ₹1.5 Lakhs</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Additional NPS Contribution under 80CCD(1B) (₹)</label>
                    <input type="number" step="5000" class="form-control form-control-sm" name="extra_nps" value="<?= e($extraNps) ?>" placeholder="e.g. 50000">
                    <div class="form-text" style="font-size:0.75rem;">Exclusive NPS ceiling of ₹50,000</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Additional Health Insurance Premium under 80D (₹)</label>
                    <input type="number" step="2000" class="form-control form-control-sm" name="extra_80d" value="<?= e($extra80d) ?>" placeholder="e.g. 25000">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Additional Home Loan Interest under 24(b) (₹)</label>
                    <input type="number" step="10000" class="form-control form-control-sm" name="extra_24b" value="<?= e($extraHomeLoan) ?>" placeholder="e.g. 100000">
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-play-circle-fill me-1"></i> Run Scenario Simulation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Simulation Results Column -->
    <div class="col-lg-7">
        <div class="glass-card p-4 bg-white border shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h5 class="fw-bold mb-0">Simulation Impact: Baseline vs Scenario</h5>
                <?php if ($simResult['deltas']['optimal_regime_changed']): ?>
                    <span class="badge bg-warning text-dark border">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Optimal Regime Shifts!
                    </span>
                <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        Regime Stability Maintained
                    </span>
                <?php endif; ?>
            </div>

            <!-- Side-by-Side Metrics Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-financial align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Financial Parameter</th>
                            <th class="text-end">Base Case</th>
                            <th class="text-end">Simulated Case</th>
                            <th class="text-end">Delta (Change)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Gross Salary Income</td>
                            <td class="text-end"><?= formatInr($simResult['base']['old_regime']['salary_income']) ?></td>
                            <td class="text-end fw-semibold"><?= formatInr($simResult['scenario']['old_regime']['salary_income']) ?></td>
                            <td class="text-end <?= $salaryDelta >= 0 ? 'text-primary' : 'text-danger' ?>">
                                <?= ($salaryDelta >= 0 ? '+' : '') . formatInr($salaryDelta) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Taxable Income (Old Regime)</td>
                            <td class="text-end"><?= formatInr($simResult['base']['old_regime']['taxable_income']) ?></td>
                            <td class="text-end fw-semibold"><?= formatInr($simResult['scenario']['old_regime']['taxable_income']) ?></td>
                            <td class="text-end">
                                <?= formatInr($simResult['deltas']['taxable_income_old_delta']) ?>
                            </td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-semibold">Old Regime Tax Liability</td>
                            <td class="text-end"><?= formatInr($simResult['base']['old_regime']['total_tax_liability']) ?></td>
                            <td class="text-end fw-bold"><?= formatInr($simResult['scenario']['old_regime']['total_tax_liability']) ?></td>
                            <td class="text-end fw-bold <?= $simResult['deltas']['tax_liability_old_delta'] <= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= ($simResult['deltas']['tax_liability_old_delta'] > 0 ? '+' : '') . formatInr($simResult['deltas']['tax_liability_old_delta']) ?>
                            </td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-semibold">New Regime Tax Liability</td>
                            <td class="text-end"><?= formatInr($simResult['base']['new_regime']['total_tax_liability']) ?></td>
                            <td class="text-end fw-bold"><?= formatInr($simResult['scenario']['new_regime']['total_tax_liability']) ?></td>
                            <td class="text-end fw-bold <?= $simResult['deltas']['tax_liability_new_delta'] <= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= ($simResult['deltas']['tax_liability_new_delta'] > 0 ? '+' : '') . formatInr($simResult['deltas']['tax_liability_new_delta']) ?>
                            </td>
                        </tr>
                        <tr class="table-success fw-bold">
                            <td>Recommended Optimal Regime</td>
                            <td class="text-end text-uppercase"><?= e($simResult['base']['recommended_regime']) ?></td>
                            <td class="text-end text-uppercase"><?= e($simResult['scenario']['recommended_regime']) ?></td>
                            <td class="text-end text-success">
                                Saves <?= formatInr($simResult['scenario']['savings_amount']) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Narrative Simulation Analysis -->
            <div class="alert alert-light border p-3 small mb-3">
                <h6 class="fw-bold mb-1"><i class="bi bi-lightbulb text-primary me-1"></i> Scenario Insights</h6>
                <div><?= $simResult['scenario']['explanation'] ?></div>
            </div>

            <!-- Save Scenario Form -->
            <div class="pt-3 border-top">
                <form method="POST" action="<?= baseUrl('tax_simulator.php') ?>" class="d-flex gap-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="salary_delta" value="<?= $salaryDelta ?>">
                    <input type="hidden" name="extra_80c" value="<?= $extra80c ?>">
                    <input type="hidden" name="extra_nps" value="<?= $extraNps ?>">
                    <input type="hidden" name="extra_80d" value="<?= $extra80d ?>">
                    <input type="hidden" name="extra_24b" value="<?= $extraHomeLoan ?>">

                    <input type="text" class="form-control form-control-sm" name="save_scenario_name" required placeholder="Name this scenario (e.g. 15% Promotion + NPS)" style="max-width: 320px;">
                    <button type="submit" class="btn btn-outline-success btn-sm text-nowrap">
                        <i class="bi bi-bookmark-plus me-1"></i> Save Scenario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
