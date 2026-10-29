-- ============================================================================
-- Smart Tax Planning Assistance
-- Seed Data: Tax Rules, Slabs, Provisions, Calendar, and Demo Users
-- ============================================================================

USE `smart_tax_planning`;

-- ----------------------------------------------------------------------------
-- 1. Application Settings
-- ----------------------------------------------------------------------------
INSERT INTO `application_settings` (`setting_key`, `setting_value`, `description`) VALUES
('app_name', 'Smart Tax Planning Assistance', 'Official application name'),
('default_financial_year', '2024-25', 'Active financial year default'),
('default_currency', 'INR', 'Currency symbol and code'),
('cess_percentage', '4.0', 'Health & Education Cess percentage on income tax'),
('contact_email', 'support@smarttax.local', 'Support and contact email address'),
('maintenance_mode', '0', 'System maintenance toggle');

-- ----------------------------------------------------------------------------
-- 2. Tax Provisions (Chapter VI-A & Salary Exemptions)
-- ----------------------------------------------------------------------------
INSERT INTO `tax_provisions` (`provision_code`, `provision_name`, `applicable_regime`, `maximum_limit`, `eligibility_conditions`, `documentation_required`, `source_reference`) VALUES
('80C', 'Deduction for Investments & Savings', 'old_only', 150000.00, 
 'Available for investments in PPF, EPF, ELSS mutual funds, Life Insurance Premiums, NSC, SCSS, 5-year tax saving FDs, Sukanya Samriddhi Yojana, principal repayment of housing loan, and tuition fees for up to 2 children.',
 'Deposit receipts, ELSS statement, Premium payment receipt, Home loan provisional certificate',
 'Income Tax Act 1961, Section 80C'),

('80CCD_1B', 'Additional NPS Contribution', 'old_only', 50000.00,
 'Deduction for voluntary contribution made towards National Pension System (NPS) Tier 1 account, in addition to the Rs 1,50,000 limit of Section 80C.',
 'PRAN statement, NPS transaction receipt',
 'Income Tax Act 1961, Section 80CCD(1B)'),

('80CCD_2', 'Employer Contribution to NPS', 'both', NULL,
 'Employer contribution to employee NPS account up to 10% of Salary (Basic + DA) for private sector employees, and up to 14% for Central/State Government employees. Available under BOTH Old and New Regimes.',
 'Form 16 Part B / Salary payslips showing employer NPS contribution',
 'Income Tax Act 1961, Section 80CCD(2) read with Section 115BAC'),

('80D_SELF', 'Health Insurance (Self, Spouse, Children)', 'old_only', 25000.00,
 'Premium paid towards health insurance policies or preventive health check-up (up to Rs 5,000 within ceiling) for self, spouse, and dependent children. Limit is Rs 50,000 if self or spouse is senior citizen (60+).',
 'Health insurance 80D tax certificate, policy premium receipt',
 'Income Tax Act 1961, Section 80D'),

('80D_PARENTS', 'Health Insurance (Parents)', 'old_only', 25000.00,
 'Premium paid towards medical insurance for parents. Limit is Rs 50,000 if either parent is senior citizen (60+).',
 'Health insurance certificate in parents name',
 'Income Tax Act 1961, Section 80D'),

('80E', 'Interest on Higher Education Loan', 'old_only', NULL,
 'Deduction for entire interest paid on loan taken from financial institutions or approved charitable institutions for higher education of self, spouse, or children. No upper limit on amount for 8 consecutive assessment years.',
 'Interest certificate from the lending financial institution',
 'Income Tax Act 1961, Section 80E'),

('80G', 'Donations to Approved Charitable Funds', 'old_only', NULL,
 'Deduction for donations to eligible funds (PM CARES, PM National Relief Fund, approved NGOs). Deduction is 50% or 100% of donation amount, subject to qualifying limits where applicable. Cash donation above Rs 2,000 not allowed.',
 'Receipt with 80G registration number and Form 10BE certificate',
 'Income Tax Act 1961, Section 80G'),

('80TTA', 'Interest on Savings Bank Accounts', 'old_only', 10000.00,
 'Deduction up to Rs 10,000 on interest earned from savings bank accounts or post office savings for individual taxpayers below 60 years.',
 'Bank account statement or interest certificate',
 'Income Tax Act 1961, Section 80TTA'),

