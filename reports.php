<?php
/**
 * Page 13: Comprehensive Tax Planning Reports
 * Printable audit-ready tax report with regime comparison and CSV exports.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/services/ReportService.php';

requireAuth();

$userId = $_SESSION['user_id'];
$activeFy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';
$_SESSION['active_fy'] = $activeFy;

$reportService = new ReportService();

try {
    $reportData = $reportService->generateReport($userId, $activeFy);
} catch (Exception $e) {
    setFlash('danger', 'Failed to generate report: ' . $e->getMessage());
    header('Location: ' . baseUrl('dashboard.php'));
    exit;
}

$u = $reportData['user'];
$comp = $reportData['comparison'];
$totals = $reportData['income_totals'];
$deductions = $reportData['deduction_records'];

$pageTitle = "Tax Planning Report";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h3 class="fw-bold mb-1">Tax Planning Executive Report</h3>
        <p class="text-muted small mb-0">Consolidated financial overview, regime comparison, and deduction schedule for FY <?= e($activeFy) ?>.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width: 140px;" onchange="location.href='?fy='+this.value">
            <option value="2024-25" <?= $activeFy === '2024-25' ? 'selected' : '' ?>>FY 2024-25</option>
            <option value="2023-24" <?= $activeFy === '2023-24' ? 'selected' : '' ?>>FY 2023-24</option>
            <option value="2025-26" <?= $activeFy === '2025-26' ? 'selected' : '' ?>>FY 2025-26</option>
        </select>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print / Save as PDF
        </button>
        <div class="dropdown">
            <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export Data
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item" href="<?= baseUrl('income.php?fy=' . $activeFy . '&action=export_csv') ?>"><i class="bi bi-filetype-csv me-2"></i>Income Records CSV</a></li>
                <li><a class="dropdown-item" href="<?= baseUrl('deductions.php?fy=' . $activeFy . '&action=export_csv') ?>"><i class="bi bi-filetype-csv me-2"></i>Deductions CSV</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Printable Report Paper Container -->
<div class="glass-card p-4 p-md-5 bg-white border shadow-sm">
    <!-- Header of Report -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-wallet2 text-primary fs-3"></i>
                <h4 class="fw-bold mb-0">Smart Tax Planning Assistance</h4>
            </div>
            <div class="text-muted small">Personal Income Tax Estimation &amp; Regime Advisory Report</div>
            <div class="text-muted small mt-1">Generated: <strong><?= e($reportData['generated_at']) ?></strong></div>
        </div>
        <div class="text-end">
            <span class="badge bg-primary fs-6 px-3 py-2 mb-1">FY <?= e($activeFy) ?></span>
            <div class="text-muted small">AY <?= e($comp['old_regime']['financial_year'] === '2024-25' ? '2025-26' : ($comp['old_regime']['financial_year'] === '2023-24' ? '2024-25' : '2026-27')) ?></div>
        </div>
    </div>

    <!-- Taxpayer Details Card -->
    <div class="row g-3 mb-4 p-3 bg-light rounded border">
        <div class="col-sm-6 col-md-3">
            <span class="text-muted small d-block">Taxpayer Name</span>
            <strong class="text-dark"><?= e($u['name']) ?></strong>
        </div>
        <div class="col-sm-6 col-md-3">
            <span class="text-muted small d-block">Employment Category</span>
            <strong class="text-dark"><?= e(ucfirst(str_replace('_', ' ', $u['employment_category']))) ?></strong>
        </div>
        <div class="col-sm-6 col-md-3">
            <span class="text-muted small d-block">Residential Status</span>
            <strong class="text-dark"><?= e(ucfirst($u['state'])) ?> &bull; Resident</strong>
        </div>
        <div class="col-sm-6 col-md-3">
            <span class="text-muted small d-block">Permanent Account No.</span>
            <strong class="text-dark"><?= e($u['pan']) ?></strong>
        </div>
    </div>

    <!-- Section 1: Executive Tax Summary -->
    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Executive Tax Summary &amp; Regime Recommendation</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="p-3 border rounded <?= $comp['recommended_regime'] === 'old' ? 'border-success bg-success-subtle' : 'bg-light' ?>">
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-bold">Old Tax Regime</span>
                    <?php if ($comp['recommended_regime'] === 'old'): ?>
                        <span class="badge bg-success">RECOMMENDED</span>
                    <?php endif; ?>
                </div>
                <div class="fs-4 fw-bold text-dark mb-1"><?= formatInr($comp['old_regime']['total_tax_liability']) ?></div>
                <div class="small text-muted">Net Taxable: <?= formatInr($comp['old_regime']['taxable_income']) ?> &bull; Eff Rate: <?= e($comp['old_regime']['effective_tax_rate']) ?>%</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 border rounded <?= $comp['recommended_regime'] === 'new' ? 'border-success bg-success-subtle' : 'bg-light' ?>">
                <div class="d-flex justify-content-between mb-1">
                    <span class="fw-bold">New Tax Regime (Sec 115BAC)</span>
                    <?php if ($comp['recommended_regime'] === 'new'): ?>
                        <span class="badge bg-success">RECOMMENDED</span>
                    <?php endif; ?>
                </div>
                <div class="fs-4 fw-bold text-dark mb-1"><?= formatInr($comp['new_regime']['total_tax_liability']) ?></div>
                <div class="small text-muted">Net Taxable: <?= formatInr($comp['new_regime']['taxable_income']) ?> &bull; Eff Rate: <?= e($comp['new_regime']['effective_tax_rate']) ?>%</div>
            </div>
        </div>
    </div>

    <!-- Section 2: Detailed Regime Comparison Table -->
    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Comparative Tax Breakdown</h5>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm table-financial align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tax Parameter</th>
                    <th class="text-end">Old Tax Regime</th>
                    <th class="text-end">New Tax Regime</th>
                    <th class="text-end">Variance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Gross Total Income (All Sources)</td>
                    <td class="text-end"><?= formatInr($comp['old_regime']['gross_income']) ?></td>
                    <td class="text-end"><?= formatInr($comp['new_regime']['gross_income']) ?></td>
                    <td class="text-end text-muted">₹0</td>
                </tr>
                <tr>
                    <td>Standard Deduction on Salary</td>
                    <td class="text-end"><?= formatInr($comp['old_regime']['standard_deduction']) ?></td>
                    <td class="text-end fw-semibold text-success"><?= formatInr($comp['new_regime']['standard_deduction']) ?></td>
                    <td class="text-end text-success">+<?= formatInr($comp['new_regime']['standard_deduction'] - $comp['old_regime']['standard_deduction']) ?></td>
                </tr>
                <tr>
                    <td>Chapter VI-A Deductions &amp; Exemptions</td>
                    <td class="text-end text-success"><?= formatInr($comp['old_regime']['applicable_deductions']) ?></td>
                    <td class="text-end"><?= formatInr($comp['new_regime']['applicable_deductions']) ?></td>
                    <td class="text-end text-muted">-<?= formatInr($comp['old_regime']['applicable_deductions'] - $comp['new_regime']['applicable_deductions']) ?></td>
                </tr>
                <tr class="table-light">
                    <td class="fw-bold">Taxable Income</td>
                    <td class="text-end fw-bold"><?= formatInr($comp['old_regime']['taxable_income']) ?></td>
                    <td class="text-end fw-bold"><?= formatInr($comp['new_regime']['taxable_income']) ?></td>
                    <td class="text-end text-primary"><?= formatInr(abs($comp['old_regime']['taxable_income'] - $comp['new_regime']['taxable_income'])) ?></td>
                </tr>
                <tr>
                    <td>Tax on Slabs</td>
                    <td class="text-end"><?= formatInr($comp['old_regime']['slab_tax']) ?></td>
                    <td class="text-end"><?= formatInr($comp['new_regime']['slab_tax']) ?></td>
                    <td class="text-end"><?= formatInr(abs($comp['old_regime']['slab_tax'] - $comp['new_regime']['slab_tax'])) ?></td>
                </tr>
                <tr>
                    <td>Rebate under Section 87A</td>
                    <td class="text-end text-success"><?= formatInr($comp['old_regime']['rebate_87a']) ?></td>
                    <td class="text-end text-success"><?= formatInr($comp['new_regime']['rebate_87a']) ?></td>
                    <td class="text-end">₹0</td>
                </tr>
                <tr>
                    <td>Health &amp; Education Cess (4%)</td>
                    <td class="text-end"><?= formatInr($comp['old_regime']['cess']) ?></td>
                    <td class="text-end"><?= formatInr($comp['new_regime']['cess']) ?></td>
                    <td class="text-end"><?= formatInr(abs($comp['old_regime']['cess'] - $comp['new_regime']['cess'])) ?></td>
                </tr>
                <tr class="table-primary fw-bold">
                    <td>Total Estimated Tax Liability</td>
                    <td class="text-end"><?= formatInr($comp['old_regime']['total_tax_liability']) ?></td>
                    <td class="text-end"><?= formatInr($comp['new_regime']['total_tax_liability']) ?></td>
                    <td class="text-end text-success">Diff: <?= formatInr($comp['savings_amount']) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Section 3: Tax Paid and Balance Payable/Refund -->
    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">3. Tax Credits &amp; Estimated Balance</h5>
    <?php 
        $recTax = ($comp['recommended_regime'] === 'old') 
            ? $comp['old_regime']['total_tax_liability'] 
            : $comp['new_regime']['total_tax_liability'];
        $paid = $totals['total_tds'] + $totals['total_advance_tax'];
        $balance = $recTax - $paid;
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 border rounded text-center bg-light">
                <span class="text-muted small d-block">Recommended Liability</span>
                <strong class="fs-5 text-dark"><?= formatInr($recTax) ?></strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded text-center bg-light">
                <span class="text-muted small d-block">Total Taxes Paid (TDS + Adv)</span>
                <strong class="fs-5 text-primary"><?= formatInr($paid) ?></strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 border rounded text-center <?= $balance > 0 ? 'bg-danger-subtle border-danger' : 'bg-success-subtle border-success' ?>">
                <span class="small d-block fw-semibold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                    <?= $balance > 0 ? 'Estimated Tax Payable' : 'Estimated Potential Refund' ?>
                </span>
                <strong class="fs-5 <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                    <?= formatInr(abs($balance)) ?>
                </strong>
            </div>
        </div>
    </div>

    <!-- Section 4: Recorded Deductions Audit -->
    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">4. Recorded Deductions &amp; Statutory Audit</h5>
    <?php if (empty($deductions)): ?>
        <p class="text-muted small">No deductions or tax-saving investments recorded for this financial year.</p>
    <?php else: ?>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered small">
                <thead class="table-light">
                    <tr>
                        <th>Section</th>
                        <th>Investment Category</th>
                        <th class="text-end">Amount Entered</th>
                        <th class="text-end">Eligible (Old)</th>
                        <th>Verification Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deductions as $d): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark border"><?= e($d['section_code']) ?></span></td>
                            <td><?= e($d['category']) ?></td>
                            <td class="text-end"><?= formatInr($d['amount_entered']) ?></td>
                            <td class="text-end fw-semibold text-success"><?= formatInr($d['eligible_amount']) ?></td>
                            <td><?= e(ucfirst(str_replace('_', ' ', $d['verification_status']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Legal Disclaimer on Report -->
    <div class="p-3 bg-light border rounded text-muted small mt-4" style="font-size:0.75rem;">
        <strong>Statutory Notice:</strong> <?= e($reportData['disclaimer']) ?> Income tax rules, slabs, and rebates are referenced from the official provisions of the Income Tax Act 1961 as amended by Finance (No. 2) Act 2024. Taxpayers must verify all figures with official Form 26AS, AIS, and Form 16 before filing an official tax return.
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
