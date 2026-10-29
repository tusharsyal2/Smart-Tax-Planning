<?php
/**
 * Page 1: Landing Page
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/config/app.php';

$pageTitle = "Smart Tax Planning Assistance | India's Intelligent Income Tax Planner";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5 bg-white border-bottom position-relative overflow-hidden">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-3 rounded-pill bg-primary-subtle text-primary border border-primary-subtle small fw-semibold">
                    <i class="bi bi-patch-check-fill"></i> Updated with Finance (No. 2) Act 2024 &amp; FY 2024-25 Slabs
                </div>
                <h1 class="display-4 fw-extrabold text-dark tracking-tight mb-3">
                    Demystify Indian Taxes. <br><span class="text-primary">Plan Smarter. Save More.</span>
                </h1>
                <p class="lead text-secondary mb-4">
                    Transform complex income tax calculations into an understandable, interactive financial roadmap. Accurately compare Old vs New Tax Regimes, identify untapped Section 80C &amp; 80D deductions, and simulate what-if scenarios in real time.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= baseUrl('register.php') ?>" class="btn btn-primary btn-lg px-4 shadow-sm">
                        <i class="bi bi-rocket-takeoff me-2"></i> Start Planning for Free
                    </a>
                    <a href="<?= baseUrl('login.php') ?>" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to Account
                    </a>
                </div>
                <div class="d-flex align-items-center gap-4 mt-4 pt-2 text-muted small">
                    <div><i class="bi bi-shield-lock-fill text-success me-1"></i> Private &amp; Secure</div>
                    <div><i class="bi bi-check2-circle text-primary me-1"></i> Slabs Verified (FY 24-25)</div>
                    <div><i class="bi bi-file-earmark-lock text-info me-1"></i> No PAN Mandatory</div>
                </div>
            </div>

            <div class="col-lg-5">
                <!-- Interactive Preview Card -->
                <div class="glass-card p-4 shadow-lg border">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">Demonstration Preview</span>
                        <span class="badge bg-success-subtle text-success fw-semibold px-2 py-1">FY 2024-25</span>
                    </div>
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-muted small">Illustrative Annual Gross Income</div>
                        <div class="fs-3 fw-bold text-dark">₹12,00,000</div>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 border rounded text-center bg-white">
                                <div class="text-muted small">Old Regime Tax</div>
                                <div class="fw-bold text-danger">₹1,12,008</div>
                                <div class="small text-muted">With ₹2.4L deductions</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded text-center bg-success-subtle border-success">
                                <div class="text-success small fw-semibold">New Regime Tax</div>
                                <div class="fw-bold text-success">₹79,664</div>
                                <div class="small text-success fw-semibold">₹75k Std Deduction</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-success d-flex align-items-center gap-2 mb-0 py-2 small">
                        <i class="bi bi-stars fs-5"></i>
                        <div>Estimated Savings with <strong>New Regime: ₹32,344</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center max-w-xl mx-auto mb-5">
            <span class="text-primary fw-bold text-uppercase small letter-spacing">Comprehensive Capabilities</span>
            <h2 class="fw-bold mt-1">Engineered for Indian Taxpayers</h2>
            <p class="text-muted">Everything you need to navigate Chapter VI-A deductions, salary components, and regime transitions.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="glass-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-primary-subtle text-primary mb-3">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <h5 class="fw-bold">Side-by-Side Regime Comparison</h5>
                    <p class="text-muted small">
                        Compare your exact tax liability under both the Old and New Tax Regimes. Understand the <strong>Breakeven Deduction Point</strong> where Old Regime outperforms New Regime.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="glass-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-success-subtle text-success mb-3">
                        <i class="bi bi-lightbulb"></i>
                    </div>
                    <h5 class="fw-bold">Actionable Tax-Saving Recommendations</h5>
                    <p class="text-muted small">
                        Identifies untapped deduction room across Section 80C, 80CCD(1B) NPS, 80D health insurance, and home loan interest with true marginal rate impact calculations.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="glass-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-info-subtle text-info mb-3">
                        <i class="bi bi-sliders"></i>
                    </div>
                    <h5 class="fw-bold">Interactive What-If Simulator</h5>
                    <p class="text-muted small">
                        Model hypothetical career moves: test salary appraisals, fresh insurance policies, or new PPF/NPS investments without modifying your baseline financial records.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="glass-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-warning-subtle text-warning mb-3">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <h5 class="fw-bold">5 Heads of Income Management</h5>
                    <p class="text-muted small">
                        Organize your salary payslips, rental income with standard 30% deduction, business profits, capital gains, and interest income in one unified dashboard.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="glass-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-danger-subtle text-danger mb-3">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h5 class="fw-bold">Statutory Tax Calendar</h5>
                    <p class="text-muted small">
                        Never miss an Advance Tax installment (June 15, Sept 15, Dec 15, March 15), ITR filing deadlines (July 31), or investment proof submissions.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="glass-card p-4 h-100 bg-white">
                    <div class="stat-icon bg-secondary-subtle text-secondary mb-3">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>
                    <h5 class="fw-bold">Audit-Ready Reports &amp; CSV</h5>
                    <p class="text-muted small">
                        Download comprehensive planning reports with printable layouts and sanitized CSV exports immune to formula injection risks.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="py-5 bg-white border-top border-bottom">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-primary fw-bold text-uppercase small letter-spacing">Workflow</span>
            <h2 class="fw-bold mt-1">How Smart Tax Planning Works</h2>
        </div>

        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="p-3">
                    <div class="fs-1 fw-bold text-primary mb-2">01</div>
                    <h5 class="fw-bold">Enter Financials</h5>
                    <p class="text-muted small">Input your annual salary, employer benefits, rental income, or interest receipts.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <div class="fs-1 fw-bold text-primary mb-2">02</div>
                    <h5 class="fw-bold">Track Deductions</h5>
                    <p class="text-muted small">Log investments in 80C, health policies in 80D, NPS, and home loan interest.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <div class="fs-1 fw-bold text-primary mb-2">03</div>
                    <h5 class="fw-bold">Compare Regimes</h5>
                    <p class="text-muted small">Our progressive tax engine runs verified rules for FY 24-25 and highlights maximum savings.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <div class="fs-1 fw-bold text-primary mb-2">04</div>
                    <h5 class="fw-bold">Take Action</h5>
                    <p class="text-muted small">Export your planning summary, submit proofs to your employer, and stay ahead of deadlines.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Target Personas -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-primary fw-bold text-uppercase small letter-spacing">Tailored For You</span>
            <h2 class="fw-bold mt-1">Designed for Every Taxpayer</h2>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="glass-card p-4 h-100 bg-white">
                    <div class="badge bg-primary mb-2">Salaried Employees</div>
                    <h5 class="fw-bold">Corporate &amp; Private Professionals</h5>
                    <ul class="text-muted small ps-3 mt-3">
                        <li class="mb-2">Maximize ₹75,000 standard deduction under the revised New Regime.</li>
                        <li class="mb-2">Compare Form 16 TDS deductions against advance tax.</li>
                        <li class="mb-2">Evaluate Section 80CCD(2) employer NPS benefits.</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="glass-card p-4 h-100 bg-white">
                    <div class="badge bg-success mb-2">Self-Employed &amp; Freelancers</div>
                    <h5 class="fw-bold">Consultants &amp; Business Owners</h5>
                    <ul class="text-muted small ps-3 mt-3">
                        <li class="mb-2">Calculate progressive tax on net professional receipts.</li>
                        <li class="mb-2">Schedule quarterly Advance Tax payments to prevent Section 234B/234C interest penalties.</li>
                        <li class="mb-2">Manage medical insurance and retirement investments.</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="glass-card p-4 h-100 bg-white">
                    <div class="badge bg-info mb-2">Students &amp; First-Time Earners</div>
                    <h5 class="fw-bold">Interns &amp; Young Graduates</h5>
                    <ul class="text-muted small ps-3 mt-3">
                        <li class="mb-2">Understand Section 87A rebate (zero tax up to ₹7 Lakhs taxable income).</li>
                        <li class="mb-2">Learn how Chapter VI-A deductions like Section 80E higher education loan interest work.</li>
                        <li class="mb-2">Build early financial discipline with no jargon.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-5 bg-white border-top">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-primary fw-bold text-uppercase small letter-spacing">Got Questions?</span>
            <h2 class="fw-bold mt-1">Frequently Asked Questions</h2>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item mb-2 border rounded">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Which tax regime is better for FY 2024-25?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small">
                                For most individuals with deductions under ₹3.75 Lakhs, the <strong>New Tax Regime</strong> is typically more beneficial due to widened slabs, standard deduction of ₹75,000, and Section 87A rebate up to ₹7,00,000. However, if you claim substantial home loan interest (₹2L) and full 80C/80D, our tool calculates your exact breakeven.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-2 border rounded">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Is my financial data kept safe and isolated?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small">
                                Yes. All records are isolated per user account with foreign key constraints, session protection, and prepared SQL statements. PAN is entirely optional and never required.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item mb-2 border rounded">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Does this application file my income tax return directly?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small">
                                No. Smart Tax Planning Assistance is an educational and financial planning platform. It generates estimates and planning reports, but actual return filing is performed on the official Income Tax e-filing portal (incometax.gov.in).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container py-4">
        <h2 class="fw-bold mb-3">Ready to Take Control of Your Taxes?</h2>
        <p class="lead mb-4 text-white-50">Join students, salaried professionals, and freelancers planning their finances with confidence.</p>
        <a href="<?= baseUrl('register.php') ?>" class="btn btn-light btn-lg px-5 fw-bold text-primary shadow">
            Create Free Account Now
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
