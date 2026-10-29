<?php
/**
 * Page 3: Main Financial Dashboard
 * Real-time metric cards, interactive Chart.js visualizations, reminders, and historical snapshots.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/IncomeRepository.php';
require_once __DIR__ . '/repositories/DeductionRepository.php';
require_once __DIR__ . '/repositories/TaxCalculationRepository.php';
require_once __DIR__ . '/repositories/ReminderRepository.php';
require_once __DIR__ . '/services/TaxComparisonService.php';

requireAuth();

$userId = $_SESSION['user_id'];
$userRepo = new UserRepository();
$incomeRepo = new IncomeRepository();
$deductionRepo = new DeductionRepository();
$calcRepo = new TaxCalculationRepository();
$reminderRepo = new ReminderRepository();
$comparisonService = new TaxComparisonService();

// Current user profile & FY
$profile = $userRepo->findById($userId);
$activeFy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';
$_SESSION['active_fy'] = $activeFy;

// Load real database metrics
$incomeTotals = $incomeRepo->getTotals($userId, $activeFy);
$deductions = $deductionRepo->getByUserAndFy($userId, $activeFy);
$latestCalc = $calcRepo->getLatestCalculation($userId, $activeFy);
$reminders = $reminderRepo->getRemindersForUser($userId, $activeFy);

// Compute live comparison if income exists
$hasData = ($incomeTotals['total_gross'] > 0);
$liveComparison = null;
if ($hasData) {
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
    $liveComparison = $comparisonService->compare($calcParams);
}

// Deduction breakdown for chart
$deductionLabels = [];
$deductionValues = [];
$totalEnteredDeductions = 0.0;
foreach ($deductions as $d) {
    $deductionLabels[] = $d['section_code'];
    $deductionValues[] = (float)$d['amount_entered'];
    $totalEnteredDeductions += (float)$d['amount_entered'];
}

$pageTitle = "Dashboard";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Financial &amp; Tax Overview</h3>
        <p class="text-muted small mb-0">Welcome back, <strong><?= e($profile['name']) ?></strong> &bull; Employment: <span class="badge bg-light text-dark border"><?= e(ucfirst(str_replace('_', ' ', $profile['employment_category'] ?? 'Salaried'))) ?></span></p>
    </div>

    <!-- Financial Year Switcher -->
    <div class="d-flex align-items-center gap-2">
        <label for="fySelect" class="form-label small fw-semibold text-muted mb-0">Financial Year:</label>
        <select id="fySelect" class="form-select form-select-sm shadow-sm" style="width: 140px;" onchange="location.href='?fy='+this.value">
            <option value="2024-25" <?= $activeFy === '2024-25' ? 'selected' : '' ?>>FY 2024-25</option>
            <option value="2023-24" <?= $activeFy === '2023-24' ? 'selected' : '' ?>>FY 2023-24</option>
            <option value="2025-26" <?= $activeFy === '2025-26' ? 'selected' : '' ?>>FY 2025-26</option>
        </select>
    </div>
</div>

<?php if (!$hasData): ?>
    <!-- Empty State for new accounts -->
    <div class="glass-card p-5 text-center mb-4">
        <div class="stat-icon bg-primary-subtle text-primary mx-auto mb-3" style="width:64px;height:64px;font-size:2rem;">
            <i class="bi bi-wallet2"></i>
        </div>
        <h4 class="fw-bold">No Financial Records Found for FY <?= e($activeFy) ?></h4>
        <p class="text-muted mx-auto max-w-md mb-4">
            Get started by logging your annual salary or income sources. Smart Tax Planning Assistance will automatically calculate your taxable income and recommend the optimal tax regime.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="<?= baseUrl('income.php') ?>" class="btn btn-primary px-4">
                <i class="bi bi-plus-circle me-1"></i> Add Income Details
            </a>
            <a href="<?= baseUrl('tax_calculator.php') ?>" class="btn btn-outline-secondary px-4">
                <i class="bi bi-calculator me-1"></i> Try Interactive Calculator
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Top Financial Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Gross Income -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Annual Gross Income</span>
                    <div class="stat-icon bg-primary-subtle text-primary" style="width:40px;height:40px;font-size:1.2rem;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
                <div class="stat-val"><?= formatInr($incomeTotals['total_gross']) ?></div>
                <div class="text-muted small mt-2 d-flex align-items-center">
                    <i class="bi bi-info-circle me-1 text-primary"></i> Across all 5 heads
                </div>
            </div>
        </div>

        <!-- Card 2: Recommended Tax Regime & Liability -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Recommended Tax</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <?= strtoupper($liveComparison['recommended_regime'] ?? 'NEW') ?> REGIME
                    </span>
                </div>
                <div class="stat-val text-dark">
                    <?= formatInr($liveComparison['recommended_regime'] === 'old' ? $liveComparison['old_regime']['total_tax_liability'] : $liveComparison['new_regime']['total_tax_liability']) ?>
                </div>
                <div class="text-success small mt-2 fw-semibold">
                    <i class="bi bi-arrow-down-circle-fill me-1"></i> Saves <?= formatInr($liveComparison['savings_amount']) ?> vs other
                </div>
            </div>
        </div>

        <!-- Card 3: Deductions & Exemptions -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Recorded Deductions</span>
                    <div class="stat-icon bg-warning-subtle text-warning" style="width:40px;height:40px;font-size:1.2rem;">
                        <i class="bi bi-piggy-bank"></i>
                    </div>
                </div>
                <div class="stat-val"><?= formatInr($totalEnteredDeductions) ?></div>
                <div class="text-muted small mt-2">
                    Eligible (Old): <strong><?= formatInr($liveComparison['old_regime']['total_eligible_deductions'] ?? 0) ?></strong>
                </div>
            </div>
        </div>

        <!-- Card 4: Taxes Paid & Balance -->
        <div class="col-sm-6 col-xl-3">
            <?php 
                $recLiability = ($liveComparison['recommended_regime'] === 'old') 
                    ? (float)$liveComparison['old_regime']['total_tax_liability'] 
                    : (float)$liveComparison['new_regime']['total_tax_liability'];
                $paid = (float)$incomeTotals['total_tds'] + (float)$incomeTotals['total_advance_tax'];
                $diff = $recLiability - $paid;
            ?>
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase"><?= ($diff > 0) ? 'Estimated Tax Payable' : 'Estimated Refund' ?></span>
                    <div class="stat-icon <?= ($diff > 0) ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>" style="width:40px;height:40px;font-size:1.2rem;">
                        <i class="bi <?= ($diff > 0) ? 'bi-credit-card-2-front' : 'bi-arrow-down-left-circle' ?>"></i>
                    </div>
                </div>
                <div class="stat-val <?= ($diff > 0) ? 'text-danger' : 'text-success' ?>">
                    <?= formatInr(abs($diff)) ?>
                </div>
                <div class="text-muted small mt-2">
                    TDS/Advance Paid: <?= formatInr($paid) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparative Regime Banner -->
    <div class="alert alert-info border-info-subtle shadow-sm p-3 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-lightbulb-fill text-primary fs-3"></i>
            <div>
                <div class="fw-bold text-dark">Regime Recommendation for FY <?= e($activeFy) ?></div>
                <div class="text-muted small"><?= $liveComparison['explanation'] ?></div>
            </div>
        </div>
        <a href="<?= baseUrl('regime_comparison.php') ?>" class="btn btn-sm btn-primary text-nowrap">
            View Side-by-Side Analysis <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Chart 1: Regime Comparison Bar Chart -->
        <div class="col-lg-6">
            <div class="glass-card p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Tax Liability: Old vs New Regime</h5>
                    <span class="badge bg-light text-muted border">FY <?= e($activeFy) ?></span>
                </div>
                <div style="height: 280px;">
                    <canvas id="regimeComparisonChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Income Distribution by Category -->
        <div class="col-lg-6">
            <div class="glass-card p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Income Distribution by Source</h5>
                    <a href="<?= baseUrl('income.php') ?>" class="text-decoration-none small text-primary fw-semibold">Manage Income</a>
                </div>
                <div style="height: 280px;">
                    <canvas id="incomeDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Lower Section: Deductions Chart + Tax Reminders -->
    <div class="row g-4 mb-4">
        <!-- Deductions Breakdown -->
        <div class="col-lg-7">
            <div class="glass-card p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Recorded Deductions by Section</h5>
                    <a href="<?= baseUrl('deductions.php') ?>" class="text-decoration-none small text-primary fw-semibold">View All</a>
                </div>
                <?php if (empty($deductions)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 text-secondary mb-2 d-block"></i>
                        No deductions recorded yet. Add your 80C, 80D, or NPS details to optimize Old Regime tax.
                    </div>
                <?php else: ?>
                    <div style="height: 260px;">
                        <canvas id="deductionsChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Tax Reminders -->
        <div class="col-lg-5">
            <div class="glass-card p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Statutory Tax Reminders</h5>
                    <a href="<?= baseUrl('reminders.php') ?>" class="text-decoration-none small text-primary fw-semibold">Calendar</a>
                </div>
                <div class="list-group list-group-flush">
                    <?php 
                        $displayReminders = array_slice($reminders, 0, 4);
                        if (empty($displayReminders)):
                    ?>
                        <div class="text-muted small py-3 text-center">No upcoming reminders for this period.</div>
                    <?php else: ?>
                        <?php foreach ($displayReminders as $rem): ?>
                            <div class="list-group-item px-0 py-2 border-0 border-bottom d-flex align-items-start gap-2">
                                <i class="bi bi-bell-fill text-warning mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small text-dark"><?= e($rem['title']) ?></div>
                                    <div class="text-muted small" style="font-size:0.75rem;">
                                        Due Date: <strong><?= date('d M Y', strtotime($rem['due_date'])) ?></strong> 
                                        &bull; <span class="badge bg-light text-dark border"><?= e(ucfirst(str_replace('_', ' ', $rem['category']))) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Chart.js Configuration -->
<?php if ($hasData): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Regime Comparison Bar Chart
    const ctxRegime = document.getElementById('regimeComparisonChart');
    if (ctxRegime) {
        new Chart(ctxRegime, {
            type: 'bar',
            data: {
                labels: ['Old Tax Regime', 'New Tax Regime'],
                datasets: [{
                    label: 'Estimated Tax Liability (₹)',
                    data: [
                        <?= (float)$liveComparison['old_regime']['total_tax_liability'] ?>,
                        <?= (float)$liveComparison['new_regime']['total_tax_liability'] ?>
                    ],
                    backgroundColor: ['#0284c7', '#10b981'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' Tax: ' + formatInr(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + (value / 1000) + 'k'; }
                        }
                    }
                }
            }
        });
    }

    // 2. Income Distribution Doughnut Chart
    const ctxIncome = document.getElementById('incomeDistributionChart');
    if (ctxIncome) {
        new Chart(ctxIncome, {
            type: 'doughnut',
            data: {
                labels: ['Salary', 'House Property', 'Business/Profession', 'STCG', 'LTCG', 'Other Sources'],
                datasets: [{
                    data: [
                        <?= (float)($incomeTotals['salary'] ?? 0) ?>,
                        <?= (float)($incomeTotals['house_property'] ?? 0) ?>,
                        <?= (float)($incomeTotals['business_profession'] ?? 0) ?>,
                        <?= (float)($incomeTotals['capital_gains_stcg'] ?? 0) ?>,
                        <?= (float)($incomeTotals['capital_gains_ltcg'] ?? 0) ?>,
                        <?= (float)($incomeTotals['other_sources'] ?? 0) ?>
                    ],
                    backgroundColor: ['#0284c7', '#0f766e', '#f59e0b', '#8b5cf6', '#ec4899', '#64748b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.label + ': ' + formatInr(context.raw);
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Deductions Breakdown Bar Chart
    const ctxDeductions = document.getElementById('deductionsChart');
    if (ctxDeductions && <?= count($deductionLabels) ?> > 0) {
        new Chart(ctxDeductions, {
            type: 'bar',
            data: {
                labels: <?= json_encode($deductionLabels) ?>,
                datasets: [{
                    label: 'Amount (₹)',
                    data: <?= json_encode($deductionValues) ?>,
                    backgroundColor: '#0f766e',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) { return ' Entered: ' + formatInr(context.raw); }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + (value / 1000) + 'k'; }
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