('80TTB', 'Interest on Deposits for Senior Citizens', 'old_only', 50000.00,
 'Deduction up to Rs 50,000 on interest income from savings accounts, fixed deposits (FD), and recurring deposits (RD) for resident individuals aged 60 years or above.',
 'Form 16A / Bank TDS certificate / Bank statement',
 'Income Tax Act 1961, Section 80TTB'),

('24B', 'Interest on Housing Loan (Self-Occupied)', 'old_only', 200000.00,
 'Deduction for interest payable on housing loan borrowed for acquisition or construction of self-occupied residential property. Maximum deduction is Rs 2,00,000 per financial year.',
 'Housing loan interest certificate from lender mentioning principal and interest breakup',
 'Income Tax Act 1961, Section 24(b)'),

('STANDARD_DEDUCTION', 'Standard Deduction on Salary Income', 'both', 75000.00,
 'Flat standard deduction allowed to salaried employees and pensioners. For FY 2024-25 / FY 2025-26 under New Regime, standard deduction is Rs 75,000. Under Old Regime, it is Rs 50,000.',
 'Auto-applied against gross salary income in Form 16',
 'Finance (No. 2) Act 2024 amendments to Section 16(ia)');

-- ----------------------------------------------------------------------------
-- 3. Tax Rule Sets & Progressive Slabs
-- ----------------------------------------------------------------------------

-- FY 2023-24 (AY 2024-25) OLD REGIME
INSERT INTO `tax_rule_sets` 
(`id`, `financial_year`, `assessment_year`, `tax_regime`, `taxpayer_category`, `rule_version`, `standard_deduction`, `rebate_87a_limit`, `rebate_87a_max`, `cess_rate`, `effective_date`, `verification_status`, `source_reference`, `notes`) 
VALUES 
(1, '2023-24', '2024-25', 'old', 'individual', '1.0', 50000.00, 500000.00, 12500.00, 0.0400, '2023-04-01', 'verified', 'Finance Act 2023 / Income Tax Act 1961', 'Old regime rules for General Individual below 60 years');

INSERT INTO `tax_slabs` (`rule_set_id`, `slab_order`, `lower_bound`, `upper_bound`, `rate`, `conditions`) VALUES
(1, 1, 0.00, 250000.00, 0.0000, 'Up to Rs 2.5 Lakhs: Nil'),
(1, 2, 250000.00, 500000.00, 0.0500, 'Rs 2.5 Lakhs to Rs 5 Lakhs: 5%'),
(1, 3, 500000.00, 1000000.00, 0.2000, 'Rs 5 Lakhs to Rs 10 Lakhs: 20%'),
(1, 4, 1000000.00, NULL, 0.3000, 'Above Rs 10 Lakhs: 30%');

-- FY 2023-24 (AY 2024-25) NEW REGIME (Section 115BAC)
INSERT INTO `tax_rule_sets` 
(`id`, `financial_year`, `assessment_year`, `tax_regime`, `taxpayer_category`, `rule_version`, `standard_deduction`, `rebate_87a_limit`, `rebate_87a_max`, `cess_rate`, `effective_date`, `verification_status`, `source_reference`, `notes`) 
VALUES 
(2, '2023-24', '2024-25', 'new', 'individual', '1.0', 50000.00, 700000.00, 25000.00, 0.0400, '2023-04-01', 'verified', 'Finance Act 2023 Section 115BAC', 'Default tax regime under Finance Act 2023');

INSERT INTO `tax_slabs` (`rule_set_id`, `slab_order`, `lower_bound`, `upper_bound`, `rate`, `conditions`) VALUES
(2, 1, 0.00, 300000.00, 0.0000, 'Up to Rs 3 Lakhs: Nil'),
(2, 2, 300000.00, 600000.00, 0.0500, 'Rs 3 Lakhs to Rs 6 Lakhs: 5%'),
(2, 3, 600000.00, 900000.00, 0.1000, 'Rs 6 Lakhs to Rs 9 Lakhs: 10%'),
(2, 4, 900000.00, 1200000.00, 0.1500, 'Rs 9 Lakhs to Rs 12 Lakhs: 15%'),
(2, 5, 1200000.00, 1500000.00, 0.2000, 'Rs 12 Lakhs to Rs 15 Lakhs: 20%'),
(2, 6, 1500000.00, NULL, 0.3000, 'Above Rs 15 Lakhs: 30%');

