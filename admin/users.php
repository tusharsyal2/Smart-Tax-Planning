<?php
/**
 * Page 15: Admin User Management Module
 * Comprehensive user account administration: Create, Edit, Role Assignment, Status Toggle, Password Reset, and Deletion.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/AuditRepository.php';

requireAdmin();

$pdo = getDb();
$userRepo = new UserRepository($pdo);
$auditRepo = new AuditRepository($pdo);
$error = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security token mismatch. Please reload the page.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_user') {
            $name = trim($_POST['name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $pass = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $status = $_POST['status'] ?? 'active';

            if (empty($name) || empty($email) || empty($pass)) {
                $error = "Name, email, and password are required.";
            } elseif ($userRepo->findByEmail($email)) {
                $error = "An account with this email address already exists.";
            } else {
                $newId = $userRepo->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $pass,
                    'role' => $role,
                    'status' => $status
                ]);
                $auditRepo->log($_SESSION['user_id'], 'ADMIN_CREATED_USER', 'users', $newId, ['email' => $email, 'role' => $role]);
                setFlash('success', "User account {$name} created successfully.");
                header('Location: ' . baseUrl('admin/users.php'));
                exit;
            }
        } elseif ($action === 'edit_user') {
            $id = (int)($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $status = $_POST['status'] ?? 'active';

            if ($id > 0 && !empty($name)) {
                $stmt = $pdo->prepare("UPDATE `users` SET `name` = :name, `role` = :role, `status` = :st WHERE `id` = :id");
                $stmt->execute([':id' => $id, ':name' => $name, ':role' => $role, ':st' => $status]);
                $auditRepo->log($_SESSION['user_id'], 'ADMIN_UPDATED_USER', 'users', $id, ['role' => $role, 'status' => $status]);
                setFlash('success', "User account updated successfully.");
                header('Location: ' . baseUrl('admin/users.php'));
                exit;
            }
        } elseif ($action === 'reset_password') {
            $id = (int)($_POST['user_id'] ?? 0);
            $newPass = $_POST['new_password'] ?? '';
            if ($id > 0 && strlen($newPass) >= 6) {
                $userRepo->updatePassword($id, $newPass);
                $auditRepo->log($_SESSION['user_id'], 'ADMIN_RESET_PASSWORD', 'users', $id);
                setFlash('success', "Password for user #{$id} reset successfully.");
                header('Location: ' . baseUrl('admin/users.php'));
                exit;
            } else {
                $error = "Password must be at least 6 characters long.";
            }
        } elseif ($action === 'toggle_status') {
            $targetUserId = (int)($_POST['target_user_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? 'active';

            if ($targetUserId === $_SESSION['user_id'] && $newStatus !== 'active') {
                setFlash('danger', 'You cannot suspend your own active administrator account.');
            } else {
                $userRepo->updateStatus($targetUserId, $newStatus);
                $auditRepo->log($_SESSION['user_id'], 'USER_STATUS_UPDATED', 'users', $targetUserId, ['new_status' => $newStatus]);
                setFlash('success', 'User account status updated.');
            }
            header('Location: ' . baseUrl('admin/users.php'));
            exit;
        } elseif ($action === 'delete_user') {
            $id = (int)($_POST['user_id'] ?? 0);
            if ($id === $_SESSION['user_id']) {
                setFlash('danger', 'You cannot delete your own logged-in account.');
            } else {
                $userRepo->deleteAccount($id);
                $auditRepo->log($_SESSION['user_id'], 'ADMIN_DELETED_USER', 'users', $id);
                setFlash('success', 'User account permanently deleted.');
            }
            header('Location: ' . baseUrl('admin/users.php'));
            exit;
        }
    }
}

$search = trim($_GET['search'] ?? '');
$users = $userRepo->getAllUsers(100, 0, $search);

$pageTitle = "Admin User Accounts";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">User Account Management</h3>
        <p class="text-muted small mb-0">Create accounts, govern roles, reset credentials, and monitor taxpayer activity.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form method="GET" action="<?= baseUrl('admin/users.php') ?>" class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" name="search" value="<?= e($search) ?>" placeholder="Search name or email...">
            <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i></button>
        </form>
        <button type="button" class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="bi bi-person-plus-fill me-1"></i> Add New User
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
        <h5 class="fw-bold mb-0">Registered Taxpayer Accounts (<?= count($users) ?>)</h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-financial mb-0 align-middle small">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Employment</th>
                    <th>Last Active</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="text-muted small">#<?= $u['id'] ?></td>
                        <td class="fw-semibold text-dark"><?= e($u['name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-light text-dark border' ?>">
                                <?= strtoupper($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $u['status'] === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' ?>">
                                <?= ucfirst($u['status']) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= e(ucfirst(str_replace('_', ' ', $u['employment_category'] ?? 'Salaried'))) ?></td>
                        <td class="small text-muted"><?= !empty($u['last_login']) ? date('d M Y, h:i A', strtotime($u['last_login'])) : 'Never' ?></td>
                        <td class="text-end text-nowrap">
                            <!-- Edit Button -->
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='openEditUserModal(<?= json_encode($u) ?>)' title="Edit User">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <!-- Password Reset -->
                            <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= e($u['name']) ?>')" title="Reset Password">
                                <i class="bi bi-key"></i>
                            </button>
                            <!-- Toggle Status -->
                            <form method="POST" action="<?= baseUrl('admin/users.php') ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $u['status'] === 'active' ? 'inactive' : 'active' ?>">
                                <button type="submit" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success' ?> me-1" title="<?= $u['status'] === 'active' ? 'Deactivate Account' : 'Activate Account' ?>">
                                    <i class="bi <?= $u['status'] === 'active' ? 'bi-person-slash' : 'bi-person-check' ?>"></i>
                                </button>
                            </form>
                            <!-- Delete User -->
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" action="<?= baseUrl('admin/users.php') ?>" class="d-inline" onsubmit="return confirm('Permanently delete user #<?= $u['id'] ?> and all their records?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Account">
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

<!-- Modal: Create User -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('admin/users.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_user">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create New User Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name *</label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g. Vikram Malhotra">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address *</label>
                        <input type="email" class="form-control" name="email" required placeholder="vikram@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Initial Password *</label>
                        <input type="password" class="form-control" name="password" required placeholder="Min 8 characters">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">System Role</label>
                            <select class="form-select" name="role">
                                <option value="user" selected>Regular Taxpayer</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Account Status</label>
                            <select class="form-select" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('admin/users.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_u_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name *</label>
                        <input type="text" class="form-control" name="name" id="edit_u_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email (Read Only)</label>
                        <input type="email" class="form-control bg-light" id="edit_u_email" readonly>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">System Role</label>
                            <select class="form-select" name="role" id="edit_u_role">
                                <option value="user">Regular Taxpayer</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Account Status</label>
                            <select class="form-select" name="status" id="edit_u_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= baseUrl('admin/users.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_u_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Reset credentials for <strong id="reset_u_name_display"></strong>.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Password *</label>
                        <input type="password" class="form-control" name="new_password" required placeholder="Enter new strong password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3">Save New Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditUserModal(u) {
    document.getElementById('edit_u_id').value = u.id;
    document.getElementById('edit_u_name').value = u.name;
    document.getElementById('edit_u_email').value = u.email;
    document.getElementById('edit_u_role').value = u.role;
    document.getElementById('edit_u_status').value = u.status;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function openResetPasswordModal(id, name) {
    document.getElementById('reset_u_id').value = id;
    document.getElementById('reset_u_name_display').innerText = name;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
