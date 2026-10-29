-- ============================================================================
-- Smart Tax Planning Assistance
-- Database Schema Definition (MySQL 8.0+ / MariaDB)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `smart_tax_planning` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smart_tax_planning`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `tax_reminders`;
DROP TABLE IF EXISTS `investment_expense_records`;
DROP TABLE IF EXISTS `tax_scenarios`;
DROP TABLE IF EXISTS `tax_calculations`;
DROP TABLE IF EXISTS `tax_provisions`;
DROP TABLE IF EXISTS `tax_slabs`;
DROP TABLE IF EXISTS `tax_rule_sets`;
DROP TABLE IF EXISTS `deduction_records`;
DROP TABLE IF EXISTS `income_records`;
DROP TABLE IF EXISTS `financial_profiles`;
DROP TABLE IF EXISTS `application_settings`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. Users Table
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Financial Profiles Table
-- ----------------------------------------------------------------------------
CREATE TABLE `financial_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `financial_year` VARCHAR(10) NOT NULL DEFAULT '2024-25',
    `employment_category` ENUM('salaried', 'self_employed', 'professional', 'student', 'senior_citizen') NOT NULL DEFAULT 'salaried',
    `residential_status` ENUM('resident', 'nri', 'rnor') NOT NULL DEFAULT 'resident',
    `age_category` ENUM('below_60', 'senior_60_80', 'super_senior_80') NOT NULL DEFAULT 'below_60',
    `state` VARCHAR(100) NOT NULL DEFAULT 'Delhi',
    `pan_number` VARCHAR(20) NULL,
    `default_regime` ENUM('new', 'old') NOT NULL DEFAULT 'new',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_profiles_user_fy` (`user_id`, `financial_year`),
    CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Income Records Table
-- ----------------------------------------------------------------------------
CREATE TABLE `income_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `financial_year` VARCHAR(10) NOT NULL DEFAULT '2024-25',
    `category` ENUM('salary', 'house_property', 'business_profession', 'capital_gains_stcg', 'capital_gains_ltcg', 'other_sources') NOT NULL,
    `sub_category` VARCHAR(100) NULL,
    `gross_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `adjustments` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `tds_deducted` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `advance_tax_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `description` VARCHAR(255) NULL,
    `source_ref` VARCHAR(100) NULL,
    `income_date` DATE NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_income_user_fy` (`user_id`, `financial_year`),
    INDEX `idx_income_category` (`category`),
    CONSTRAINT `fk_income_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Deduction Records Table
-- ----------------------------------------------------------------------------
CREATE TABLE `deduction_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `financial_year` VARCHAR(10) NOT NULL DEFAULT '2024-25',
    `section_code` VARCHAR(50) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `amount_entered` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `eligible_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `verification_status` ENUM('verified', 'pending_verification', 'document_provided', 'rejected') NOT NULL DEFAULT 'pending_verification',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_deductions_user_fy` (`user_id`, `financial_year`),
    INDEX `idx_deductions_section` (`section_code`),
    CONSTRAINT `fk_deduction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Tax Rule Sets Table
