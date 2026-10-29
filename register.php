<?php
/**
 * Page 2: User Authentication - Register
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/AuditRepository.php';

if (isLoggedIn()) {
    header('Location: ' . baseUrl('dashboard.php'));
    exit;
}

$userRepo = new UserRepository();
$auditRepo = new AuditRepository();
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security token mismatch. Please refresh and try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $empCategory = $_POST['employment_category'] ?? 'salaried';
        $ageCategory = $_POST['age_category'] ?? 'below_60';
        $state = trim($_POST['state'] ?? 'Delhi');
        $fy = $_POST['financial_year'] ?? '2024-25';

        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            $error = "Please fill in all mandatory fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please provide a valid email address.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $passwordConfirm) {
            $error = "Passwords do not match. Please re-enter.";
        } else {
            // Check if user already exists
            $existing = $userRepo->findByEmail($email);
            if ($existing) {
                $error = "An account with this email address already exists. Please sign in instead.";
            } else {
                try {
                    $userId = $userRepo->create([
                        'name' => $name,
                        'email' => $email,
                        'password' => $password,
                        'employment_category' => $empCategory,
                        'age_category' => $ageCategory,
                        'state' => $state,
                        'financial_year' => $fy,
                        'default_regime' => 'new',
                        'role' => 'user'
                    ]);

                    $auditRepo->log($userId, 'USER_REGISTERED', 'users', $userId, ['email' => $email]);

                    // Automatically establish session
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['active_fy'] = $fy;

                    setFlash('success', "Registration successful! Welcome to Smart Tax Planning Assistance.");
                    header('Location: ' . baseUrl('dashboard.php'));
                    exit;
                } catch (Exception $e) {
                    $error = "Failed to create account: " . $e->getMessage();
                }
            }
        }
    }
}

$pageTitle = "Create Account | Smart Tax Planning Assistance";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="glass-card p-4 p-md-5 bg-white shadow-sm border">
                <div class="text-center mb-4">
                    <div class="stat-icon bg-primary-subtle text-primary mx-auto mb-2" style="width:54px;height:54px;font-size:1.6rem;">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Create Tax Planning Account</h3>
                    <p class="text-muted small">Personalized calculations and regime comparison in minutes</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('register.php') ?>" novalidate>
                    <?= csrfField() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label small fw-semibold">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($_POST['name'] ?? '') ?>" required placeholder="e.g. Rahul Sharma">
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label small fw-semibold">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="rahul@example.com">
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label small fw-semibold">Password (min 8 chars) *</label>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Create strong password">
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirm" class="form-label small fw-semibold">Confirm Password *</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required placeholder="Re-type password">
                        </div>

                        <div class="col-md-6">
                            <label for="employment_category" class="form-label small fw-semibold">Employment Type</label>
                            <select class="form-select" id="employment_category" name="employment_category">
                                <option value="salaried" <?= (($_POST['employment_category'] ?? '') === 'salaried') ? 'selected' : '' ?>>Salaried Employee</option>
                                <option value="professional" <?= (($_POST['employment_category'] ?? '') === 'professional') ? 'selected' : '' ?>>Professional / Consultant</option>
                                <option value="self_employed" <?= (($_POST['employment_category'] ?? '') === 'self_employed') ? 'selected' : '' ?>>Self-Employed / Business</option>
                                <option value="student" <?= (($_POST['employment_category'] ?? '') === 'student') ? 'selected' : '' ?>>Student / Intern</option>
                                <option value="senior_citizen" <?= (($_POST['employment_category'] ?? '') === 'senior_citizen') ? 'selected' : '' ?>>Senior Citizen (60+)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="age_category" class="form-label small fw-semibold">Age Group</label>
                            <select class="form-select" id="age_category" name="age_category">
                                <option value="below_60" <?= (($_POST['age_category'] ?? '') === 'below_60') ? 'selected' : '' ?>>Below 60 Years</option>
                                <option value="senior_60_80" <?= (($_POST['age_category'] ?? '') === 'senior_60_80') ? 'selected' : '' ?>>Senior (60 to 80 Years)</option>
                                <option value="super_senior_80" <?= (($_POST['age_category'] ?? '') === 'super_senior_80') ? 'selected' : '' ?>>Super Senior (80+ Years)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="state" class="form-label small fw-semibold">State of Residence</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?= e($_POST['state'] ?? 'Delhi') ?>" placeholder="e.g. Maharashtra, Karnataka">
                        </div>

                        <div class="col-md-6">
                            <label for="financial_year" class="form-label small fw-semibold">Target Financial Year</label>
                            <select class="form-select" id="financial_year" name="financial_year">
                                <option value="2024-25" selected>FY 2024-25 (AY 2025-26)</option>
                                <option value="2023-24">FY 2023-24 (AY 2024-25)</option>
                                <option value="2025-26">FY 2025-26 (AY 2026-27)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100 fs-6 fw-semibold">
                            <i class="bi bi-person-check-fill me-1"></i> Register &amp; Open Tax Dashboard
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-center text-muted small">
                    Already registered? <a href="<?= baseUrl('login.php') ?>" class="fw-semibold text-primary">Sign In here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
