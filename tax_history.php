<?php
/**
 * Page 12: Tax Calculation History
 * Searchable log of past calculation snapshots and saved What-If scenarios.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/repositories/TaxCalculationRepository.php';

requireAuth();

$userId = $_SESSION['user_id'];
$calcRepo = new TaxCalculationRepository();

$activeFy = $_GET['fy'] ?? null;
$history = $calcRepo->getHistory($userId, $activeFy, 100);
$scenarios = $calcRepo->getScenarios($userId);

$pageTitle = "Calculation History";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Tax Calculation History &amp; Scenarios</h3>
        <p class="text-muted small mb-0">Immutable snapshots of your past tax evaluations, regime comparisons, and simulations.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width: 150px;" onchange="location.href=this.value ? '?fy='+this.value : '?'">
            <option value="" <?= empty($activeFy) ? 'selected' : '' ?>>All Financial Years</option>
            <option value="2024-25" <?= $activeFy === '2024-25' ? 'selected' : '' ?>>FY 2024-25</option>
            <option value="2023-24" <?= $activeFy === '2023-24' ? 'selected' : '' ?>>FY 2023-24</option>
            <option value="2025-26" <?= $activeFy === '2025-26' ? 'selected' : '' ?>>FY 2025-26</option>
        </select>
        <a href="<?= baseUrl('tax_calculator.php') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-calculator me-1"></i> New Calculation
        </a>
    </div>
</div>

<!-- Tabs for Calculations vs Scenarios -->
<ul class="nav nav-pills mb-4" id="historyTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-semibold" id="calcs-tab" data-bs-toggle="tab" data-bs-target="#calcs" type="button" role="tab">
            <i class="bi bi-clock-history me-1"></i> Calculation Snapshots (<?= count($history) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="scenarios-tab" data-bs-toggle="tab" data-bs-target="#scenarios" type="button" role="tab">
            <i class="bi bi-sliders me-1"></i> Saved What-If Scenarios (<?= count($scenarios) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="historyTabContent">
    <!-- Tab 1: Calculation Snapshots -->
    <div class="tab-pane fade show active" id="calcs" role="tabpanel">
        <div class="glass-card bg-white border shadow-sm">
            <?php if (empty($history)): ?>
                <div class="empty-state py-5">
                    <div class="empty-state-icon"><i class="bi bi-clock-history"></i></div>
                    <h5 class="fw-bold">No Calculation Snapshots Found</h5>
                    <p class="text-muted small max-w-sm mx-auto mb-3">Whenever you calculate your tax liability in the Tax Calculator, click "Save to History" to preserve the snapshot here.</p>
                    <a href="<?= baseUrl('tax_calculator.php') ?>" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-calculator me-1"></i> Go to Tax Calculator
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-financial mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>FY</th>
                                <th>Gross Income</th>
                                <th>Old Regime Tax</th>
                                <th>New Regime Tax</th>
                                <th>Optimal Regime</th>
                                <th>Estimated Savings</th>
                                <th>Effective Rate</th>
                                <th class="text-end">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                                <tr>
                                    <td class="text-muted small text-nowrap"><?= date('d M Y, h:i A', strtotime($h['created_at'])) ?></td>
                                    <td><span class="badge bg-light text-dark border">FY <?= e($h['financial_year']) ?></span></td>
                                    <td class="fw-bold text-dark"><?= formatInr($h['gross_income']) ?></td>
                                    <td class="text-secondary"><?= formatInr($h['tax_liability_old']) ?></td>
                                    <td class="text-secondary"><?= formatInr($h['tax_liability_new']) ?></td>
                                    <td>
                                        <span class="badge <?= $h['recommended_regime'] === 'new' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' ?>">
                                            <?= strtoupper($h['recommended_regime']) ?>
                                        </span>
                                    </td>
                                    <td class="text-success fw-bold"><?= formatInr($h['potential_savings']) ?></td>
                                    <td><?= e($h['effective_tax_rate']) ?>%</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick='openSnapshotModal(<?= json_encode($h) ?>)'>
                                            <i class="bi bi-eye me-1"></i> Inspect
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab 2: Saved Scenarios -->
    <div class="tab-pane fade" id="scenarios" role="tabpanel">
        <div class="glass-card bg-white border shadow-sm">
            <?php if (empty($scenarios)): ?>
                <div class="empty-state py-5">
                    <div class="empty-state-icon"><i class="bi bi-sliders"></i></div>
                    <h5 class="fw-bold">No Saved Scenarios</h5>
                    <p class="text-muted small max-w-sm mx-auto mb-3">Model salary raises or new investments in the What-If Simulator and save them as named scenarios.</p>
                    <a href="<?= baseUrl('tax_simulator.php') ?>" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-sliders me-1"></i> Launch Simulator
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-financial mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Saved On</th>
                                <th>Scenario Name</th>
                                <th>Financial Year</th>
                                <th>Input Adjustments</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scenarios as $sc): ?>
                                <?php $inputs = json_decode($sc['scenario_inputs_json'], true); ?>
                                <tr>
                                    <td class="text-muted small"><?= date('d M Y', strtotime($sc['created_at'])) ?></td>
                                    <td class="fw-bold text-dark"><?= e($sc['scenario_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border">FY <?= e($sc['financial_year']) ?></span></td>
                                    <td class="small text-muted">
                                        Salary Delta: <?= formatInr($inputs['salary_delta'] ?? 0) ?> &bull; 
                                        Extra 80C: <?= formatInr($inputs['deductions']['80C'] ?? 0) ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= baseUrl('tax_simulator.php?fy=' . urlencode($sc['financial_year'])) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-arrow-repeat me-1"></i> Re-Open
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Inspect Snapshot Details -->
<div class="modal fade" id="snapshotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Calculation Snapshot Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="p-2 border rounded text-center bg-light">
                            <span class="text-muted small d-block">Gross Income</span>
                            <strong class="fs-6" id="snap_gross">₹0</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded text-center bg-light">
                            <span class="text-muted small d-block">Old Regime Tax</span>
                            <strong class="fs-6 text-danger" id="snap_old">₹0</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded text-center bg-light">
                            <span class="text-muted small d-block">New Regime Tax</span>
                            <strong class="fs-6 text-success" id="snap_new">₹0</strong>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border mb-3">
                    <div class="small text-muted">Recommended Optimal Regime:</div>
                    <div class="fw-bold text-dark fs-5" id="snap_rec">NEW REGIME</div>
                    <div class="text-success small fw-semibold" id="snap_savings">Saves ₹0</div>
                </div>

                <h6 class="fw-bold small text-uppercase text-muted mb-2">Raw JSON Snapshot</h6>
                <pre class="bg-dark text-light p-3 rounded small overflow-auto" style="max-height: 250px;" id="snap_json"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openSnapshotModal(item) {
    document.getElementById('snap_gross').innerText = formatInr(parseFloat(item.gross_income));
    document.getElementById('snap_old').innerText = formatInr(parseFloat(item.tax_liability_old));
    document.getElementById('snap_new').innerText = formatInr(parseFloat(item.tax_liability_new));
    document.getElementById('snap_rec').innerText = item.recommended_regime.toUpperCase() + ' REGIME';
    document.getElementById('snap_savings').innerText = 'Estimated Savings: ' + formatInr(parseFloat(item.potential_savings));
    
    let resJson = item.result_snapshot_json;
    if (typeof resJson === 'string') {
        try { resJson = JSON.parse(resJson); } catch(e){}
    }
    document.getElementById('snap_json').innerText = JSON.stringify(resJson, null, 2);

    new bootstrap.Modal(document.getElementById('snapshotModal')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
