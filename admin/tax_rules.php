<?php
/**
 * Page 15: Admin Tax Rules & Progressive Slabs Configuration
 * Allows authorized administrators to view and configure year-specific tax slabs, standard deductions, and create new rule sets.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/TaxRuleService.php';
require_once __DIR__ . '/../repositories/AuditRepository.php';

requireAdmin();

$pdo = getDb();
$ruleService = new TaxRuleService($pdo);
$auditRepo = new AuditRepository($pdo);
$error = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security token mismatch. Please reload the page.";
    } else {
        $action = $_POST['action'] ?? 'update_rule';

        if ($action === 'update_rule') {
            $id = (int)($_POST['rule_id'] ?? 0);
            $stdDed = max(0.0, (float)($_POST['standard_deduction'] ?? 50000.0));
            $rebateLimit = max(0.0, (float)($_POST['rebate_87a_limit'] ?? 700000.0));
            $rebateMax = max(0.0, (float)($_POST['rebate_87a_max'] ?? 25000.0));
            $cess = (float)($_POST['cess_rate'] ?? 0.04);
            $status = $_POST['verification_status'] ?? 'verified';
            $source = trim($_POST['source_reference'] ?? '');
            $version = trim($_POST['rule_version'] ?? '1.0');

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE `tax_rule_sets`
                    SET `standard_deduction` = :std_ded,
                        `rebate_87a_limit` = :rebate_lim,
                        `rebate_87a_max` = :rebate_max,
                        `cess_rate` = :cess,
                        `verification_status` = :st,
                        `source_reference` = :src,
                        `rule_version` = :ver
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    ':id' => $id,
                    ':std_ded' => $stdDed,
                    ':rebate_lim' => $rebateLimit,
                    ':rebate_max' => $rebateMax,
                    ':cess' => $cess,
                    ':st' => $status,
                    ':src' => $source,
                    ':ver' => $version
                ]);

                $auditRepo->log($_SESSION['user_id'], 'TAX_RULE_UPDATED', 'tax_rule_sets', $id, ['version' => $version]);
                setFlash('success', 'Tax rule set parameters updated successfully.');
                header('Location: ' . baseUrl('admin/tax_rules.php'));
                exit;
            }
        } elseif ($action === 'add_slab') {
            $ruleId = (int)($_POST['rule_set_id'] ?? 0);
            $order = (int)($_POST['slab_order'] ?? 1);
            $lower = max(0.0, (float)($_POST['lower_bound'] ?? 0.0));
            $upper = !empty($_POST['upper_bound']) ? max(0.0, (float)$_POST['upper_bound']) : null;
            $rate = max(0.0, (float)($_POST['rate'] ?? 0.0));
            $conditions = trim($_POST['conditions'] ?? '');

            if ($ruleId > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO `tax_slabs` (`rule_set_id`, `slab_order`, `lower_bound`, `upper_bound`, `rate`, `conditions`)
                    VALUES (:rid, :ord, :low, :up, :rate, :cond)
                ");
                $stmt->execute([
                    ':rid' => $ruleId,
                    ':ord' => $order,
                    ':low' => $lower,
                    ':up' => $upper,
                    ':rate' => $rate,
                    ':cond' => $conditions
                ]);
                $auditRepo->log($_SESSION['user_id'], 'TAX_SLAB_ADDED', 'tax_slabs', (int)$pdo->lastInsertId(), ['rule_set_id' => $ruleId, 'rate' => $rate]);
                setFlash('success', 'Progressive tax slab added to rule set.');
                header('Location: ' . baseUrl('admin/tax_rules.php'));
                exit;
            }
        } elseif ($action === 'delete_slab') {
            $slabId = (int)($_POST['slab_id'] ?? 0);
            if ($slabId > 0) {
                $stmt = $pdo->prepare("DELETE FROM `tax_slabs` WHERE `id` = :id");
                $stmt->execute([':id' => $slabId]);
                $auditRepo->log($_SESSION['user_id'], 'TAX_SLAB_DELETED', 'tax_slabs', $slabId);
                setFlash('success', 'Progressive tax slab removed.');
                header('Location: ' . baseUrl('admin/tax_rules.php'));
                exit;
            }
        }
    }
}

// Fetch all rule sets with their slabs
$stmt = $pdo->query("SELECT * FROM `tax_rule_sets` ORDER BY `financial_year` DESC, `tax_regime` ASC");
$ruleSets = $stmt->fetchAll();

foreach ($ruleSets as &$rs) {
    $sStmt = $pdo->prepare("SELECT * FROM `tax_slabs` WHERE `rule_set_id` = :id ORDER BY `slab_order` ASC, `lower_bound` ASC");
    $sStmt->execute([':id' => $rs['id']]);
    $rs['slabs'] = $sStmt->fetchAll();
}
unset($rs);

$pageTitle = "Admin Tax Rules & Slabs";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Tax Rules &amp; Slabs Configuration</h3>
        <p class="text-muted small mb-0">Manage statutory ceilings, standard deductions, and progressive slab intervals across financial years.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= baseUrl('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Dashboard
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($ruleSets as $rs): ?>
        <div class="col-lg-6">
            <div class="glass-card p-4 bg-white border shadow-sm h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge <?= $rs['tax_regime'] === 'new' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' ?>">
                                <?= strtoupper($rs['tax_regime']) ?> REGIME
                            </span>
                            <span class="badge bg-light text-dark border">FY <?= e($rs['financial_year']) ?></span>
                            <span class="badge bg-secondary-subtle text-secondary">v<?= e($rs['rule_version']) ?></span>
                        </div>
                        <h5 class="fw-bold text-dark mb-0">AY <?= e($rs['assessment_year']) ?> (<?= e(ucfirst($rs['taxpayer_category'])) ?>)</h5>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick='openEditRuleModal(<?= json_encode($rs) ?>)'>
                            <i class="bi bi-pencil me-1"></i> Edit Rules
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="openAddSlabModal(<?= $rs['id'] ?>, '<?= e($rs['financial_year']) ?> (<?= strtoupper($rs['tax_regime']) ?>)')">
                            <i class="bi bi-plus-circle me-1"></i> Add Slab
                        </button>
                    </div>
                </div>

                <div class="row g-2 mb-3 small">
                    <div class="col-6">
                        <span class="text-muted d-block">Standard Deduction:</span>
                        <strong class="text-dark"><?= formatInr($rs['standard_deduction']) ?></strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Section 87A Ceiling:</span>
                        <strong class="text-dark"><?= formatInr($rs['rebate_87a_limit']) ?></strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Max 87A Rebate:</span>
                        <strong class="text-success"><?= formatInr($rs['rebate_87a_max']) ?></strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Cess Rate:</span>
                        <strong class="text-dark"><?= ($rs['cess_rate'] * 100) ?>%</strong>
                    </div>
                </div>

                <!-- Progressive Slabs for this Rule Set -->
                <h6 class="fw-bold small text-uppercase text-muted mb-2">Progressive Slabs (<?= count($rs['slabs']) ?>)</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered small mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Lower Bound</th>
                                <th>Upper Bound</th>
                                <th>Tax Rate</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rs['slabs'] as $s): ?>
                                <tr>
                                    <td><?= $s['slab_order'] ?></td>
                                    <td><?= formatInr($s['lower_bound']) ?></td>
                                    <td><?= $s['upper_bound'] ? formatInr($s['upper_bound']) : '<span class="text-muted">No Limit (Above)</span>' ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= ($s['rate'] * 100) ?>%</span></td>
                                    <td class="text-end">
                                        <form method="POST" action="<?= baseUrl('admin/tax_rules.php') ?>" class="d-inline" onsubmit="return confirm('Delete this slab interval?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_slab">
                                            <input type="hidden" name="slab_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn btn-link text-danger p-0 ms-1" title="Delete Slab">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-auto pt-2 border-top text-muted small" style="font-size:0.75rem;">
                    <strong>Source:</strong> <?= e($rs['source_reference']) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Edit Rule Set -->
<div class="modal fade" id="editRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('admin/tax_rules.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_rule">
                <input type="hidden" name="rule_id" id="edit_rule_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Tax Rule Parameters</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Standard Deduction (₹) *</label>
                        <input type="number" step="1000" class="form-control" name="standard_deduction" id="edit_std_ded" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Section 87A Limit (₹) *</label>
                            <input type="number" step="1000" class="form-control" name="rebate_87a_limit" id="edit_rebate_lim" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Max 87A Rebate (₹) *</label>
                            <input type="number" step="500" class="form-control" name="rebate_87a_max" id="edit_rebate_max" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Cess Rate (e.g. 0.04) *</label>
                            <input type="number" step="0.001" class="form-control" name="cess_rate" id="edit_cess" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Rule Version *</label>
                            <input type="text" class="form-control" name="rule_version" id="edit_ver" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Verification Status</label>
                        <select class="form-select" name="verification_status" id="edit_status">
                            <option value="verified">Verified Official</option>
                            <option value="provisional">Provisional</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Statutory Source Reference *</label>
                        <input type="text" class="form-control" name="source_reference" id="edit_source" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Update Tax Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Slab -->
<div class="modal fade" id="addSlabModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('admin/tax_rules.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_slab">
                <input type="hidden" name="rule_set_id" id="add_slab_rule_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Progressive Tax Slab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Adding interval for: <strong id="add_slab_rule_name"></strong></p>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Lower Bound (₹) *</label>
                            <input type="number" step="1000" class="form-control" name="lower_bound" required placeholder="e.g. 1500000">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Upper Bound (₹) (Blank for above)</label>
                            <input type="number" step="1000" class="form-control" name="upper_bound" placeholder="Leave blank for infinity">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tax Rate (e.g. 0.30 for 30%) *</label>
                            <input type="number" step="0.01" class="form-control" name="rate" required placeholder="0.30">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Slab Order</label>
                            <input type="number" class="form-control" name="slab_order" value="5">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Label / Condition Note</label>
                        <input type="text" class="form-control" name="conditions" placeholder="e.g. Above Rs 15 Lakhs: 30%">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">Add Slab</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditRuleModal(rs) {
    document.getElementById('edit_rule_id').value = rs.id;
    document.getElementById('edit_std_ded').value = rs.standard_deduction;
    document.getElementById('edit_rebate_lim').value = rs.rebate_87a_limit;
    document.getElementById('edit_rebate_max').value = rs.rebate_87a_max;
    document.getElementById('edit_cess').value = rs.cess_rate;
    document.getElementById('edit_ver').value = rs.rule_version;
    document.getElementById('edit_status').value = rs.verification_status;
    document.getElementById('edit_source').value = rs.source_reference;

    new bootstrap.Modal(document.getElementById('editRuleModal')).show();
}

function openAddSlabModal(ruleId, ruleName) {
    document.getElementById('add_slab_rule_id').value = ruleId;
    document.getElementById('add_slab_rule_name').innerText = ruleName;
    new bootstrap.Modal(document.getElementById('addSlabModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
