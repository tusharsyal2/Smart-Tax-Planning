<?php
/**
 * Page 15: Admin Statutory Tax Calendar Manager
 * Manage official CBDT deadlines and compliance milestones broadcast to all taxpayers.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/AuditRepository.php';

requireAdmin();

$pdo = getDb();
$auditRepo = new AuditRepository($pdo);
$error = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security token mismatch. Please reload the page.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $fy = $_POST['financial_year'] ?? '2024-25';
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $due = $_POST['due_date'] ?? '';
            $cat = $_POST['category'] ?? 'advance_tax';
            $source = trim($_POST['source_reference'] ?? '');

            if (empty($title) || empty($due)) {
                $error = "Title and Due Date are mandatory.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO `tax_reminders`
                    (`user_id`, `financial_year`, `title`, `description`, `due_date`, `category`, `status`, `is_statutory`, `source_reference`)
                    VALUES (NULL, :fy, :title, :desc, :due, :cat, 'pending', 1, :src)
                ");
                $stmt->execute([
                    ':fy' => $fy,
                    ':title' => $title,
                    ':desc' => $desc,
                    ':due' => $due,
                    ':cat' => $cat,
                    ':src' => $source
                ]);
                $auditRepo->log($_SESSION['user_id'], 'STATUTORY_REMINDER_CREATED', 'tax_reminders', (int)$pdo->lastInsertId(), ['title' => $title]);
                setFlash('success', 'Statutory tax reminder added to official calendar.');
                header('Location: ' . baseUrl('admin/calendar.php'));
                exit;
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['reminder_id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM `tax_reminders` WHERE `id` = :id AND `is_statutory` = 1");
                $stmt->execute([':id' => $id]);
                $auditRepo->log($_SESSION['user_id'], 'STATUTORY_REMINDER_DELETED', 'tax_reminders', $id);
                setFlash('success', 'Statutory deadline removed.');
                header('Location: ' . baseUrl('admin/calendar.php'));
                exit;
            }
        }
    }
}

$reminders = $pdo->query("SELECT * FROM `tax_reminders` WHERE `is_statutory` = 1 ORDER BY `financial_year` DESC, `due_date` ASC")->fetchAll();

$pageTitle = "Admin Statutory Tax Calendar";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Statutory Tax Calendar Management</h3>
        <p class="text-muted small mb-0">Manage official CBDT filing deadlines and advance tax milestones displayed across all taxpayer dashboards.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= baseUrl('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Dashboard
        </a>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStatutoryModal">
            <i class="bi bi-plus-lg me-1"></i> Add Statutory Milestone
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="glass-card bg-white border shadow-sm">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Official Statutory Dates (<?= count($reminders) ?>)</h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-financial mb-0 align-middle small">
            <thead>
                <tr>
                    <th>Due Date</th>
                    <th>FY</th>
                    <th>Category</th>
                    <th>Milestone Title</th>
                    <th>Statutory Provision</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reminders as $r): ?>
                    <tr>
                        <td class="fw-bold text-dark text-nowrap"><i class="bi bi-calendar3 me-1 text-primary"></i> <?= date('d M Y', strtotime($r['due_date'])) ?></td>
                        <td><span class="badge bg-light text-dark border">FY <?= e($r['financial_year']) ?></span></td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">
                                <?= e(ucfirst(str_replace('_', ' ', $r['category']))) ?>
                            </span>
                        </td>
                        <td class="fw-semibold text-dark"><?= e($r['title']) ?></td>
                        <td class="text-muted" style="font-size:0.75rem;"><?= e($r['source_reference'] ?? 'Income Tax Act 1961') ?></td>
                        <td class="text-end">
                            <form method="POST" action="<?= baseUrl('admin/calendar.php') ?>" class="d-inline" onsubmit="return confirm('Remove this statutory calendar milestone?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="reminder_id" value="<?= $r['id'] ?>">
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
</div>

<!-- Modal: Add Statutory Milestone -->
<div class="modal fade" id="addStatutoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('admin/calendar.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Statutory Milestone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Target Financial Year</label>
                        <select class="form-select" name="financial_year">
                            <option value="2024-25" selected>FY 2024-25</option>
                            <option value="2025-26">FY 2025-26</option>
                            <option value="2023-24">FY 2023-24</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Milestone Title *</label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g. 1st Installment of Advance Tax">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category</label>
                        <select class="form-select" name="category">
                            <option value="advance_tax">Advance Tax Installment</option>
                            <option value="itr_filing">ITR Filing Deadline</option>
                            <option value="investment_proof">Investment Proofs</option>
                            <option value="tds_review">TDS Reconciliation</option>
                            <option value="custom">General Statutory Milestone</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Statutory Due Date *</label>
                        <input type="date" class="form-control" name="due_date" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description / Guidance</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Compliance instructions for taxpayers"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Official Source Reference</label>
                        <input type="text" class="form-control" name="source_reference" placeholder="e.g. Section 211(1) Income Tax Act 1961">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Publish Milestone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
