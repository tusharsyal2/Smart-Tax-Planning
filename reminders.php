<?php
/**
 * Page 11: Tax Calendar and Reminders
 * Indian Statutory Tax Deadlines and Personal Tax Planning Task Manager.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/repositories/ReminderRepository.php';
require_once __DIR__ . '/repositories/AuditRepository.php';

requireAuth();

$userId = $_SESSION['user_id'];
$reminderRepo = new ReminderRepository();
$auditRepo = new AuditRepository();

$activeFy = $_GET['fy'] ?? $_SESSION['active_fy'] ?? '2024-25';
$_SESSION['active_fy'] = $activeFy;

$error = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security token mismatch. Please reload.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $due = $_POST['due_date'] ?? '';
            $cat = $_POST['category'] ?? 'custom';

            if (empty($title) || empty($due)) {
                $error = "Please provide both reminder title and due date.";
            } else {
                $id = $reminderRepo->createCustomReminder($userId, [
                    'financial_year' => $activeFy,
                    'title' => $title,
                    'description' => $desc,
                    'due_date' => $due,
                    'category' => $cat
                ]);
                $auditRepo->log($userId, 'REMINDER_CREATED', 'tax_reminders', $id, ['title' => $title]);
                setFlash('success', 'Custom tax reminder added.');
                header('Location: ' . baseUrl('reminders.php?fy=' . $activeFy));
                exit;
            }
        } elseif ($action === 'toggle_status') {
            $id = (int)($_POST['reminder_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? 'completed';
            $reminderRepo->updateStatus($id, $userId, $newStatus);
            setFlash('success', 'Reminder status updated.');
            header('Location: ' . baseUrl('reminders.php?fy=' . $activeFy));
            exit;
        } elseif ($action === 'delete') {
            $id = (int)($_POST['reminder_id'] ?? 0);
            $reminderRepo->delete($id, $userId);
            setFlash('success', 'Reminder deleted.');
            header('Location: ' . baseUrl('reminders.php?fy=' . $activeFy));
            exit;
        }
    }
}

$reminders = $reminderRepo->getRemindersForUser($userId, $activeFy);

$pageTitle = "Tax Calendar & Reminders";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Indian Tax Calendar &amp; Reminders</h3>
        <p class="text-muted small mb-0">Statutory Advance Tax dates, ITR filing deadlines, and personal tax planning checklists.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width: 140px;" onchange="location.href='?fy='+this.value">
            <option value="2024-25" <?= $activeFy === '2024-25' ? 'selected' : '' ?>>FY 2024-25</option>
            <option value="2023-24" <?= $activeFy === '2023-24' ? 'selected' : '' ?>>FY 2023-24</option>
            <option value="2025-26" <?= $activeFy === '2025-26' ? 'selected' : '' ?>>FY 2025-26</option>
        </select>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addReminderModal">
            <i class="bi bi-plus-lg me-1"></i> Add Custom Task
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statutory Highlights Banner -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="stat-card border-start border-4 border-primary">
            <div class="text-muted small fw-semibold text-uppercase mb-1">1st Advance Tax</div>
            <div class="fw-bold fs-5 text-dark">June 15</div>
            <div class="text-muted small">15% of net tax due</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card border-start border-4 border-info">
            <div class="text-muted small fw-semibold text-uppercase mb-1">2nd Advance Tax</div>
            <div class="fw-bold fs-5 text-dark">September 15</div>
            <div class="text-muted small">Cumulative 45% due</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card border-start border-4 border-warning">
            <div class="text-muted small fw-semibold text-uppercase mb-1">3rd Advance Tax</div>
            <div class="fw-bold fs-5 text-dark">December 15</div>
            <div class="text-muted small">Cumulative 75% due</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card border-start border-4 border-danger">
            <div class="text-muted small fw-semibold text-uppercase mb-1">4th Installment &amp; ITR</div>
            <div class="fw-bold fs-5 text-dark">March 15 / July 31</div>
            <div class="text-muted small">100% tax &amp; return due</div>
        </div>
    </div>
</div>

<!-- Reminders Table Card -->
<div class="glass-card bg-white border shadow-sm">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Tax Planning Calendar (<?= count($reminders) ?>)</h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-financial mb-0 align-middle">
            <thead>
                <tr>
                    <th>Due Date</th>
                    <th>Type</th>
                    <th>Task / Reminder Title</th>
                    <th>Description &amp; Statutory Source</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reminders as $rem): ?>
                    <?php 
                        $isPast = strtotime($rem['due_date']) < time();
                        $isCompleted = ($rem['status'] === 'completed');
                    ?>
                    <tr class="<?= $isCompleted ? 'table-light opacity-75' : '' ?>">
                        <td class="text-nowrap fw-semibold text-dark">
                            <i class="bi bi-calendar3 me-1 text-primary"></i> <?= date('d M Y', strtotime($rem['due_date'])) ?>
                            <?php if ($isPast && !$isCompleted): ?>
                                <span class="badge bg-danger-subtle text-danger ms-1 small">Past Due</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($rem['is_statutory']): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Statutory (CBDT)</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border">Custom Task</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold text-dark">
                            <?= e($rem['title']) ?>
                        </td>
                        <td class="text-muted small">
                            <?= e($rem['description'] ?? '—') ?>
                            <?php if (!empty($rem['source_reference'])): ?>
                                <div class="text-primary mt-1" style="font-size:0.75rem;"><i class="bi bi-book me-1"></i><?= e($rem['source_reference']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isCompleted): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2 me-1"></i>Completed</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="<?= baseUrl('reminders.php') ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="reminder_id" value="<?= $rem['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $isCompleted ? 'pending' : 'completed' ?>">
                                <button type="submit" class="btn btn-sm <?= $isCompleted ? 'btn-outline-secondary' : 'btn-outline-success' ?> me-1" title="<?= $isCompleted ? 'Mark as Pending' : 'Mark as Completed' ?>">
                                    <i class="bi <?= $isCompleted ? 'bi-arrow-counterclockwise' : 'bi-check-lg' ?>"></i>
                                </button>
                            </form>
                            <?php if (!$rem['is_statutory']): ?>
                                <form method="POST" action="<?= baseUrl('reminders.php') ?>" class="d-inline" onsubmit="return confirm('Delete this custom reminder?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="reminder_id" value="<?= $rem['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Reminder -->
<div class="modal fade" id="addReminderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('reminders.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Custom Tax Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Task Title *</label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g. Collect Health Insurance 80D Certificate">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category</label>
                        <select class="form-select" name="category">
                            <option value="investment_proof">Investment Proof Submission</option>
                            <option value="advance_tax">Advance Tax Estimation</option>
                            <option value="tds_review">Form 26AS / AIS Review</option>
                            <option value="itr_filing">ITR Filing Preparation</option>
                            <option value="custom">General Custom Reminder</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Target Due Date *</label>
                        <input type="date" class="form-control" name="due_date" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes / Instructions</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Specific documents, policy numbers, or portal links"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Add Reminder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
