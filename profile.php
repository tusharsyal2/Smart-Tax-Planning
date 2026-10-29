<?php
/**
 * Page 4: Personal and Financial Profile
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/AuditRepository.php';

requireAuth();

$userId = $_SESSION['user_id'];
$userRepo = new UserRepository();
$auditRepo = new AuditRepository();

$error = null;
$success = null;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = "Security validation failed. Please reload the page.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $emp = $_POST['employment_category'] ?? 'salaried';
        $res = $_POST['residential_status'] ?? 'resident';
        $age = $_POST['age_category'] ?? 'below_60';
        $state = trim($_POST['state'] ?? 'Delhi');
        $pan = strtoupper(trim($_POST['pan_number'] ?? ''));
        $regime = $_POST['default_regime'] ?? 'new';
        $fy = $_POST['financial_year'] ?? '2024-25';

        // PAN validation (if provided)
        if (!empty($pan) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
            $error = "Invalid PAN format. Indian PAN must follow the 10-character alphanumeric pattern (e.g. ABCDE1234F).";
        } elseif (empty($name)) {
            $error = "Full Name cannot be empty.";
        } else {
            try {
                $userRepo->updateProfile($userId, [
                    'name' => $name,
                    'employment_category' => $emp,
                    'residential_status' => $res,
                    'age_category' => $age,
                    'state' => $state,
                    'pan_number' => $pan,
                    'default_regime' => $regime,
                    'financial_year' => $fy
                ]);

                $_SESSION['user_name'] = $name;
                $_SESSION['active_fy'] = $fy;

                $auditRepo->log($userId, 'PROFILE_UPDATED', 'financial_profiles', $userId, ['name' => $name, 'fy' => $fy]);
                setFlash('success', 'Your personal and financial profile has been updated successfully.');
                header('Location: ' . baseUrl('profile.php'));
                exit;
            } catch (Exception $e) {
                $error = "Failed to update profile: " . $e->getMessage();
            }
        }
    }
}

$profile = $userRepo->findById($userId);
$pageTitle = "Personal & Financial Profile";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Taxpayer Profile</h3>
                <p class="text-muted small mb-0">Manage demographic and tax-status parameters for accurate slab evaluation.</p>
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

        <div class="glass-card p-4 p-md-5 bg-white border shadow-sm">
            <form method="POST" action="<?= baseUrl('profile.php') ?>">
                <?= csrfField() ?>

                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Personal Identification</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="name" class="form-label small fw-semibold">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= e($profile['name']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-semibold">Email Address (Read-Only)</label>
                        <input type="email" class="form-control bg-light" id="email" value="<?= e($profile['email']) ?>" readonly>
                        <div class="form-text" style="font-size:0.75rem;">Account email used for secure login.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="pan_number" class="form-label small fw-semibold">Permanent Account Number (PAN) - Optional</label>
                        <input type="text" class="form-control text-uppercase" id="pan_number" name="pan_number" maxlength="10" value="<?= e($profile['pan_number'] ?? '') ?>" placeholder="ABCDE1234F">
                        <div class="form-text" style="font-size:0.75rem;">Optional for simulations. Masked on generated planning reports.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="state" class="form-label small fw-semibold">Residential State / Union Territory</label>
                        <input type="text" class="form-control" id="state" name="state" value="<?= e($profile['state'] ?? 'Delhi') ?>">
                    </div>
                </div>

                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Income Tax Classification</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="employment_category" class="form-label small fw-semibold">Employment Category</label>
                        <select class="form-select" id="employment_category" name="employment_category">
                            <option value="salaried" <?= ($profile['employment_category'] ?? '') === 'salaried' ? 'selected' : '' ?>>Salaried Employee (Form 16)</option>
                            <option value="professional" <?= ($profile['employment_category'] ?? '') === 'professional' ? 'selected' : '' ?>>Professional / Freelancer / Consultant</option>
                            <option value="self_employed" <?= ($profile['employment_category'] ?? '') === 'self_employed' ? 'selected' : '' ?>>Self-Employed / Business Owner</option>
                            <option value="student" <?= ($profile['employment_category'] ?? '') === 'student' ? 'selected' : '' ?>>Student / Intern</option>
                            <option value="senior_citizen" <?= ($profile['employment_category'] ?? '') === 'senior_citizen' ? 'selected' : '' ?>>Senior Citizen (Retired/Pensioner)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="age_category" class="form-label small fw-semibold">Age Group (Affects Old Regime Slabs)</label>
                        <select class="form-select" id="age_category" name="age_category">
                            <option value="below_60" <?= ($profile['age_category'] ?? '') === 'below_60' ? 'selected' : '' ?>>Below 60 Years (General Individual)</option>
                            <option value="senior_60_80" <?= ($profile['age_category'] ?? '') === 'senior_60_80' ? 'selected' : '' ?>>Senior Citizen (60 to 80 Years - ₹3L Nil slab)</option>
                            <option value="super_senior_80" <?= ($profile['age_category'] ?? '') === 'super_senior_80' ? 'selected' : '' ?>>Super Senior (80+ Years - ₹5L Nil slab)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="residential_status" class="form-label small fw-semibold">Residential Status for Tax Purposes</label>
                        <select class="form-select" id="residential_status" name="residential_status">
                            <option value="resident" <?= ($profile['residential_status'] ?? '') === 'resident' ? 'selected' : '' ?>>Resident Indian (ROR)</option>
                            <option value="nri" <?= ($profile['residential_status'] ?? '') === 'nri' ? 'selected' : '' ?>>Non-Resident Indian (NRI)</option>
                            <option value="rnor" <?= ($profile['residential_status'] ?? '') === 'rnor' ? 'selected' : '' ?>>Resident but Not Ordinarily Resident (RNOR)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="financial_year" class="form-label small fw-semibold">Active Financial Year</label>
                        <select class="form-select" id="financial_year" name="financial_year">
                            <option value="2024-25" <?= ($profile['financial_year'] ?? '') === '2024-25' ? 'selected' : '' ?>>FY 2024-25 (AY 2025-26) - Revised Slabs</option>
                            <option value="2023-24" <?= ($profile['financial_year'] ?? '') === '2023-24' ? 'selected' : '' ?>>FY 2023-24 (AY 2024-25)</option>
                            <option value="2025-26" <?= ($profile['financial_year'] ?? '') === '2025-26' ? 'selected' : '' ?>>FY 2025-26 (AY 2026-27)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="default_regime" class="form-label small fw-semibold">Preferred Default Regime</label>
                        <select class="form-select" id="default_regime" name="default_regime">
                            <option value="new" <?= ($profile['default_regime'] ?? '') === 'new' ? 'selected' : '' ?>>New Tax Regime (Section 115BAC)</option>
                            <option value="old" <?= ($profile['default_regime'] ?? '') === 'old' ? 'selected' : '' ?>>Old Tax Regime (With Chapter VI-A)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check2-circle me-1"></i> Save Profile Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
