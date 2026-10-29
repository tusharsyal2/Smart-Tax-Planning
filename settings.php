<?php
/**
 * Page 14: User Settings & Account Security
 * Password management, profile preferences, data export, and account deletion.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/IncomeRepository.php';
require_once __DIR__ . '/repositories/DeductionRepository.php';
require_once __DIR__ . '/repositories/TaxCalculationRepository.php';
require_once __DIR__ . '/repositories/AuditRepository.php';

requireAuth();

$userId = $_SESSION['user_id'];
$userRepo = new UserRepository();
$incomeRepo = new IncomeRepository();
$deductionRepo = new DeductionRepository();
$calcRepo = new TaxCalculationRepository();
$auditRepo = new AuditRepository();

$user = $userRepo->findById($userId);
$error = null;

// Handle Export All Data (JSON Bundle)
if (isset($_GET['action']) && $_GET['action'] === 'export_all_json') {
    $fy = $_SESSION['active_fy'] ?? '2024-25';
    $dataBundle = [
        'user' => [
            'name' => $user['name'],
            'email' => $user['email'],
            'employment_category' => $user['employment_category'],
            'state' => $user['state']
        ],
        'financial_year' => $fy,
        'exported_at' => date('c'),
        'income_records' => $incomeRepo->getByUserAndFy($userId, $fy),
        'deduction_records' => $deductionRepo->getByUserAndFy($userId, $fy),
        'calculation_history' => $calcRepo->getHistory($userId, $fy)
    ];

    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tax_data_backup_' . date('Ymd_His') . '.json"');
    echo json_encode($dataBundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security token mismatch. Please reload the page.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($currentPass) || empty($newPass)) {
                $error = "Please fill in all password fields.";
            } elseif (!password_verify($currentPass, $user['password_hash'])) {
                $error = "Your current password does not match our records.";
            } elseif (strlen($newPass) < 8) {
                $error = "New password must be at least 8 characters long.";
            } elseif ($newPass !== $confirmPass) {
                $error = "New passwords do not match.";
            } else {
                $userRepo->updatePassword($userId, $newPass);
                $auditRepo->log($userId, 'PASSWORD_CHANGED', 'users', $userId);
                setFlash('success', 'Your password has been updated securely.');
                header('Location: ' . baseUrl('settings.php'));
                exit;
            }
        } elseif ($action === 'delete_account') {
            $confirmPass = $_POST['delete_password'] ?? '';
            if (!password_verify($confirmPass, $user['password_hash'])) {
                $error = "Incorrect password. Account deletion aborted for security.";
            } else {
                $auditRepo->log($userId, 'ACCOUNT_DELETED', 'users', $userId, ['email' => $user['email']]);
                $userRepo->deleteAccount($userId);
                // Clear session
                $_SESSION = [];
                session_destroy();
                header('Location: ' . baseUrl('login.php?deleted=1'));
                exit;
            }
        }
    }
}

$pageTitle = "User Settings";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Account &amp; Security Settings</h3>
                <p class="text-muted small mb-0">Configure your credentials, security preferences, and data privacy options.</p>
            </div>
            <a href="<?= baseUrl('dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Change Password Card -->
        <div class="glass-card p-4 bg-white border shadow-sm mb-4">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-key text-primary me-2"></i>Change Password</h5>
            <form method="POST" action="<?= baseUrl('settings.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Current Password *</label>
                    <input type="password" class="form-control" name="current_password" required placeholder="Enter current password">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">New Password (min 8 chars) *</label>
                        <input type="password" class="form-control" name="new_password" required placeholder="New password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Confirm New Password *</label>
                        <input type="password" class="form-control" name="confirm_password" required placeholder="Re-type new password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-shield-lock me-1"></i> Update Password
                </button>
            </form>
        </div>

        <!-- Data Privacy & Export Card -->
        <div class="glass-card p-4 bg-white border shadow-sm mb-4">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-cloud-arrow-down text-primary me-2"></i>Data Portability &amp; Backup</h5>
            <p class="text-muted small">
                Download a complete, machine-readable JSON archive of all your saved income records, deductions, and calculation history.
            </p>
            <a href="?action=export_all_json" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-download me-1"></i> Export Complete Data Bundle (.JSON)
            </a>
        </div>

        <!-- Danger Zone: Account Deletion -->
        <div class="glass-card p-4 bg-white border-danger border shadow-sm">
            <h5 class="fw-bold text-danger border-bottom pb-2 mb-3"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Danger Zone</h5>
            <p class="text-muted small">
                Permanently delete your user account and all associated financial profiles, income entries, deductions, and calculation history. This action cannot be undone.
            </p>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                <i class="bi bi-trash me-1"></i> Request Account Deletion
            </button>
        </div>
    </div>
</div>

<!-- Modal: Confirm Delete Account -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('settings.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_account">
                <div class="modal-header bg-danger-subtle text-danger">
                    <h5 class="modal-title fw-bold">Delete Account &amp; Financial Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger small fw-semibold">
                        Warning: This will immediately purge all your income logs, deduction records, reminders, and saved tax scenarios from the database.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Enter your account password to confirm: *</label>
                        <input type="password" class="form-control" name="delete_password" required placeholder="Your current password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3">Permanently Delete Everything</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