-- FY 2024-25 (AY 2025-26) OLD REGIME
INSERT INTO `tax_rule_sets` 
(`id`, `financial_year`, `assessment_year`, `tax_regime`, `taxpayer_category`, `rule_version`, `standard_deduction`, `rebate_87a_limit`, `rebate_87a_max`, `cess_rate`, `effective_date`, `verification_status`, `source_reference`, `notes`) 
VALUES 
(3, '2024-25', '2025-26', 'old', 'individual', '1.0', 50000.00, 500000.00, 12500.00, 0.0400, '2024-04-01', 'verified', 'Finance (No. 2) Act 2024', 'Old regime retained without slab modification');

INSERT INTO `tax_slabs` (`rule_set_id`, `slab_order`, `lower_bound`, `upper_bound`, `rate`, `conditions`) VALUES
(3, 1, 0.00, 250000.00, 0.0000, 'Up to Rs 2.5 Lakhs: Nil'),
(3, 2, 250000.00, 500000.00, 0.0500, 'Rs 2.5 Lakhs to Rs 5 Lakhs: 5%'),
(3, 3, 500000.00, 1000000.00, 0.2000, 'Rs 5 Lakhs to Rs 10 Lakhs: 20%'),
(3, 4, 1000000.00, NULL, 0.3000, 'Above Rs 10 Lakhs: 30%');

-- FY 2024-25 (AY 2025-26) NEW REGIME (Finance (No. 2) Act 2024)
INSERT INTO `tax_rule_sets` 
(`id`, `financial_year`, `assessment_year`, `tax_regime`, `taxpayer_category`, `rule_version`, `standard_deduction`, `rebate_87a_limit`, `rebate_87a_max`, `cess_rate`, `effective_date`, `verification_status`, `source_reference`, `notes`) 
VALUES 
(4, '2024-25', '2025-26', 'new', 'individual', '1.0', 75000.00, 700000.00, 25000.00, 0.0400, '2024-04-01', 'verified', 'Finance (No. 2) Act 2024 Section 115BAC(1A)', 'Revised slabs and standard deduction increased to Rs 75,000');

INSERT INTO `tax_slabs` (`rule_set_id`, `slab_order`, `lower_bound`, `upper_bound`, `rate`, `conditions`) VALUES
(4, 1, 0.00, 300000.00, 0.0000, 'Up to Rs 3 Lakhs: Nil'),
(4, 2, 300000.00, 700000.00, 0.0500, 'Rs 3 Lakhs to Rs 7 Lakhs: 5%'),
(4, 3, 700000.00, 1000000.00, 0.1000, 'Rs 7 Lakhs to Rs 10 Lakhs: 10%'),
(4, 4, 1000000.00, 1200000.00, 0.1500, 'Rs 10 Lakhs to Rs 12 Lakhs: 15%'),
(4, 5, 1200000.00, 1500000.00, 0.2000, 'Rs 12 Lakhs to Rs 15 Lakhs: 20%'),
(4, 6, 1500000.00, NULL, 0.3000, 'Above Rs 15 Lakhs: 30%');

-- FY 2025-26 (AY 2026-27) OLD REGIME (Provisional / Baseline)
INSERT INTO `tax_rule_sets` 
(`id`, `financial_year`, `assessment_year`, `tax_regime`, `taxpayer_category`, `rule_version`, `standard_deduction`, `rebate_87a_limit`, `rebate_87a_max`, `cess_rate`, `effective_date`, `verification_status`, `source_reference`, `notes`) 
VALUES 
(5, '2025-26', '2026-27', 'old', 'individual', '1.0', 50000.00, 500000.00, 12500.00, 0.0400, '2025-04-01', 'verified', 'Finance Act 2024 Continuous Baseline', 'Provisional baseline pending Budget 2025 updates');

INSERT INTO `tax_slabs` (`rule_set_id`, `slab_order`, `lower_bound`, `upper_bound`, `rate`, `conditions`) VALUES
(5, 1, 0.00, 250000.00, 0.0000, 'Up to Rs 2.5 Lakhs: Nil'),
(5, 2, 250000.00, 500000.00, 0.0500, 'Rs 2.5 Lakhs to Rs 5 Lakhs: 5%'),
(5, 3, 500000.00, 1000000.00, 0.2000, 'Rs 5 Lakhs to Rs 10 Lakhs: 20%'),
(5, 4, 1000000.00, NULL, 0.3000, 'Above Rs 10 Lakhs: 30%');

