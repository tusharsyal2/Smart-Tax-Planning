<?php
/**
 * Page 7: Tax-Saving Recommendations Engine
 * Personalized tax-planning opportunities based on actual marginal tax rates.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/services/TaxRecommendationService.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/IncomeRepository.php';
require_once __DIR__ . '/repositories/DeductionRepository.php';

requireAuth();

$userId = $_SESSION['user_id'];
$userRepo = new UserRepository();
$incomeRepo = new IncomeRepository();
$deductionRepo = new DeductionRepository();
$recService = new TaxRecommendationService();

$profile = $userRepo->findById($userId);
$activeFy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';
$_SESSION['active_fy'] = $activeFy;

$incomeTotals = $incomeRepo->getTotals($userId, $activeFy);
$deductions = $deductionRepo->getByUserAndFy($userId, $activeFy);

$calcParams = [
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

$recResult = $recService->generateRecommendations($calcParams);
$recommendations = $recResult['recommendations'] ?? [];

$pageTitle = "Tax-Saving Recommendations";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Personalized Tax-Saving Opportunities</h3>
        <p class="text-muted small mb-0">Actionable deduction avenues calculated against your marginal tax slab rate (<?= e($recResult['marginal_tax_rate']) ?>).</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width: 150px;" onchange="location.href='?fy='+this.value">
            <option value="2024-25" <?= $activeFy === '2024-25' ? 'selected' : '' ?>>FY 2024-25</option>
            <option value="2023-24" <?= $activeFy === '2023-24' ? 'selected' : '' ?>>FY 2023-24</option>
            <option value="2025-26" <?= $activeFy === '2025-26' ? 'selected' : '' ?>>FY 2025-26</option>
        </select>
    </div>
</div>

<div class="alert alert-warning border-warning-subtle small d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-shield-exclamation text-warning fs-4"></i>
    <div>
        <strong>Fiduciary Caution:</strong> Never invest or spend money solely for tax reduction. Always evaluate liquidity, personal risk appetite, and lock-in requirements. Spending ₹100 does not save ₹100; it saves tax proportional to your marginal tax bracket.
    </div>
</div>

<div class="row g-4">
    <?php foreach ($recommendations as $rec): ?>
        <div class="col-lg-6">
            <div class="glass-card p-4 bg-white border h-100 shadow-sm d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small mb-1">
                            <?= e($rec['section']) ?>
                        </span>
                        <h5 class="fw-bold text-dark mb-0"><?= e($rec['title']) ?></h5>
                    </div>
                    <?php if ($rec['estimated_tax_savings'] > 0): ?>
                        <div class="text-end">
                            <span class="badge bg-success-subtle text-success fs-6 fw-bold">
                                Save ~<?= formatInr($rec['estimated_tax_savings']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row g-2 my-3 p-3 bg-light rounded small">
                    <div class="col-4">
                        <span class="text-muted d-block">Current Amount</span>
                        <strong class="text-dark"><?= formatInr($rec['current_amount']) ?></strong>
                    </div>
                    <div class="col-4">
                        <span class="text-muted d-block">Statutory Limit</span>
                        <strong class="text-dark"><?= $rec['statutory_limit'] ? formatInr($rec['statutory_limit']) : 'No Limit' ?></strong>
                    </div>
                    <div class="col-4">
                        <span class="text-muted d-block">Untapped Gap</span>
                        <strong class="text-primary"><?= formatInr($rec['additional_eligible_gap']) ?></strong>
                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="fw-bold small text-uppercase text-muted mb-2">Eligible Investment Avenues</h6>
                    <div class="list-group list-group-flush border rounded small">
                        <?php foreach ($rec['options'] as $opt): ?>
                            <div class="list-group-item py-2 px-3">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold text-dark"><?= e($opt['name']) ?></span>
                                    <span class="text-muted">Lock-in: <strong><?= e($opt['lock_in']) ?></strong></span>
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">
                                    Risk: <?= e($opt['risk']) ?> &bull; <?= e($opt['returns']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-auto pt-2 border-top text-muted small" style="font-size:0.75rem;">
                    <div><i class="bi bi-info-circle text-primary me-1"></i> <?= e($rec['caution']) ?></div>
                    <div class="mt-1 text-secondary"><i class="bi bi-file-earmark-check me-1"></i> <?= e($rec['verification_required']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="glass-card p-4 bg-light text-center border mt-4">
    <p class="text-muted small mb-0"><?= e($recResult['disclaimer']) ?></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
