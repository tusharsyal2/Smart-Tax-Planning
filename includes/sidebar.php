<?php
/**
 * Dashboard Unified Sidebar Component
 * Supports both Standard User Navigation and Full Administrator Navigation
 * Smart Tax Planning Assistance
 */

$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$user = currentUser();
$isAdmin = ($user['role'] ?? '') === 'admin';
$isAdminDir = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
?>
<aside class="app-sidebar no-print">
    <!-- Brand Logo -->
    <a href="<?= ($isAdminDir || $isAdmin) ? baseUrl('admin/dashboard.php') : baseUrl('dashboard.php') ?>" class="sidebar-brand">
        <i class="bi bi-wallet2 text-primary"></i>
        <span>Smart Tax Plan</span>
        <?php if ($isAdminDir || $isAdmin): ?>
            <span class="badge bg-danger small ms-auto" style="font-size:0.65rem;">ADMIN</span>
        <?php endif; ?>
    </a>

    <!-- Navigation Menu -->
    <div class="sidebar-menu">
        <?php if ($isAdminDir || $isAdmin): ?>
            <!-- ================= ADMIN NAVIGATION MENU ================= -->
            <div class="sidebar-section-title text-danger fw-bold">
                <i class="bi bi-shield-lock-fill me-1"></i> System Administration
            </div>
            <a href="<?= baseUrl('admin/dashboard.php') ?>" class="sidebar-link <?= ($currentScript === 'dashboard.php' && $isAdminDir) ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Admin Health</span>
            </a>
            <a href="<?= baseUrl('admin/tax_rules.php') ?>" class="sidebar-link <?= ($currentScript === 'tax_rules.php') ? 'active' : '' ?>">
                <i class="bi bi-card-checklist"></i>
                <span>Tax Rules &amp; Slabs</span>
            </a>
            <a href="<?= baseUrl('admin/provisions.php') ?>" class="sidebar-link <?= ($currentScript === 'provisions.php') ? 'active' : '' ?>">
                <i class="bi bi-piggy-bank"></i>
                <span>Deduction Provisions</span>
            </a>
            <a href="<?= baseUrl('admin/calendar.php') ?>" class="sidebar-link <?= ($currentScript === 'calendar.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i>
                <span>Statutory Calendar</span>
            </a>
            <a href="<?= baseUrl('admin/users.php') ?>" class="sidebar-link <?= ($currentScript === 'users.php') ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>User Management</span>
            </a>
            <a href="<?= baseUrl('admin/audit_logs.php') ?>" class="sidebar-link <?= ($currentScript === 'audit_logs.php') ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i>
                <span>Audit Logs</span>
            </a>

            <div class="sidebar-section-title mt-3">User Experience Preview</div>
            <a href="<?= baseUrl('dashboard.php') ?>" class="sidebar-link <?= ($currentScript === 'dashboard.php' && !$isAdminDir) ? 'active' : '' ?>">
                <i class="bi bi-columns-gap"></i>
                <span>User Dashboard</span>
            </a>
            <a href="<?= baseUrl('tax_calculator.php') ?>" class="sidebar-link <?= ($currentScript === 'tax_calculator.php') ? 'active' : '' ?>">
                <i class="bi bi-calculator"></i>
                <span>Tax Calculator</span>
            </a>
            <a href="<?= baseUrl('regime_comparison.php') ?>" class="sidebar-link <?= ($currentScript === 'regime_comparison.php') ? 'active' : '' ?>">
                <i class="bi bi-arrow-left-right"></i>
                <span>Regime Comparison</span>
            </a>
            <a href="<?= baseUrl('admin/switch.php?to=user') ?>" class="sidebar-link text-secondary">
                <i class="bi bi-person-circle"></i>
                <span>Switch to User View</span>
            </a>

        <?php else: ?>
            <!-- ================= USER NAVIGATION MENU ================= -->
            <div class="sidebar-section-title">Overview</div>
            <a href="<?= baseUrl('dashboard.php') ?>" class="sidebar-link <?= ($currentScript === 'dashboard.php' && !$isAdminDir) ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            <div class="sidebar-section-title">Tax Planning &amp; Analysis</div>
            <a href="<?= baseUrl('tax_calculator.php') ?>" class="sidebar-link <?= ($currentScript === 'tax_calculator.php') ? 'active' : '' ?>">
                <i class="bi bi-calculator"></i>
                <span>Tax Calculator</span>
            </a>
            <a href="<?= baseUrl('regime_comparison.php') ?>" class="sidebar-link <?= ($currentScript === 'regime_comparison.php') ? 'active' : '' ?>">
                <i class="bi bi-arrow-left-right"></i>
                <span>Regime Comparison</span>
            </a>
            <a href="<?= baseUrl('recommendations.php') ?>" class="sidebar-link <?= ($currentScript === 'recommendations.php') ? 'active' : '' ?>">
                <i class="bi bi-lightbulb"></i>
                <span>Tax Savings &amp; Tips</span>
            </a>
            <a href="<?= baseUrl('tax_simulator.php') ?>" class="sidebar-link <?= ($currentScript === 'tax_simulator.php') ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i>
                <span>What-If Simulator</span>
            </a>

            <div class="sidebar-section-title">Financial Records</div>
            <a href="<?= baseUrl('income.php') ?>" class="sidebar-link <?= ($currentScript === 'income.php') ? 'active' : '' ?>">
                <i class="bi bi-cash-stack"></i>
                <span>Income Management</span>
            </a>
            <a href="<?= baseUrl('deductions.php') ?>" class="sidebar-link <?= ($currentScript === 'deductions.php') ? 'active' : '' ?>">
                <i class="bi bi-piggy-bank"></i>
                <span>Deductions &amp; 80C</span>
            </a>

            <div class="sidebar-section-title">Planning &amp; Tools</div>
            <a href="<?= baseUrl('reminders.php') ?>" class="sidebar-link <?= ($currentScript === 'reminders.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-event"></i>
                <span>Tax Calendar</span>
            </a>
            <a href="<?= baseUrl('tax_history.php') ?>" class="sidebar-link <?= ($currentScript === 'tax_history.php') ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>
                <span>Calculation History</span>
            </a>
            <a href="<?= baseUrl('reports.php') ?>" class="sidebar-link <?= ($currentScript === 'reports.php') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Financial Reports</span>
            </a>

            <div class="sidebar-section-title">Account</div>
            <a href="<?= baseUrl('profile.php') ?>" class="sidebar-link <?= ($currentScript === 'profile.php') ? 'active' : '' ?>">
                <i class="bi bi-person-badge"></i>
                <span>Financial Profile</span>
            </a>
            <a href="<?= baseUrl('settings.php') ?>" class="sidebar-link <?= ($currentScript === 'settings.php') ? 'active' : '' ?>">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>

            <div class="sidebar-section-title text-danger"><i class="bi bi-shield-lock me-1"></i>Administration</div>
            <a href="<?= baseUrl('admin/switch.php?to=admin') ?>" class="sidebar-link text-danger fw-semibold">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Open Admin Portal</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- User Profile Footer in Sidebar -->
    <div class="sidebar-user-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 overflow-hidden">
                <div class="stat-icon bg-primary-subtle text-primary" style="width:36px;height:36px;font-size:1.1rem;">
                    <i class="bi <?= ($isAdminDir || $isAdmin) ? 'bi-shield-check text-danger' : 'bi-person text-primary' ?>"></i>
                </div>
                <div class="text-truncate">
                    <div class="fw-semibold text-truncate small"><?= e($user['name'] ?? 'User') ?></div>
                    <div class="text-muted text-truncate" style="font-size:0.75rem;"><?= e($user['email'] ?? '') ?></div>
                </div>
            </div>
            <a href="<?= baseUrl('logout.php') ?>" class="text-danger p-1" title="Sign Out">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>
    </div>
</aside>