-- FY 2025-26 (AY 2026-27) NEW REGIME
INSERT INTO `tax_rule_sets` 
(`id`, `financial_year`, `assessment_year`, `tax_regime`, `taxpayer_category`, `rule_version`, `standard_deduction`, `rebate_87a_limit`, `rebate_87a_max`, `cess_rate`, `effective_date`, `verification_status`, `source_reference`, `notes`) 
VALUES 
(6, '2025-26', '2026-27', 'new', 'individual', '1.0', 75000.00, 700000.00, 25000.00, 0.0400, '2025-04-01', 'verified', 'Finance (No. 2) Act 2024 Frame', 'Revised new regime parameters carry forward');

INSERT INTO `tax_slabs` (`rule_set_id`, `slab_order`, `lower_bound`, `upper_bound`, `rate`, `conditions`) VALUES
(6, 1, 0.00, 300000.00, 0.0000, 'Up to Rs 3 Lakhs: Nil'),
(6, 2, 300000.00, 700000.00, 0.0500, 'Rs 3 Lakhs to Rs 7 Lakhs: 5%'),
(6, 3, 700000.00, 1000000.00, 0.1000, 'Rs 7 Lakhs to Rs 10 Lakhs: 10%'),
(6, 4, 1000000.00, 1200000.00, 0.1500, 'Rs 10 Lakhs to Rs 12 Lakhs: 15%'),
(6, 5, 1200000.00, 1500000.00, 0.2000, 'Rs 12 Lakhs to Rs 15 Lakhs: 20%'),
(6, 6, 1500000.00, NULL, 0.3000, 'Above Rs 15 Lakhs: 30%');

-- ----------------------------------------------------------------------------
-- 4. Statutory Tax Calendar Reminders
-- ----------------------------------------------------------------------------
INSERT INTO `tax_reminders` (`user_id`, `financial_year`, `title`, `description`, `due_date`, `category`, `status`, `is_statutory`, `source_reference`) VALUES
(NULL, '2024-25', '1st Installment of Advance Tax', 'Pay at least 15% of estimated net tax liability for FY 2024-25 (applicable if net tax exceeds Rs 10,000).', '2024-06-15', 'advance_tax', 'pending', 1, 'Section 211(1) Income Tax Act 1961'),
(NULL, '2024-25', '2nd Installment of Advance Tax', 'Cumulative 45% of estimated net tax liability payable by all non-corporate taxpayers.', '2024-09-15', 'advance_tax', 'pending', 1, 'Section 211(1) Income Tax Act 1961'),
(NULL, '2024-25', '3rd Installment of Advance Tax', 'Cumulative 75% of estimated net tax liability payable.', '2024-12-15', 'advance_tax', 'pending', 1, 'Section 211(1) Income Tax Act 1961'),
(NULL, '2024-25', '4th Installment of Advance Tax', '100% of estimated net tax liability payable before end of financial year.', '2025-03-15', 'advance_tax', 'pending', 1, 'Section 211(1) Income Tax Act 1961'),
(NULL, '2024-25', 'Deadline for Individual ITR Filing (Non-Audit)', 'Statutory due date for filing Income Tax Return for salaried, self-employed individuals not requiring tax audit.', '2025-07-31', 'itr_filing', 'pending', 1, 'Section 139(1) Income Tax Act 1961'),
(NULL, '2024-25', 'Deadline for Tax Audit Cases', 'Due date for taxpayers subject to audit under Section 44AB.', '2025-10-31', 'itr_filing', 'pending', 1, 'Section 139(1) Income Tax Act 1961'),
(NULL, '2024-25', 'Belated / Revised Return Deadline', 'Final opportunity to file belated ITR or revise errors for AY 2025-26 under Sec 139(4)/(5).', '2025-12-31', 'itr_filing', 'pending', 1, 'Section 139(4)/(5) Income Tax Act 1961'),
(NULL, '2024-25', 'Submit Investment Proofs to Employer', 'Provide final documentation (PPF, ELSS, Insurance receipts, Rent receipts) for Form 16 TDS accuracy.', '2025-01-31', 'investment_proof', 'pending', 1, 'CBDT Circular on TDS Salary');

