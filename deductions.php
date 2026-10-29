<?php
/**
 * Page 8: Deduction and Investment Tracker
 * CRUD for Chapter VI-A deductions and housing interest with statutory eligibility tracking.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/repositories/DeductionRepository.php';
require_once __DIR__ . '/repositories/AuditRepository.php';
require_once __DIR__ . '/services/ReportService.php';

requireAuth();

$userId = $_SESSION['user_id'];
$deductionRepo = new DeductionRepository();
$auditRepo = new AuditRepository();
$reportService = new ReportService();

$activeFy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';
$_SESSION['active_fy'] = $activeFy;

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $csv = $reportService->exportDeductionsCsv($userId, $activeFy);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="deductions_' . $activeFy . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo $csv;
    exit;
}

$error = null;

// Handle POST actions (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security token validation failed. Please refresh.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $section = trim($_POST['section_code'] ?? '80C');
            $category = trim($_POST['category'] ?? '');
            $amount = max(0.0, (float)($_POST['amount_entered'] ?? 0.0));
            $status = $_POST['verification_status'] ?? 'pending_verification';
            $notes = trim($_POST['notes'] ?? '');

            if ($amount <= 0) {
                $error = "Please enter an amount greater than zero.";
            } else {
                $id = $deductionRepo->create($userId, [
                    'financial_year' => $activeFy,
                    'section_code' => $section,
                    'category' => !empty($category) ? $category : $section,
                    'amount_entered' => $amount,
                    'verification_status' => $status,
                    'notes' => $notes
                ]);
                $auditRepo->log($userId, 'DEDUCTION_CREATED', 'deduction_records', $id, ['section' => $section, 'amount' => $amount]);
                setFlash('success', "Deduction record under {$section} added successfully.");
                header('Location: ' . baseUrl('deductions.php?fy=' . $activeFy));
                exit;
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['record_id'] ?? 0);
            $section = trim($_POST['section_code'] ?? '80C');
            $category = trim($_POST['category'] ?? '');
            $amount = max(0.0, (float)($_POST['amount_entered'] ?? 0.0));
            $status = $_POST['verification_status'] ?? 'pending_verification';
            $notes = trim($_POST['notes'] ?? '');

            if ($id > 0 && $amount > 0) {
                $deductionRepo->update($id, $userId, [
                    'financial_year' => $activeFy,
                    'section_code' => $section,
                    'category' => !empty($category) ? $category : $section,
                    'amount_entered' => $amount,
                    'verification_status' => $status,
                    'notes' => $notes
                ]);
                $auditRepo->log($userId, 'DEDUCTION_UPDATED', 'deduction_records', $id, ['amount' => $amount]);
                setFlash('success', "Deduction record updated successfully.");
                header('Location: ' . baseUrl('deductions.php?fy=' . $activeFy));
                exit;
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['record_id'] ?? 0);
            if ($id > 0) {
                $deductionRepo->delete($id, $userId);
                $auditRepo->log($userId, 'DEDUCTION_DELETED', 'deduction_records', $id);
                setFlash('success', "Deduction entry removed.");
                header('Location: ' . baseUrl('deductions.php?fy=' . $activeFy));
                exit;
            }
        }
    }
}

$records = $deductionRepo->getByUserAndFy($userId, $activeFy);

$totalEntered = 0.0;
$totalEligible = 0.0;
foreach ($records as $r) {
    $totalEntered += (float)$r['amount_entered'];
    $totalEligible += (float)$r['eligible_amount'];
}

$pageTitle = "Deductions & Investments Tracker";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Deductions &amp; Investments Tracker</h3>
        <p class="text-muted small mb-0">Record eligible Chapter VI-A investments with automatic statutory ceiling enforcement.</p>
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
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDeductionModal">
            <i class="bi bi-plus-lg me-1"></i> Add Deduction
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold text-uppercase mb-1">Total Amount Entered</div>
            <div class="stat-val"><?= formatInr($totalEntered) ?></div>
            <div class="text-muted small mt-2">Recorded user expenses</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold text-uppercase mb-1">Potentially Eligible (Old)</div>
            <div class="stat-val text-success"><?= formatInr($totalEligible) ?></div>
            <div class="text-muted small mt-2">After applying statutory caps</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold text-uppercase mb-1">Section 80C Ceiling</div>
            <div class="stat-val text-primary">₹1,50,000</div>
            <div class="text-muted small mt-2">Max allowed per tax year</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="text-muted small fw-semibold text-uppercase mb-1">Section 80CCD(1B)</div>
            <div class="stat-val text-info">₹50,000</div>
            <div class="text-muted small mt-2">NPS exclusive deduction</div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="glass-card bg-white border shadow-sm">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Recorded Deductions (<?= count($records) ?>)</h5>
    </div>

    <?php if (empty($records)): ?>
        <div class="empty-state py-5">
            <div class="empty-state-icon"><i class="bi bi-piggy-bank"></i></div>
            <h5 class="fw-bold">No Deductions Recorded Yet</h5>
            <p class="text-muted small max-w-sm mx-auto mb-3">Add investments like PPF, ELSS, health insurance policies, or NPS contributions to evaluate your Old Regime tax savings.</p>
            <button type="button" class="btn btn-primary btn-sm px-4" data-bs-toggle="modal" data-bs-target="#addDeductionModal">
                <i class="bi bi-plus-lg me-1"></i> Add First Deduction
            </button>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-financial mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Category Description</th>
                        <th>Amount Entered</th>
                        <th>Potentially Eligible</th>
                        <th>Verification Status</th>
                        <th>Notes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold"><?= e($r['section_code']) ?></span></td>
                            <td class="fw-semibold text-dark"><?= e($r['category']) ?></td>
                            <td><?= formatInr($r['amount_entered']) ?></td>
                            <td class="text-success fw-bold"><?= formatInr($r['eligible_amount']) ?></td>
                            <td>
                                <?php if ($r['verification_status'] === 'verified'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2 me-1"></i>Verified</span>
                                <?php elseif ($r['verification_status'] === 'document_provided'): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-file-earmark-check me-1"></i>Doc Provided</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-clock me-1"></i>Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= e($r['notes'] ?? '—') ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    onclick='openEditModal(<?= json_encode($r) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="<?= baseUrl('deductions.php') ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this deduction record?');">
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

<!-- Modal: Add Deduction -->
<div class="modal fade" id="addDeductionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('deductions.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Deduction / Investment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Statutory Tax Section *</label>
                        <select class="form-select" name="section_code" id="sectionSelect" required onchange="handleSectionChange(this.value)">
                            <option value="80C">Section 80C (PPF, EPF, ELSS, Life Ins, Tuition, Home Loan Principal) [Max ₹1.5L]</option>
                            <option value="80CCD_1B">Section 80CCD(1B) (NPS Voluntary Contribution) [Max ₹50,000]</option>
                            <option value="80CCD_2">Section 80CCD(2) (Employer NPS Contribution - Both Regimes)</option>
                            <option value="80D_SELF">Section 80D (Health Insurance Self/Family) [Max ₹25k / ₹50k]</option>
                            <option value="80D_PARENTS">Section 80D (Health Insurance Parents) [Max ₹25k / ₹50k]</option>
                            <option value="24B">Section 24(b) (Home Loan Interest Self-Occupied) [Max ₹2 Lakhs]</option>
                            <option value="80E">Section 80E (Higher Education Loan Interest - No Cap)</option>
                            <option value="80G">Section 80G (Eligible Charitable Donations)</option>
                            <option value="80TTA">Section 80TTA (Savings Account Interest) [Max ₹10,000]</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description / Investment Item *</label>
                        <input type="text" class="form-control" name="category" id="catDesc" required placeholder="e.g. PPF Account Deposit / Star Health Insurance">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Amount Entered (₹) *</label>
                        <input type="number" step="0.01" class="form-control" name="amount_entered" required placeholder="e.g. 50000">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Verification Status</label>
                        <select class="form-select" name="verification_status">
                            <option value="pending_verification">Pending Verification</option>
                            <option value="verified">Verified with Receipt</option>
                            <option value="document_provided">Document Provided</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes / Receipt Ref</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Policy number, receipt reference, or bank details"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Deduction -->
<div class="modal fade" id="editDeductionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('deductions.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="record_id" id="edit_record_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Deduction Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Statutory Tax Section *</label>
                        <select class="form-select" name="section_code" id="edit_section_code" required>
                            <option value="80C">Section 80C</option>
                            <option value="80CCD_1B">Section 80CCD(1B)</option>
                            <option value="80CCD_2">Section 80CCD(2)</option>
                            <option value="80D_SELF">Section 80D (Self/Family)</option>
                            <option value="80D_PARENTS">Section 80D (Parents)</option>
                            <option value="24B">Section 24(b)</option>
                            <option value="80E">Section 80E</option>
                            <option value="80G">Section 80G</option>
                            <option value="80TTA">Section 80TTA</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description *</label>
                        <input type="text" class="form-control" name="category" id="edit_category" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Amount (₹) *</label>
                        <input type="number" step="0.01" class="form-control" name="amount_entered" id="edit_amount_entered" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Verification Status</label>
                        <select class="form-select" name="verification_status" id="edit_verification_status">
                            <option value="pending_verification">Pending Verification</option>
                            <option value="verified">Verified with Receipt</option>
                            <option value="document_provided">Document Provided</option>
                            <option value="rejected">Rejected / Ineligible</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea class="form-control" name="notes" id="edit_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(record) {
    document.getElementById('edit_record_id').value = record.id;
    document.getElementById('edit_section_code').value = record.section_code;
    document.getElementById('edit_category').value = record.category;
    document.getElementById('edit_amount_entered').value = record.amount_entered;
    document.getElementById('edit_verification_status').value = record.verification_status;
    document.getElementById('edit_notes').value = record.notes || '';
    
    new bootstrap.Modal(document.getElementById('editDeductionModal')).show();
}

function handleSectionChange(val) {
    const desc = document.getElementById('catDesc');
    if (val === '80C') desc.value = 'Public Provident Fund (PPF) / ELSS Mutual Fund';
    else if (val === '80CCD_1B') desc.value = 'National Pension System (NPS Tier 1)';
    else if (val === '80CCD_2') desc.value = 'Employer NPS Contribution';
    else if (val === '80D_SELF') desc.value = 'Health Insurance (Self & Dependent Children)';
    else if (val === '80D_PARENTS') desc.value = 'Health Insurance for Parents';
    else if (val === '24B') desc.value = 'Housing Loan Interest (Self-Occupied House)';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
