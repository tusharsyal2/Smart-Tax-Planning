<?php
/**
 * Page 2: User Authentication - Login
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/AuditRepository.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . baseUrl('dashboard.php'));
    exit;
}

$userRepo = new UserRepository();
$auditRepo = new AuditRepository();
$error = null;

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security validation failed (invalid CSRF token). Please refresh and try again.";
    } else {
        // Rate limiting check
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttemptTime = $_SESSION['last_attempt_time'] ?? 0;

        if ($attempts >= 5 && (time() - $lastAttemptTime) < 300) {
            $remaining = 300 - (time() - $lastAttemptTime);
            $error = "Too many failed login attempts. Please wait " . ceil($remaining / 60) . " minute(s) before trying again.";
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = "Please provide both email address and password.";
            } else {
                $user = $userRepo->findByEmail($email);

                if ($user && password_verify($password, $user['password_hash'])) {
                    if ($user['status'] !== 'active') {
                        $error = "Your account is currently inactive or suspended. Please contact the administrator.";
                    } else {
                        // Reset rate limiting
                        unset($_SESSION['login_attempts'], $_SESSION['last_attempt_time']);

                        // Session Fixation Protection: Regenerate session ID
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['active_fy'] = '2024-25';

                        $userRepo->updateLastLogin($user['id']);
                        $auditRepo->log($user['id'], 'USER_LOGIN', 'users', $user['id'], ['email' => $user['email']]);

                        setFlash('success', "Welcome back, " . htmlspecialchars($user['name']) . "!");
                        if ($user['role'] === 'admin') {
                            header('Location: ' . baseUrl('admin/dashboard.php'));
                        } else {
                            header('Location: ' . baseUrl('dashboard.php'));
                        }
                        exit;
                    }
                } else {
                    $_SESSION['login_attempts'] = ($attempts + 1);
                    $_SESSION['last_attempt_time'] = time();
                    $error = "Invalid email or password. Please check your credentials.";
                }
            }
        }
    }
}

$pageTitle = "Sign In | Smart Tax Planning Assistance";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="glass-card p-4 p-md-5 bg-white shadow-sm border">
                <div class="text-center mb-4">
                    <div class="stat-icon bg-primary-subtle text-primary mx-auto mb-2" style="width:54px;height:54px;font-size:1.6rem;">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Sign In</h3>
                    <p class="text-muted small">Access your personal tax planner and reports</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-1"></i> <?= e($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= baseUrl('login.php') ?>" novalidate>
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus placeholder="name@example.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label small fw-semibold mb-0">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg fs-6 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </div>
                </form>

                <!-- Demo Login Shortcut Box -->
                <div class="mt-4 pt-3 border-top text-center">
                    <div class="text-muted small mb-2 fw-semibold">Quick Demo Logins:</div>
                    <div class="d-flex flex-wrap justify-content-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="fillDemo('salaried.demo@smarttax.local', 'Demo@123')">
                            Salaried Demo
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="fillDemo('admin@smarttax.local', 'Admin@123')">
                            Admin Demo
                        </button>
                    </div>
                </div>

                <div class="mt-4 text-center text-muted small">
                    Don't have an account? <a href="<?= baseUrl('register.php') ?>" class="fw-semibold text-primary">Create Account</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemo(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
