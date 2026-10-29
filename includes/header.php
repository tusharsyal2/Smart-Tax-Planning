<?php
/**
 * Global Header Component
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/app.php';

$pageTitle = $pageTitle ?? 'Smart Tax Planning Assistance';
$user = currentUser();
$flash = getFlash();
$activeFy = $_SESSION['active_fy'] ?? '2024-25';
$isAdmin = ($user['role'] ?? '') === 'admin';
$isAdminDir = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Personal Income Tax Planning & Estimation Assistance for Indian Taxpayers. Compare Old vs New Tax Regime, optimize Section 80C, 80D, NPS deductions, and simulate tax savings.">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e($pageTitle) ?> | Smart Tax Planning</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Theme Styles -->
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/admin.css') ?>">

    <!-- Chart.js 4.4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
</head>
<body>

<?php if (isLoggedIn()): ?>
<div class="app-wrapper">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Container -->
    <div class="app-main">
        <!-- Top Navbar -->
        <header class="app-navbar no-print">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-light d-lg-none" id="sidebarToggle" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-md-flex align-items-center gap-2">
                    <?php if ($isAdminDir || $isAdmin): ?>
                        <span class="badge bg-danger text-white px-2 py-1">
                            <i class="bi bi-shield-lock-fill me-1"></i> Admin Console
                        </span>
                    <?php else: ?>
                        <span class="badge bg-light text-dark border px-2 py-1">
                            <i class="bi bi-calendar3 me-1 text-primary"></i> FY: <strong><?= e($activeFy) ?></strong>
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="bi bi-shield-check me-1"></i> Slabs Verified
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <?php if ($isAdminDir || $isAdmin): ?>
                    <a href="<?= baseUrl('admin/switch.php?to=user') ?>" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1" title="Switch to regular user view">
                        <i class="bi bi-person"></i> Switch to User View
                    </a>
                    <a href="<?= baseUrl('admin/tax_rules.php') ?>" class="btn btn-sm btn-danger d-none d-sm-inline-flex align-items-center gap-1">
                        <i class="bi bi-gear-wide-connected"></i> Configure Tax Rules
                    </a>
                <?php else: ?>
                    <a href="<?= baseUrl('admin/switch.php?to=admin') ?>" class="btn btn-sm btn-outline-danger d-none d-sm-inline-flex align-items-center gap-1" title="Open Administrator Control Panel">
                        <i class="bi bi-shield-lock"></i> Admin Portal
                    </a>
                    <a href="<?= baseUrl('tax_calculator.php') ?>" class="btn btn-sm btn-outline-primary d-none d-sm-inline-flex align-items-center gap-1">
                        <i class="bi bi-calculator"></i> Quick Calculator
                    </a>
                    <a href="<?= baseUrl('regime_comparison.php') ?>" class="btn btn-sm btn-primary d-none d-sm-inline-flex align-items-center gap-1">
                        <i class="bi bi-arrow-left-right"></i> Compare Regimes
                    </a>
                <?php endif; ?>

                <!-- User Dropdown Menu -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-6 text-primary"></i>
                        <span class="d-none d-md-inline fw-semibold"><?= e($user['name'] ?? 'Account') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header"><?= e($user['email'] ?? '') ?> (<?= strtoupper($user['role'] ?? 'user') ?>)</h6></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('profile.php') ?>"><i class="bi bi-person me-2"></i>My Financial Profile</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('settings.php') ?>"><i class="bi bi-gear me-2"></i>User Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($isAdmin): ?>
                            <li><a class="dropdown-item text-danger fw-semibold" href="<?= baseUrl('admin/dashboard.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                            <li><a class="dropdown-item text-secondary" href="<?= baseUrl('admin/switch.php?to=user') ?>"><i class="bi bi-arrow-left-circle me-2"></i>Switch to User View</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item text-danger fw-semibold" href="<?= baseUrl('admin/switch.php?to=admin') ?>"><i class="bi bi-shield-lock me-2"></i>Open Admin Portal</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= baseUrl('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="app-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($flash['type'] === 'success'): ?>
                            <i class="bi bi-check-circle-fill fs-5"></i>
                        <?php elseif ($flash['type'] === 'danger'): ?>
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <?php else: ?>
                            <i class="bi bi-info-circle-fill fs-5"></i>
                        <?php endif; ?>
                        <div><?= e($flash['message']) ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

<?php else: ?>
    <!-- Public Header for Landing, Login, Register -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary" href="<?= baseUrl('index.php') ?>">
                <i class="bi bi-pie-chart-fill fs-3 text-primary"></i>
                <span>Smart Tax Planning</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item"><a class="nav-link" href="<?= baseUrl('index.php#features') ?>">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= baseUrl('index.php#how-it-works') ?>">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= baseUrl('index.php#faq') ?>">FAQ</a></li>
                    <li class="nav-item ms-lg-2">
                        <a href="<?= baseUrl('login.php') ?>" class="btn btn-outline-primary px-3">Sign In</a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= baseUrl('register.php') ?>" class="btn btn-primary px-3">Get Started Free</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main>
        <?php if ($flash): ?>
            <div class="container mt-3">
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
<?php endif; ?>
