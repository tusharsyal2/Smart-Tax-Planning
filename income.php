<?php
/**
 * Page 9: Income Management Module
 * Comprehensive management across all 5 Indian tax heads with TDS and Advance Tax tracking.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/repositories/IncomeRepository.php';
require_once __DIR__ . '/repositories/AuditRepository.php';
require_once __DIR__ . '/services/ReportService.php';

requireAuth();

$userId = $_SESSION['user_id'];
$incomeRepo = new IncomeRepository();
$auditRepo = new AuditRepository();
$reportService = new ReportService();

$activeFy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';
$_SESSION['active_fy'] = $activeFy;

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $csv = $reportService->exportIncomeCsv($userId, $activeFy);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="income_' . $activeFy . '.csv"');
    echo "\xEF\xBB\xBF";
    echo $csv;
    exit;
}

$error = null;

// Handle POST actions (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security token mismatch. Please reload the page.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $cat = $_POST['category'] ?? 'salary';
            $sub = trim($_POST['sub_category'] ?? '');
            $gross = max(0.0, (float)($_POST['gross_amount'] ?? 0.0));
            $adj = max(0.0, (float)($_POST['adjustments'] ?? 0.0));
            $tds = max(0.0, (float)($_POST['tds_deducted'] ?? 0.0));
            $adv = max(0.0, (float)($_POST['advance_tax_paid'] ?? 0.0));
            $desc = trim($_POST['description'] ?? '');
            $date = !empty($_POST['income_date']) ? $_POST['income_date'] : date('Y-m-d');

            if ($gross <= 0) {
                $error = "Please enter a gross amount greater than zero.";
            } else {
                $id = $incomeRepo->create($userId, [
                    'financial_year' => $activeFy,
                    'category' => $cat,
                    'sub_category' => $sub,
                    'gross_amount' => $gross,
                    'adjustments' => $adj,
                    'tds_deducted' => $tds,
                    'advance_tax_paid' => $adv,
                    'description' => $desc,
                    'income_date' => $date
                ]);
                $auditRepo->log($userId, 'INCOME_CREATED', 'income_records', $id, ['category' => $cat, 'gross' => $gross]);
                setFlash('success', "Income entry under " . ucfirst(str_replace('_', ' ', $cat)) . " added.");
                header('Location: ' . baseUrl('income.php?fy=' . $activeFy));
                exit;
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['record_id'] ?? 0);
            $cat = $_POST['category'] ?? 'salary';
            $sub = trim($_POST['sub_category'] ?? '');
            $gross = max(0.0, (float)($_POST['gross_amount'] ?? 0.0));
            $adj = max(0.0, (float)($_POST['adjustments'] ?? 0.0));
            $tds = max(0.0, (float)($_POST['tds_deducted'] ?? 0.0));
            $adv = max(0.0, (float)($_POST['advance_tax_paid'] ?? 0.0));
            $desc = trim($_POST['description'] ?? '');
            $date = !empty($_POST['income_date']) ? $_POST['income_date'] : date('Y-m-d');

            if ($id > 0 && $gross > 0) {
                $incomeRepo->update($id, $userId, [
                    'financial_year' => $activeFy,
                    'category' => $cat,
                    'sub_category' => $sub,
                    'gross_amount' => $gross,
                    'adjustments' => $adj,
                    'tds_deducted' => $tds,
                    'advance_tax_paid' => $adv,
                    'description' => $desc,
                    'income_date' => $date
                ]);
                $auditRepo->log($userId, 'INCOME_UPDATED', 'income_records', $id, ['gross' => $gross]);
                setFlash('success', "Income entry updated.");
                header('Location: ' . baseUrl('income.php?fy=' . $activeFy));
                exit;
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['record_id'] ?? 0);
            if ($id > 0) {
                $incomeRepo->delete($id, $userId);
                $auditRepo->log($userId, 'INCOME_DELETED', 'income_records', $id);
                setFlash('success', "Income entry removed.");
                header('Location: ' . baseUrl('income.php?fy=' . $activeFy));
                exit;
            }
        }
    }
}

$records = $incomeRepo->getByUserAndFy($userId, $activeFy);
$totals = $incomeRepo->getTotals($userId, $activeFy);

$pageTitle = "Income Management";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Income Management Module</h3>
        <p class="text-muted small mb-0">Record and classify earnings across the 5 statutory heads of Indian Income Tax.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width: 140px;" onchange="location.href='?fy='+this.value">
            <option value="2024-25" <?= $activeFy === '2024-25' ? 'selected' : '' ?>>FY 2024-25</option>
            <option value="2023-24" <?= $activeFy === '2023-24' ? 'selected' : '' ?>>FY 2023-24</option>
            <option value="2025-26" <?= $activeFy === '2025-26' ? 'selected' : '' ?>>FY 2025-26</option>
        </select>
        <a href="?fy=<?= e($activeFy) ?>&action=export_csv" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> Export CSV
        </a>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
            <i class="bi bi-plus-lg me-1"></i> Add Income
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Income Category Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold text-uppercase mb-1">Salary Income</div>
            <div class="stat-val text-primary"><?= formatInr($totals['salary']) ?></div>
            <div class="text-muted small mt-2">Before standard deduction</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold text-uppercase mb-1">Other Sources (Interest)</div>
            <div class="stat-val text-info"><?= formatInr($totals['other_sources']) ?></div>
            <div class="text-muted small mt-2">Savings &amp; FD interest</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold text-uppercase mb-1">Business / House Property</div>
            <div class="stat-val text-dark"><?= formatInr($totals['business_profession'] + $totals['house_property']) ?></div>
            <div class="text-muted small mt-2">Net professional receipts</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold text-uppercase mb-1">TDS Deducted at Source</div>
            <div class="stat-val text-success"><?= formatInr($totals['total_tds']) ?></div>
            <div class="text-muted small mt-2">Available for tax credit</div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="glass-card bg-white border shadow-sm">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Income Records (<?= count($records) ?>)</h5>
        <span class="badge bg-light text-muted border">Total Gross: <?= formatInr($totals['total_gross']) ?></span>
    </div>

    <?php if (empty($records)): ?>
        <div class="empty-state py-5">
            <div class="empty-state-icon"><i class="bi bi-wallet2"></i></div>
            <h5 class="fw-bold">No Income Records Logged</h5>
            <p class="text-muted small max-w-sm mx-auto mb-3">Add your salary payslip details, freelance income, or savings interest to calculate your tax liability.</p>
            <button type="button" class="btn btn-primary btn-sm px-4" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
                <i class="bi bi-plus-lg me-1"></i> Add First Income Entry
            </button>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-financial mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Income Head</th>
                        <th>Description</th>
                        <th>Gross Amount</th>
                        <th>Adjustments</th>
                        <th>Net Amount</th>
                        <th>TDS Paid</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                        <?php $net = (float)$r['gross_amount'] - (float)$r['adjustments']; ?>
                        <tr>
                            <td class="text-muted small"><?= !empty($r['income_date']) ? date('d M Y', strtotime($r['income_date'])) : '—' ?></td>
                            <td>
                                <span class="badge bg-light text-primary border">
                                    <?= e(ucfirst(str_replace('_', ' ', $r['category']))) ?>
                                </span>
                            </td>
                            <td class="fw-semibold text-dark">
                                <?= e($r['description'] ?: ($r['sub_category'] ?: 'Income Entry')) ?>
                            </td>
                            <td><?= formatInr($r['gross_amount']) ?></td>
                            <td class="text-muted"><?= formatInr($r['adjustments']) ?></td>
                            <td class="fw-bold text-dark"><?= formatInr($net) ?></td>
                            <td class="text-success"><?= formatInr($r['tds_deducted']) ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='openEditIncomeModal(<?= json_encode($r) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="<?= baseUrl('income.php') ?>" class="d-inline" onsubmit="return confirm('Remove this income entry?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="record_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Add Income -->
<div class="modal fade" id="addIncomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('income.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Income Source</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Income Category (Head) *</label>
                        <select class="form-select" name="category" required>
                            <option value="salary">Income from Salary (Form 16)</option>
                            <option value="house_property">Income from House Property (Rental)</option>
                            <option value="business_profession">Profits &amp; Gains of Business / Profession</option>
                            <option value="capital_gains_stcg">Short-Term Capital Gains (STCG)</option>
                            <option value="capital_gains_ltcg">Long-Term Capital Gains (LTCG)</option>
                            <option value="other_sources">Income from Other Sources (Interest / Dividend)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description / Employer / Source *</label>
                        <input type="text" class="form-control" name="description" required placeholder="e.g. Tech Corp Annual CTC / HDFC Savings Interest">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Gross Amount (₹) *</label>
                            <input type="number" step="0.01" class="form-control" name="gross_amount" required placeholder="e.g. 1200000">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Adjustments / Taxes (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="adjustments" value="0" placeholder="e.g. Prof Tax">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">TDS Deducted (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="tds_deducted" value="0" placeholder="Tax deducted at source">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Advance Tax Paid (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="advance_tax_paid" value="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Receipt Date</label>
                        <input type="date" class="form-control" name="income_date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Save Income</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Income -->
<div class="modal fade" id="editIncomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('income.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="record_id" id="edit_inc_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Income Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Income Category *</label>
                        <select class="form-select" name="category" id="edit_inc_cat" required>
                            <option value="salary">Income from Salary</option>
                            <option value="house_property">Income from House Property</option>
                            <option value="business_profession">Business / Profession</option>
                            <option value="capital_gains_stcg">Short-Term Capital Gains</option>
                            <option value="capital_gains_ltcg">Long-Term Capital Gains</option>
                            <option value="other_sources">Other Sources</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description *</label>
                        <input type="text" class="form-control" name="description" id="edit_inc_desc" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Gross Amount (₹) *</label>
                            <input type="number" step="0.01" class="form-control" name="gross_amount" id="edit_inc_gross" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Adjustments (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="adjustments" id="edit_inc_adj">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">TDS Deducted (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="tds_deducted" id="edit_inc_tds">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Advance Tax (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="advance_tax_paid" id="edit_inc_adv">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Date</label>
                        <input type="date" class="form-control" name="income_date" id="edit_inc_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Update Income</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditIncomeModal(rec) {
    document.getElementById('edit_inc_id').value = rec.id;
    document.getElementById('edit_inc_cat').value = rec.category;
    document.getElementById('edit_inc_desc').value = rec.description || '';
    document.getElementById('edit_inc_gross').value = rec.gross_amount;
    document.getElementById('edit_inc_adj').value = rec.adjustments;
    document.getElementById('edit_inc_tds').value = rec.tds_deducted;
    document.getElementById('edit_inc_adv').value = rec.advance_tax_paid;
    document.getElementById('edit_inc_date').value = rec.income_date || '';
    new bootstrap.Modal(document.getElementById('editIncomeModal')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