-- ----------------------------------------------------------------------------
-- 5. Seed Users (Admin & Demonstration Profiles)
-- Password for admin: Admin@123
-- Password for demo users: Demo@123
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'System Administrator', 'admin@smarttax.local', '$2y$10$7NDedNIyNfqroEijmrcVWOQRtg8FlEEPPOZN2J4hTABGhWph5gaYe', 'admin', 'active'),
(2, 'Aarav Sharma (Salaried)', 'salaried.demo@smarttax.local', '$2y$10$tD2Kp4LKiGGLdjl042ojTuQtQ7PEmjG3TxettukWZvPiX08XW3/ba', 'user', 'active'),
(3, 'Priya Verma (Freelancer)', 'freelancer.demo@smarttax.local', '$2y$10$tD2Kp4LKiGGLdjl042ojTuQtQ7PEmjG3TxettukWZvPiX08XW3/ba', 'user', 'active'),
(4, 'Rohan Gupta (Student)', 'student.demo@smarttax.local', '$2y$10$tD2Kp4LKiGGLdjl042ojTuQtQ7PEmjG3TxettukWZvPiX08XW3/ba', 'user', 'active');

-- Profiles for Seed Users
INSERT INTO `financial_profiles` (`id`, `user_id`, `financial_year`, `employment_category`, `residential_status`, `age_category`, `state`, `pan_number`, `default_regime`) VALUES
(1, 1, '2024-25', 'salaried', 'resident', 'below_60', 'Maharashtra', 'ABCDE1234F', 'new'),
(2, 2, '2024-25', 'salaried', 'resident', 'below_60', 'Karnataka', 'ABCDE5678G', 'new'),
(3, 3, '2024-25', 'professional', 'resident', 'below_60', 'Delhi', 'FGHIJ9012K', 'new'),
(4, 4, '2024-25', 'student', 'resident', 'below_60', 'Punjab', NULL, 'new');

-- Illustrative Demonstration Income for User 2 (Aarav Sharma - Salaried, Gross 12L)
INSERT INTO `income_records` (`user_id`, `financial_year`, `category`, `sub_category`, `gross_amount`, `adjustments`, `tds_deducted`, `advance_tax_paid`, `description`, `income_date`) VALUES
(2, '2024-25', 'salary', 'Gross Annual Salary', 1200000.00, 2400.00, 65000.00, 0.00, 'Annual CTC from Tech Solutions Pvt Ltd (Professional Tax Rs 2,400)', '2024-04-30'),
(2, '2024-25', 'other_sources', 'Savings Bank Interest', 18000.00, 0.00, 0.00, 0.00, 'Annual interest credited across HDFC & SBI accounts', '2024-09-30');

-- Illustrative Demonstration Deductions for User 2
INSERT INTO `deduction_records` (`user_id`, `financial_year`, `section_code`, `category`, `amount_entered`, `eligible_amount`, `verification_status`, `notes`) VALUES
(2, '2024-25', '80C', 'PPF & Employee PF', 120000.00, 120000.00, 'verified', 'PPF contribution Rs 70,000 + EPF Rs 50,000'),
(2, '2024-25', '80D_SELF', 'Health Insurance Self & Family', 22000.00, 22000.00, 'verified', 'Star Health Comprehensive Family Floater'),
(2, '2024-25', '80CCD_1B', 'NPS Voluntary Contribution', 50000.00, 50000.00, 'verified', 'Tier-1 NPS contribution via eNPS portal');

-- User 2 Custom Tax Reminder
INSERT INTO `tax_reminders` (`user_id`, `financial_year`, `title`, `description`, `due_date`, `category`, `status`, `is_statutory`) VALUES
(2, '2024-25', 'Verify Form 26AS with TDS Certificate', 'Cross check quarterly TDS deducted by employer against Form 26AS and AIS on the e-filing portal.', '2024-11-15', 'tds_review', 'pending', 0);

-- Demonstration calculation snapshot for User 2
INSERT INTO `tax_calculations` 
(`user_id`, `financial_year`, `gross_income`, `exemptions_deductions_old`, `taxable_income_old`, `tax_liability_old`, `exemptions_deductions_new`, `taxable_income_new`, `tax_liability_new`, `recommended_regime`, `potential_savings`, `taxes_paid`, `remaining_payable_or_refund`, `effective_tax_rate`, `rule_version`, `input_snapshot_json`, `result_snapshot_json`)
VALUES
(2, '2024-25', 1218000.00, 242000.00, 976000.00, 112008.00, 75000.00, 1143000.00, 79664.00, 'new', 32344.00, 65000.00, 14664.00, 6.54, '1.0', 
'{"gross_salary":1200000,"other_income":18000,"deductions":{"80C":120000,"80D":22000,"80CCD_1B":50000}}',
'{"recommended":"new","savings":32344,"tax_old":112008,"tax_new":79664,"standard_deduction_new":75000,"cess_new":3064}');
