<?php
/**
 * Page 15: Admin Audit Log Viewer
 * Immutable security and operational audit log stream.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../repositories/AuditRepository.php';

requireAdmin();

$auditRepo = new AuditRepository();
$logs = $auditRepo->getRecentLogs(100);

$pageTitle = "Admin Audit Logs";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">System Audit &amp; Event Logs</h3>
        <p class="text-muted small mb-0">Chronological telemetry of administrative rule updates, logins, and profile changes.</p>
    </div>
    <div>
        <a href="<?= baseUrl('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Dashboard
        </a>
    </div>
</div>

<div class="glass-card bg-white border shadow-sm">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Recent Event Stream (<?= count($logs) ?>)</h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-financial mb-0 align-middle small">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>User / Actor</th>
                    <th>Target Record</th>
                    <th>IP Address</th>
                    <th>Metadata</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No audit events recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="text-muted text-nowrap"><?= date('d M Y, h:i:s A', strtotime($l['created_at'])) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border fw-bold">
                                    <?= e($l['action']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($l['user_name']): ?>
                                    <strong><?= e($l['user_name']) ?></strong> <span class="text-muted">(#<?= $l['user_id'] ?>)</span>
                                <?php else: ?>
                                    <span class="text-muted">System / Guest</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    <?= e($l['affected_record_type']) ?> #<?= e($l['affected_record_id']) ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= e($l['ip_address']) ?></td>
                            <td>
                                <?php if (!empty($l['metadata_json'])): ?>
                                    <code class="text-primary small"><?= e(substr($l['metadata_json'], 0, 80)) ?><?= strlen($l['metadata_json']) > 80 ? '...' : '' ?></code>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
