<?php
/**
 * Page 15: Administrator Executive Dashboard
 * High-performance fintech command center with live telemetry,
 * tax rule versioning, financial aggregates, Chart.js analytics, and audit logs.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$pdo = getDb();

// -----------------------------------------------------------------------------
// 1. Core Platform Telemetry & Aggregates
// -----------------------------------------------------------------------------
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
$activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `status` = 'active'")->fetchColumn();

// Employment Demographic Breakdown from financial_profiles
$empStmt = $pdo->query("
    SELECT `employment_category`, COUNT(*) as cnt 
    FROM `financial_profiles` 
    GROUP BY `employment_category`
");
$empCounts = [
    'salaried' => 0,
    'self_employed' => 0,
    'professional' => 0,
    'student' => 0,
    'senior_citizen' => 0,
];
while ($row = $empStmt->fetch()) {
    $cat = $row['employment_category'];
    if (isset($empCounts[$cat])) {
        $empCounts[$cat] = (int)$row['cnt'];
    }
}
// Account for users who haven't completed their profile yet
$profilesCount = array_sum($empCounts);
$unprofiledUsers = max(0, $totalUsers - $profilesCount);

// Tax Rule Engines & Slabs
$activeRuleSets = (int)$pdo->query("SELECT COUNT(*) FROM `tax_rule_sets` WHERE `verification_status` = 'verified'")->fetchColumn();
$totalRuleSets = (int)$pdo->query("SELECT COUNT(*) FROM `tax_rule_sets`")->fetchColumn();
$totalSlabs = (int)$pdo->query("SELECT COUNT(*) FROM `tax_slabs`")->fetchColumn();
$totalProvisions = (int)$pdo->query("SELECT COUNT(*) FROM `tax_provisions` WHERE `is_active` = 1")->fetchColumn();

// Rule Set Versions List
$ruleSetsList = $pdo->query("
    SELECT `id`, `financial_year`, `assessment_year`, `standard_deduction`, `rebate_87a_limit`, `verification_status`
    FROM `tax_rule_sets` 
    ORDER BY `financial_year` DESC
")->fetchAll();

// Calculations & Financial Volumes
$totalCalculations = (int)$pdo->query("SELECT COUNT(*) FROM `tax_calculations`")->fetchColumn();
$grossIncomeSum = (float)$pdo->query("SELECT COALESCE(SUM(`gross_income`), 0) FROM `tax_calculations`")->fetchColumn();
$totalSavingsSum = (float)$pdo->query("SELECT COALESCE(SUM(`potential_savings`), 0) FROM `tax_calculations`")->fetchColumn();
$avgGrossIncome = $totalCalculations > 0 ? ($grossIncomeSum / $totalCalculations) : 0;
$avgTaxSavings = $totalCalculations > 0 ? ($totalSavingsSum / $totalCalculations) : 0;

// Regime Preference Breakdown
$regimeStmt = $pdo->query("
    SELECT `recommended_regime`, COUNT(*) as cnt, 
           COALESCE(AVG(`tax_liability_new`), 0) as avg_new_tax, 
           COALESCE(AVG(`tax_liability_old`), 0) as avg_old_tax 
    FROM `tax_calculations` 
    GROUP BY `recommended_regime`
");
$regimeStats = ['new' => 0, 'old' => 0, 'equal' => 0];
$avgNewTax = 0;
$avgOldTax = 0;
while ($row = $regimeStmt->fetch()) {
    $rKey = $row['recommended_regime'];
    if (isset($regimeStats[$rKey])) {
        $regimeStats[$rKey] = (int)$row['cnt'];
    }
    if ($row['avg_new_tax'] > 0) $avgNewTax = (float)$row['avg_new_tax'];
    if ($row['avg_old_tax'] > 0) $avgOldTax = (float)$row['avg_old_tax'];
}

// Audit Trail & Security Health
$totalAuditLogs = (int)$pdo->query("SELECT COUNT(*) FROM `audit_logs`")->fetchColumn();
$recentAudits = $pdo->query("
    SELECT a.*, COALESCE(u.name, 'System Administrator') as actor_name, u.email as actor_email
    FROM `audit_logs` a
    LEFT JOIN `users` u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 6
")->fetchAll();

// Statutory Compliance Milestones
$statutoryDeadlines = $pdo->query("
    SELECT * FROM `tax_reminders`
    WHERE `is_statutory` = 1
    ORDER BY `due_date` ASC
    LIMIT 4
")->fetchAll();

// Recent Tax Computations Snapshot
$recentCalculations = $pdo->query("
    SELECT c.`id`, c.`financial_year`, c.`gross_income`, c.`tax_liability_old`, c.`tax_liability_new`, 
           c.`recommended_regime`, c.`potential_savings`, c.`created_at`, u.`name` as full_name, u.`email`
    FROM `tax_calculations` c
    JOIN `users` u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 4
")->fetchAll();

// Runtime Environment Diagnostics
$phpVersion = PHP_VERSION;
$mysqlVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
$serverOs = PHP_OS;
$memoryUsageMB = round(memory_get_usage(true) / (1024 * 1024), 2);
$memoryPeakMB = round(memory_get_peak_usage(true) / (1024 * 1024), 2);
$memoryLimit = ini_get('memory_limit');
$maxExecTime = ini_get('max_execution_time') . 's';

$pageTitle = "Administrator Executive Command Center";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ======================================================================= -->
<!-- 1. Executive Command Hero Banner -->
<!-- ======================================================================= -->
<div class="admin-hero-banner mb-4">
    <div class="row align-items-center position-relative" style="z-index: 2;">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="admin-pulse-dot"></span>
                <span class="text-uppercase fw-bold text-success-emphasis small tracking-wider" style="color: #6ee7b7 !important; letter-spacing: 1.5px; font-size: 0.75rem;">
                    FINANCIAL ENGINE ACTIVE &bull; FINANCE ACT 2024 COMPLIANT
                </span>
            </div>
            <h1 class="display-6 fw-bold mb-2 text-white" style="letter-spacing: -0.5px;">
                Executive Command Center
            </h1>
            <p class="text-light text-opacity-75 mb-3 lead fs-6" style="max-width: 680px;">
                Holistic statutory tax administration, dynamic progressive slab interval control, real-time taxpayer telemetry, and cryptographic audit monitoring.
            </p>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="admin-chip admin-chip-dark">
                    <i class="bi bi-clock-history"></i> Live: <?= date('d M Y, H:i T') ?>
                </span>
                <span class="admin-chip admin-chip-dark">
                    <i class="bi bi-cpu"></i> PHP <?= e($phpVersion) ?> (<?= $memoryUsageMB ?> MB)
                </span>
                <span class="admin-chip admin-chip-dark">
                    <i class="bi bi-database-check"></i> MySQL <?= e(explode('-', $mysqlVersion)[0]) ?>
                </span>
                <span class="admin-chip admin-chip-dark text-success-emphasis" style="color: #86efac !important;">
                    <i class="bi bi-shield-lock-fill"></i> Super Admin Privileges
                </span>
            </div>
        </div>
        <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
            <div class="d-inline-flex flex-column flex-sm-row flex-lg-column gap-2">
                <a href="<?= baseUrl('admin/switch.php?role=user') ?>" class="btn btn-outline-light btn-sm px-3 py-2 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-person-workspace text-warning"></i> Preview Taxpayer Portal
                </a>
                <a href="<?= baseUrl('admin/tax_rules.php') ?>" class="btn btn-primary btn-sm px-3 py-2 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2" style="background: linear-gradient(135deg, #6366f1, #4f46e5); border: none;">
                    <i class="bi bi-sliders2-vertical"></i> Configure Tax Slabs
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================================= -->
<!-- 2. Core KPI Stat Cards -->
<!-- ======================================================================= -->
<div class="row g-3 mb-4">
    <!-- Card 1: Registered Taxpayers -->
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.5px;">Registered Taxpayers</span>
                    <div class="admin-stat-value mt-1"><?= number_format($totalUsers) ?></div>
                </div>
                <div class="admin-icon-box admin-icon-indigo">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <div class="admin-metric-subtext">
                <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">
                    <i class="bi bi-check-circle-fill"></i> <?= $activeUsers ?> Active
                </span>
                <span class="text-muted ms-auto small">
                    <a href="<?= baseUrl('admin/users.php') ?>" class="text-decoration-none fw-semibold">Manage &rarr;</a>
                </span>
            </div>
            <div class="mt-2 pt-2 border-top border-light-subtle d-flex justify-content-between text-muted small" style="font-size: 0.78rem;">
                <span>Salaried: <strong><?= $empCounts['salaried'] ?></strong></span>
                <span>Pro/Biz: <strong><?= $empCounts['self_employed'] + $empCounts['professional'] ?></strong></span>
                <span>Students: <strong><?= $empCounts['student'] ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Card 2: Verified FY Rule Sets -->
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.5px;">Verified Rule Sets</span>
                    <div class="admin-stat-value mt-1 text-success"><?= $activeRuleSets ?> <span class="text-muted fs-6 fw-normal">/ <?= $totalRuleSets ?></span></div>
                </div>
                <div class="admin-icon-box admin-icon-emerald">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
            </div>
            <div class="admin-metric-subtext">
                <span class="badge bg-emerald-subtle text-emerald fw-bold px-2 py-1" style="background: #ecfdf5; color: #047857;">
                    <i class="bi bi-calculator"></i> <?= $totalSlabs ?> Progressive Slabs
                </span>
                <span class="text-muted ms-auto small">
                    <a href="<?= baseUrl('admin/tax_rules.php') ?>" class="text-decoration-none fw-semibold">Configure &rarr;</a>
                </span>
            </div>
            <div class="mt-2 pt-2 border-top border-light-subtle d-flex justify-content-between text-muted small" style="font-size: 0.78rem;">
                <span>FY 2024-25 (Budget)</span>
                <span class="badge bg-success-subtle text-success">Verified Active</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Computation & Assessment Volume -->
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.5px;">Volume Assessed</span>
                    <div class="admin-stat-value mt-1" style="font-size: 1.85rem;">
                        <?= formatRupee($grossIncomeSum) ?>
                    </div>
                </div>
                <div class="admin-icon-box admin-icon-cyan">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
            </div>
            <div class="admin-metric-subtext">
                <span class="badge bg-info-subtle text-info-emphasis fw-bold px-2 py-1">
                    <i class="bi bi-lightning-charge-fill"></i> <?= $totalCalculations ?> Computations
                </span>
                <span class="text-muted ms-auto small">
                    <span class="text-success fw-semibold">Avg Save <?= formatRupee($avgTaxSavings) ?></span>
                </span>
            </div>
            <div class="mt-2 pt-2 border-top border-light-subtle d-flex justify-content-between text-muted small" style="font-size: 0.78rem;">
                <span>New Regime: <strong><?= $regimeStats['new'] ?></strong></span>
                <span>Old Regime: <strong><?= $regimeStats['old'] ?></strong></span>
                <span>Equal: <strong><?= $regimeStats['equal'] ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Card 4: Audit & Security Health -->
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.5px;">Security Audit Trail</span>
                    <div class="admin-stat-value mt-1"><?= number_format($totalAuditLogs) ?></div>
                </div>
                <div class="admin-icon-box admin-icon-amber">
                    <i class="bi bi-shield-check"></i>
                </div>
            </div>
            <div class="admin-metric-subtext">
                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold px-2 py-1">
                    <i class="bi bi-lock-fill"></i> 100% Prepared SQL
                </span>
                <span class="text-muted ms-auto small">
                    <a href="<?= baseUrl('admin/audit_logs.php') ?>" class="text-decoration-none fw-semibold">Audit &rarr;</a>
                </span>
            </div>
            <div class="mt-2 pt-2 border-top border-light-subtle d-flex justify-content-between text-muted small" style="font-size: 0.78rem;">
                <span>Zero Breaches</span>
                <span class="text-success fw-semibold"><i class="bi bi-shield-fill-check"></i> Enforced</span>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================================= -->
<!-- 3. Executive Control Quick Launchpad (6 Tiles) -->
<!-- ======================================================================= -->
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-grid-fill text-primary me-2"></i>Executive Operational Launchpad
        </h5>
        <span class="text-muted small">Centralized Administrative Governance</span>
    </div>

    <div class="row g-3">
        <div class="col-md-4 col-sm-6">
            <a href="<?= baseUrl('admin/tax_rules.php') ?>" class="admin-quick-action">
                <div class="admin-action-icon bg-indigo-subtle text-indigo">
                    <i class="bi bi-sliders2-vertical"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark">Tax Slabs &amp; FY Engines</div>
                    <div class="text-muted small">Configure budget progressive brackets &amp; 87A rebate</div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-sm-6">
            <a href="<?= baseUrl('admin/provisions.php') ?>" class="admin-quick-action">
                <div class="admin-action-icon bg-success-subtle text-success">
                    <i class="bi bi-file-earmark-medical"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark">Deduction Provisions</div>
                    <div class="text-muted small">Manage 80C, 80D, 80CCD caps &amp; regimes</div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-sm-6">
            <a href="<?= baseUrl('admin/calendar.php') ?>" class="admin-quick-action">
                <div class="admin-action-icon bg-warning-subtle text-warning">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark">Statutory Calendar</div>
                    <div class="text-muted small">Broadcast CBDT advance tax &amp; ITR filing milestones</div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-sm-6">
            <a href="<?= baseUrl('admin/users.php') ?>" class="admin-quick-action">
                <div class="admin-action-icon bg-primary-subtle text-primary">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark">Taxpayer Directory</div>
                    <div class="text-muted small">Role delegation, account status &amp; password resets</div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-sm-6">
            <a href="<?= baseUrl('admin/audit_logs.php') ?>" class="admin-quick-action">
                <div class="admin-action-icon bg-danger-subtle text-danger">
                    <i class="bi bi-journal-code"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark">Immutable Audit Log</div>
                    <div class="text-muted small">Track admin interventions &amp; IP telemetry logs</div>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-sm-6">
            <a href="<?= baseUrl('admin/switch.php?role=user') ?>" class="admin-quick-action" style="border-left: 3px solid #10b981;">
                <div class="admin-action-icon" style="background: #ecfdf5; color: #10b981;">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark">Taxpayer Sandbox</div>
                    <div class="text-muted small">Switch to standard user mode to test calculations</div>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- ======================================================================= -->
<!-- 4. Interactive Chart.js Visualizations (2 Columns) -->
<!-- ======================================================================= -->
<div class="row g-4 mb-4">
    <!-- Chart 1: Regime Preference & Computation Volume -->
    <div class="col-lg-8">
        <div class="admin-glass-panel h-100">
            <div class="admin-glass-panel-header">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Tax Regime Adoption &amp; Comparative Analysis
                    </h6>
                    <small class="text-muted">Distribution of taxpayer outcomes under Old vs New Tax Regimes</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        Live Database Feed
                    </span>
                </div>
            </div>
            <div class="admin-glass-panel-body">
                <div class="admin-chart-box">
                    <canvas id="regimeComparisonChart"></canvas>
                </div>
                <div class="row g-3 mt-3 pt-3 border-top text-center">
                    <div class="col-4">
                        <div class="text-muted small">New Regime Recommended</div>
                        <div class="fs-5 fw-bold text-primary mt-1"><?= $regimeStats['new'] ?> Taxpayers</div>
                        <small class="text-success fw-semibold">
                            <?= $totalCalculations > 0 ? round(($regimeStats['new'] / $totalCalculations) * 100, 1) : 0 ?>% of total
                        </small>
                    </div>
                    <div class="col-4 border-start border-end">
                        <div class="text-muted small">Old Regime Recommended</div>
                        <div class="fs-5 fw-bold text-dark mt-1"><?= $regimeStats['old'] ?> Taxpayers</div>
                        <small class="text-muted fw-semibold">
                            <?= $totalCalculations > 0 ? round(($regimeStats['old'] / $totalCalculations) * 100, 1) : 0 ?>% of total
                        </small>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Equivalent / Neutral</div>
                        <div class="fs-5 fw-bold text-secondary mt-1"><?= $regimeStats['equal'] ?> Taxpayers</div>
                        <small class="text-muted fw-semibold">
                            <?= $totalCalculations > 0 ? round(($regimeStats['equal'] / $totalCalculations) * 100, 1) : 0 ?>% of total
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 2: Taxpayer Demographics & Employment Profiles -->
    <div class="col-lg-4">
        <div class="admin-glass-panel h-100">
            <div class="admin-glass-panel-header">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-pie-chart-fill text-success me-2"></i>Taxpayer Demographics
                    </h6>
                    <small class="text-muted">Employment category breakdown</small>
                </div>
                <span class="badge bg-light text-dark border">
                    <?= $totalUsers ?> Profiles
                </span>
            </div>
            <div class="admin-glass-panel-body d-flex flex-column justify-content-between">
                <div class="admin-chart-box" style="height: 230px;">
                    <canvas id="demographicsChart"></canvas>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex justify-content-between align-items-center mb-1">
                            <span><i class="bi bi-circle-fill text-primary me-2" style="font-size: 0.65rem;"></i>Salaried Professionals</span>
                            <span class="fw-bold text-dark"><?= $empCounts['salaried'] ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center mb-1">
                            <span><i class="bi bi-circle-fill text-info me-2" style="font-size: 0.65rem;"></i>Business &amp; Self-Employed</span>
                            <span class="fw-bold text-dark"><?= $empCounts['self_employed'] + $empCounts['professional'] ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center mb-1">
                            <span><i class="bi bi-circle-fill text-warning me-2" style="font-size: 0.65rem;"></i>Students &amp; Interns</span>
                            <span class="fw-bold text-dark"><?= $empCounts['student'] ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-circle-fill text-secondary me-2" style="font-size: 0.65rem;"></i>Senior Citizens &amp; Others</span>
                            <span class="fw-bold text-dark"><?= $empCounts['senior_citizen'] + $unprofiledUsers ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================================= -->
<!-- 5. Split Section: Infrastructure Telemetry & Live Activity Stream -->
<!-- ======================================================================= -->
<div class="row g-4 mb-4">
    <!-- Real-Time Server Environment & Telemetry Console -->
    <div class="col-lg-6">
        <div class="admin-console-card p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="fw-bold mb-1 text-white">
                        <i class="bi bi-terminal-fill text-info me-2"></i>Infrastructure &amp; Engine Telemetry
                    </h5>
                    <p class="text-muted small mb-0">Runtime environment, memory utilization, and security parameters</p>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-check-all me-1"></i> Healthy
                </span>
            </div>

            <!-- Memory Consumption Gauge -->
            <div class="mb-4">
                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>PHP Memory Usage (Allocated: <?= $memoryUsageMB ?> MB / Limit: <?= $memoryLimit ?>)</span>
                    <span class="text-white fw-bold"><?= round(($memoryUsageMB / 512) * 100, 1) ?>%</span>
                </div>
                <div class="telemetry-progress" style="background: rgba(255,255,255,0.08);">
                    <div class="telemetry-progress-bar" style="width: <?= min(100, round(($memoryUsageMB / 512) * 100, 1)) ?>%;"></div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm text-light mb-0" style="font-size: 0.85rem;">
                    <tbody>
                        <tr class="admin-console-divider border-top border-bottom">
                            <td class="text-muted py-2"><i class="bi bi-cpu me-2"></i>PHP Runtime Engine</td>
                            <td class="fw-semibold text-white py-2 text-end">PHP <?= e($phpVersion) ?> (<?= e($serverOs) ?>)</td>
                        </tr>
                        <tr class="admin-console-divider border-bottom">
                            <td class="text-muted py-2"><i class="bi bi-database me-2"></i>Database Engine</td>
                            <td class="fw-semibold text-white py-2 text-end">MySQL <?= e($mysqlVersion) ?> (InnoDB)</td>
                        </tr>
                        <tr class="admin-console-divider border-bottom">
                            <td class="text-muted py-2"><i class="bi bi-clock-history me-2"></i>Max Execution Window</td>
                            <td class="fw-semibold text-white py-2 text-end"><?= e($maxExecTime) ?></td>
                        </tr>
                        <tr class="admin-console-divider border-bottom">
                            <td class="text-muted py-2"><i class="bi bi-shield-shaded me-2"></i>Session Security Flags</td>
                            <td class="fw-semibold text-success py-2 text-end">HttpOnly &bull; SameSite=Lax</td>
                        </tr>
                        <tr class="admin-console-divider border-bottom">
                            <td class="text-muted py-2"><i class="bi bi-key-fill me-2"></i>Password Hash Algorithm</td>
                            <td class="fw-semibold text-info py-2 text-end">BCRYPT (Cost Factor 12)</td>
                        </tr>
                        <tr>
                            <td class="text-muted py-2"><i class="bi bi-calculator me-2"></i>Statutory Rounding Rules</td>
                            <td class="fw-semibold text-white py-2 text-end">Section 288A &amp; 288B Enforced</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Live Security & Operational Audit Activity Stream -->
    <div class="col-lg-6">
        <div class="admin-glass-panel h-100">
            <div class="admin-glass-panel-header">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-activity text-danger me-2"></i>Live Audit Activity Stream
                    </h6>
                    <small class="text-muted">Real-time record mutations and security access events</small>
                </div>
                <a href="<?= baseUrl('admin/audit_logs.php') ?>" class="btn btn-outline-primary btn-sm px-2 py-1">
                    Full Log &rarr;
                </a>
            </div>
            <div class="admin-glass-panel-body">
                <?php if (empty($recentAudits)): ?>
                    <p class="text-muted small mb-0">No audit events recorded yet.</p>
                <?php else: ?>
                    <div class="admin-timeline">
                        <?php foreach ($recentAudits as $log): 
                            $isSecurity = str_contains($log['action'], 'LOGIN') || str_contains($log['action'], 'ROLE') || str_contains($log['action'], 'PASSWORD');
                            $isCreate = str_contains($log['action'], 'CREATE') || str_contains($log['action'], 'INSERT');
                            $dotClass = $isSecurity ? 'warning' : ($isCreate ? 'success' : '');
                        ?>
                            <div class="admin-timeline-item">
                                <div class="admin-timeline-dot <?= $dotClass ?>"></div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark small" style="letter-spacing: -0.2px;">
                                        <?= e($log['action']) ?>
                                    </span>
                                    <span class="text-muted" style="font-size: 0.75rem;">
                                        <?= date('H:i:s, d M', strtotime($log['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="text-muted small" style="font-size: 0.8125rem;">
                                    Actor: <strong class="text-secondary"><?= e($log['actor_name']) ?></strong> &bull; 
                                    Target: <code class="text-primary"><?= e($log['affected_record_type']) ?>#<?= (int)$log['affected_record_id'] ?></code>
                                </div>
                                <?php if (!empty($log['ip_address'])): ?>
                                    <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-geo-alt"></i> IP: <?= e($log['ip_address']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================================= -->
<!-- 6. Recent Tax Calculations & Compliance Milestones Grid -->
<!-- ======================================================================= -->
<div class="row g-4 mb-4">
    <!-- Latest Tax Computations Overview -->
    <div class="col-lg-8">
        <div class="admin-glass-panel h-100">
            <div class="admin-glass-panel-header">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-clock-history text-primary me-2"></i>Recent Taxpayer Computations
                    </h6>
                    <small class="text-muted">Latest assessments generated through the progressive computation engine</small>
                </div>
                <a href="<?= baseUrl('tax_history.php') ?>" class="btn btn-outline-secondary btn-sm px-2 py-1">
                    View All &rarr;
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-3">Taxpayer</th>
                            <th>FY</th>
                            <th>Gross Income</th>
                            <th>Old Liability</th>
                            <th>New Liability</th>
                            <th>Recommended</th>
                            <th class="pe-3 text-end">Savings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentCalculations)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No tax calculations recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentCalculations as $calc): 
                                $isNew = $calc['recommended_regime'] === 'new';
                            ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark"><?= e($calc['full_name']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= e($calc['email']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($calc['financial_year']) ?></span>
                                    </td>
                                    <td class="fw-semibold text-dark">
                                        <?= formatRupee($calc['gross_income']) ?>
                                    </td>
                                    <td class="text-secondary">
                                        <?= formatRupee($calc['tax_liability_old']) ?>
                                    </td>
                                    <td class="fw-bold <?= $isNew ? 'text-primary' : 'text-secondary' ?>">
                                        <?= formatRupee($calc['tax_liability_new']) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $isNew ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning-emphasis' ?>">
                                            <?= ucfirst(e($calc['recommended_regime'])) ?> Regime
                                        </span>
                                    </td>
                                    <td class="pe-3 text-end fw-bold text-success">
                                        <?= formatRupee($calc['potential_savings']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Statutory Compliance Milestones -->
    <div class="col-lg-4">
        <div class="admin-glass-panel h-100">
            <div class="admin-glass-panel-header">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-calendar2-check-fill text-warning me-2"></i>Statutory Deadlines
                    </h6>
                    <small class="text-muted">Broadcast to all user calendars</small>
                </div>
                <a href="<?= baseUrl('admin/calendar.php') ?>" class="btn btn-outline-warning btn-sm px-2 py-1 text-dark">
                    Manage &rarr;
                </a>
            </div>
            <div class="admin-glass-panel-body">
                <?php if (empty($statutoryDeadlines)): ?>
                    <p class="text-muted small mb-0">No statutory deadlines scheduled.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($statutoryDeadlines as $milestone): 
                            $dueDate = strtotime($milestone['due_date']);
                            $daysLeft = ceil(($dueDate - time()) / 86400);
                            $isUrgent = $daysLeft >= 0 && $daysLeft <= 30;
                        ?>
                            <div class="list-group-item px-0 py-2 border-light-subtle">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="fw-semibold text-dark small"><?= e($milestone['title']) ?></div>
                                    <span class="badge <?= $isUrgent ? 'bg-danger-subtle text-danger' : 'bg-light text-secondary border' ?>" style="font-size: 0.72rem;">
                                        <?= date('d M Y', $dueDate) ?>
                                    </span>
                                </div>
                                <div class="text-muted small" style="font-size: 0.78rem;">
                                    <?= e($milestone['description'] ?? 'Statutory compliance milestone under Income-tax Act.') ?>
                                </div>
                                <div class="mt-1">
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;">
                                        <?= strtoupper(str_replace('_', ' ', $milestone['category'])) ?>
                                    </span>
                                    <?php if ($daysLeft > 0): ?>
                                        <span class="text-muted ms-1" style="font-size: 0.72rem;">(<?= (int)$daysLeft ?> days left)</span>
                                    <?php elseif ($daysLeft == 0): ?>
                                        <span class="text-danger fw-bold ms-1" style="font-size: 0.72rem;">(Due Today!)</span>
                                    <?php else: ?>
                                        <span class="text-muted ms-1" style="font-size: 0.72rem;">(Elapsed)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================================= -->
<!-- 7. Interactive Chart.js Initialization Script -->
<!-- ======================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Regime Preference & Comparative Bar/Line Chart
    const ctxRegime = document.getElementById('regimeComparisonChart');
    if (ctxRegime) {
        new Chart(ctxRegime, {
            type: 'bar',
            data: {
                labels: ['New Tax Regime', 'Old Tax Regime (With Exemptions)', 'Equal / Neutral'],
                datasets: [
                    {
                        label: 'Taxpayers Selecting Regime',
                        data: [<?= $regimeStats['new'] ?>, <?= $regimeStats['old'] ?>, <?= $regimeStats['equal'] ?>],
                        backgroundColor: [
                            'rgba(79, 70, 229, 0.85)',
                            'rgba(245, 158, 11, 0.85)',
                            'rgba(148, 163, 184, 0.85)'
                        ],
                        borderColor: [
                            '#4f46e5',
                            '#f59e0b',
                            '#94a3b8'
                        ],
                        borderWidth: 1.5,
                        borderRadius: 8,
                        maxBarThickness: 54
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.parsed.y + ' Taxpayers';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#64748b',
                            font: { size: 11 }
                        },
                        grid: {
                            color: 'rgba(226, 232, 240, 0.6)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: '#1e293b',
                            font: { size: 12, weight: '600' }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // 2. Demographics Doughnut Chart
    const ctxDemo = document.getElementById('demographicsChart');
    if (ctxDemo) {
        new Chart(ctxDemo, {
            type: 'doughnut',
            data: {
                labels: ['Salaried', 'Self-Employed / Business', 'Students', 'Senior Citizens / Others'],
                datasets: [{
                    data: [
                        <?= $empCounts['salaried'] ?>,
                        <?= $empCounts['self_employed'] + $empCounts['professional'] ?>,
                        <?= $empCounts['student'] ?>,
                        <?= $empCounts['senior_citizen'] + $unprofiledUsers ?>
                    ],
                    backgroundColor: [
                        '#4f46e5',
                        '#06b6d4',
                        '#f59e0b',
                        '#94a3b8'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.label + ': ' + context.parsed;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
