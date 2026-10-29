<?php
/**
 * Page 15: Admin Tax Provisions & Deductions Manager
 * Manage Chapter VI-A provisions, maximum limits, regime applicability, and eligibility rules.
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

        if ($action === 'create' || $action === 'update') {
            $code = strtoupper(trim($_POST['provision_code'] ?? ''));
            $name = trim($_POST['provision_name'] ?? '');
            $regime = $_POST['applicable_regime'] ?? 'old_only';
            $limit = !empty($_POST['maximum_limit']) ? (float)$_POST['maximum_limit'] : null;
            $eligibility = trim($_POST['eligibility_conditions'] ?? '');
            $docs = trim($_POST['documentation_required'] ?? '');
            $source = trim($_POST['source_reference'] ?? '');

            if (empty($code) || empty($name)) {
                $error = "Provision Code and Name are mandatory.";
            } elseif ($action === 'create') {
                $stmt = $pdo->prepare("
                    INSERT INTO `tax_provisions`
                    (`provision_code`, `provision_name`, `applicable_regime`, `maximum_limit`, `eligibility_conditions`, `documentation_required`, `source_reference`, `is_active`)
                    VALUES (:code, :name, :regime, :lim, :elig, :docs, :src, 1)
                ");
                $stmt->execute([
                    ':code' => $code,
                    ':name' => $name,
                    ':regime' => $regime,
                    ':lim' => $limit,
                    ':elig' => $eligibility,
                    ':docs' => $docs,
                    ':src' => $source
                ]);
                $auditRepo->log($_SESSION['user_id'], 'PROVISION_CREATED', 'tax_provisions', (int)$pdo->lastInsertId(), ['code' => $code]);
                setFlash('success', "Tax provision {$code} created successfully.");
                header('Location: ' . baseUrl('admin/provisions.php'));
                exit;
            } else {
                $id = (int)($_POST['provision_id'] ?? 0);
                $stmt = $pdo->prepare("
                    UPDATE `tax_provisions`
                    SET `provision_name` = :name,
                        `applicable_regime` = :regime,
                        `maximum_limit` = :lim,
                        `eligibility_conditions` = :elig,
                        `documentation_required` = :docs,
                        `source_reference` = :src
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    ':id' => $id,
                    ':name' => $name,
                    ':regime' => $regime,
                    ':lim' => $limit,
                    ':elig' => $eligibility,
                    ':docs' => $docs,
                    ':src' => $source
                ]);
                $auditRepo->log($_SESSION['user_id'], 'PROVISION_UPDATED', 'tax_provisions', $id, ['code' => $code]);
                setFlash('success', "Tax provision {$code} updated.");
                header('Location: ' . baseUrl('admin/provisions.php'));
                exit;
            }
        } elseif ($action === 'toggle_status') {
            $id = (int)($_POST['provision_id'] ?? 0);
            $newActive = (int)($_POST['new_active'] ?? 1);
            $stmt = $pdo->prepare("UPDATE `tax_provisions` SET `is_active` = :act WHERE `id` = :id");
            $stmt->execute([':id' => $id, ':act' => $newActive]);
            setFlash('success', 'Provision active status toggled.');
            header('Location: ' . baseUrl('admin/provisions.php'));
            exit;
        }
    }
}

$provisions = $pdo->query("SELECT * FROM `tax_provisions` ORDER BY `provision_code` ASC")->fetchAll();

$pageTitle = "Admin Tax Provisions";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Chapter VI-A &amp; Statutory Provisions</h3>
        <p class="text-muted small mb-0">Manage statutory limits, documentation requirements, and regime allowances.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= baseUrl('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Dashboard
        </a>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProvisionModal">
            <i class="bi bi-plus-lg me-1"></i> Add Provision
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
        <h5 class="fw-bold mb-0">Statutory Provisions (<?= count($provisions) ?>)</h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-financial mb-0 align-middle small">
            <thead>
                <tr>
                    <th>Section Code</th>
                    <th>Provision Name</th>
                    <th>Applicable Regime</th>
                    <th>Statutory Ceiling</th>
                    <th>Status</th>
                    <th>Source Law</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($provisions as $p): ?>
                    <tr>
                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold"><?= e($p['provision_code']) ?></span></td>
                        <td class="fw-semibold text-dark"><?= e($p['provision_name']) ?></td>
                        <td>
                            <?php if ($p['applicable_regime'] === 'both'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Both Regimes</span>
                            <?php elseif ($p['applicable_regime'] === 'new_only'): ?>
                                <span class="badge bg-info-subtle text-info border">New Regime Only</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border">Old Regime Only</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold"><?= $p['maximum_limit'] ? formatInr($p['maximum_limit']) : 'No Upper Cap' ?></td>
                        <td>
                            <?php if ($p['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted" style="font-size:0.75rem;"><?= e($p['source_reference']) ?></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='openEditProvisionModal(<?= json_encode($p) ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="<?= baseUrl('admin/provisions.php') ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="provision_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="new_active" value="<?= $p['is_active'] ? 0 : 1 ?>">
                                <button type="submit" class="btn btn-sm <?= $p['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $p['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                    <i class="bi <?= $p['is_active'] ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Provision -->
<div class="modal fade" id="addProvisionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('admin/provisions.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Statutory Provision</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Provision Code *</label>
                        <input type="text" class="form-control text-uppercase" name="provision_code" required placeholder="e.g. 80EEB">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Provision Name *</label>
                        <input type="text" class="form-control" name="provision_name" required placeholder="e.g. Electric Vehicle Loan Interest">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Applicable Regime</label>
                        <select class="form-select" name="applicable_regime">
                            <option value="old_only">Old Tax Regime Only</option>
                            <option value="both">Both Old and New Regimes</option>
                            <option value="new_only">New Tax Regime Only</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Maximum Statutory Limit (₹) (Leave blank if uncapped)</label>
                        <input type="number" step="1000" class="form-control" name="maximum_limit" placeholder="e.g. 150000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Eligibility Conditions</label>
                        <textarea class="form-control" name="eligibility_conditions" rows="2" placeholder="Specific eligibility criteria"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Documentation Required</label>
                        <textarea class="form-control" name="documentation_required" rows="2" placeholder="Required supporting proofs"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Source Reference</label>
                        <input type="text" class="form-control" name="source_reference" value="Income Tax Act 1961">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Create Provision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Provision -->
<div class="modal fade" id="editProvisionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('admin/provisions.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="provision_id" id="edit_prov_id">
                <input type="hidden" name="provision_code" id="edit_prov_code_hidden">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Statutory Provision</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Provision Code</label>
                        <input type="text" class="form-control bg-light" id="edit_prov_code" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Provision Name *</label>
                        <input type="text" class="form-control" name="provision_name" id="edit_prov_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Applicable Regime</label>
                        <select class="form-select" name="applicable_regime" id="edit_prov_regime">
                            <option value="old_only">Old Tax Regime Only</option>
                            <option value="both">Both Old and New Regimes</option>
                            <option value="new_only">New Tax Regime Only</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Maximum Statutory Limit (₹)</label>
                        <input type="number" step="1000" class="form-control" name="maximum_limit" id="edit_prov_limit">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Eligibility Conditions</label>
                        <textarea class="form-control" name="eligibility_conditions" id="edit_prov_elig" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Documentation Required</label>
                        <textarea class="form-control" name="documentation_required" id="edit_prov_docs" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Source Reference</label>
                        <input type="text" class="form-control" name="source_reference" id="edit_prov_source">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Update Provision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditProvisionModal(p) {
    document.getElementById('edit_prov_id').value = p.id;
    document.getElementById('edit_prov_code').value = p.provision_code;
    document.getElementById('edit_prov_code_hidden').value = p.provision_code;
    document.getElementById('edit_prov_name').value = p.provision_name;
    document.getElementById('edit_prov_regime').value = p.applicable_regime;
    document.getElementById('edit_prov_limit').value = p.maximum_limit || '';
    document.getElementById('edit_prov_elig').value = p.eligibility_conditions || '';
    document.getElementById('edit_prov_docs').value = p.documentation_required || '';
    document.getElementById('edit_prov_source').value = p.source_reference || '';

    new bootstrap.Modal(document.getElementById('editProvisionModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