-- ----------------------------------------------------------------------------
CREATE TABLE `tax_rule_sets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `financial_year` VARCHAR(10) NOT NULL,
    `assessment_year` VARCHAR(10) NOT NULL,
    `tax_regime` ENUM('old', 'new') NOT NULL,
    `taxpayer_category` VARCHAR(50) NOT NULL DEFAULT 'individual',
    `rule_version` VARCHAR(20) NOT NULL DEFAULT '1.0',
    `standard_deduction` DECIMAL(15,2) NOT NULL DEFAULT 50000.00,
    `rebate_87a_limit` DECIMAL(15,2) NOT NULL DEFAULT 700000.00,
    `rebate_87a_max` DECIMAL(15,2) NOT NULL DEFAULT 25000.00,
    `cess_rate` DECIMAL(5,4) NOT NULL DEFAULT 0.0400,
    `effective_date` DATE NOT NULL,
    `verification_status` ENUM('verified', 'provisional', 'draft') NOT NULL DEFAULT 'verified',
    `source_reference` VARCHAR(255) NOT NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_rule_set` (`financial_year`, `tax_regime`, `taxpayer_category`, `rule_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Tax Slabs Table
-- ----------------------------------------------------------------------------
CREATE TABLE `tax_slabs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `rule_set_id` INT NOT NULL,
    `slab_order` INT NOT NULL DEFAULT 1,
    `lower_bound` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `upper_bound` DECIMAL(15,2) NULL,
    `rate` DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
    `conditions` VARCHAR(255) NULL,
    INDEX `idx_slabs_rule_set` (`rule_set_id`),
    CONSTRAINT `fk_slab_rule_set` FOREIGN KEY (`rule_set_id`) REFERENCES `tax_rule_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. Tax Provisions Table (Chapter VI-A and Exemptions)
-- ----------------------------------------------------------------------------
CREATE TABLE `tax_provisions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `provision_code` VARCHAR(50) NOT NULL UNIQUE,
    `provision_name` VARCHAR(150) NOT NULL,
    `applicable_regime` ENUM('old_only', 'new_only', 'both') NOT NULL DEFAULT 'old_only',
    `maximum_limit` DECIMAL(15,2) NULL,
    `eligibility_conditions` TEXT NOT NULL,
    `documentation_required` TEXT NULL,
    `source_reference` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. Tax Calculations Table
-- ----------------------------------------------------------------------------
CREATE TABLE `tax_calculations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `financial_year` VARCHAR(10) NOT NULL,
    `gross_income` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `exemptions_deductions_old` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `taxable_income_old` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `tax_liability_old` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `exemptions_deductions_new` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `taxable_income_new` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `tax_liability_new` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `recommended_regime` ENUM('old', 'new', 'equal') NOT NULL DEFAULT 'new',
    `potential_savings` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `taxes_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `remaining_payable_or_refund` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `effective_tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `rule_version` VARCHAR(20) NOT NULL DEFAULT '1.0',
    `input_snapshot_json` LONGTEXT NOT NULL,
    `result_snapshot_json` LONGTEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_calc_user_fy` (`user_id`, `financial_year`),
    CONSTRAINT `fk_calc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. Tax Scenarios Table (What-If Simulation Sandbox)
-- ----------------------------------------------------------------------------
CREATE TABLE `tax_scenarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `financial_year` VARCHAR(10) NOT NULL,
    `scenario_name` VARCHAR(150) NOT NULL,
    `scenario_inputs_json` LONGTEXT NOT NULL,
    `scenario_results_json` LONGTEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_scenarios_user` (`user_id`),
    CONSTRAINT `fk_scenario_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. Investment and Expense Records Table
-- ----------------------------------------------------------------------------
CREATE TABLE `investment_expense_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `financial_year` VARCHAR(10) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `record_date` DATE NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `verification_status` ENUM('verified', 'pending', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_investments_user` (`user_id`, `financial_year`),
    CONSTRAINT `fk_investments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. Tax Reminders Table
-- ----------------------------------------------------------------------------
CREATE TABLE `tax_reminders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `financial_year` VARCHAR(10) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `due_date` DATE NOT NULL,
    `category` ENUM('advance_tax', 'itr_filing', 'investment_proof', 'tds_review', 'custom') NOT NULL DEFAULT 'custom',
    `status` ENUM('pending', 'completed', 'dismissed') NOT NULL DEFAULT 'pending',
    `is_statutory` TINYINT(1) NOT NULL DEFAULT 0,
    `source_reference` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reminders_user_date` (`user_id`, `due_date`),
    CONSTRAINT `fk_reminders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. Audit Logs Table
-- ----------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `affected_record_type` VARCHAR(100) NOT NULL,
    `affected_record_id` INT NULL,
    `metadata_json` LONGTEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 13. Application Settings Table
-- ----------------------------------------------------------------------------
CREATE TABLE `application_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
