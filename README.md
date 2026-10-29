# Smart Tax Planning Assistance

> **Personal Income Tax Estimation, Regime Comparison & Financial Planning Web Platform for Indian Taxpayers**

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-8.0%2B%20%7C%20MariaDB-orange.svg)](https://www.mysql.com/)
[![Jurisdiction](https://img.shields.io/badge/Jurisdiction-India%20(Income%20Tax%20Act%201961)-green.svg)](https://incometax.gov.in)
[![Finance Act](https://img.shields.io/badge/Tax%20Rules-Finance%20(No.%202)%20Act%202024-teal.svg)](https://incometaxindia.gov.in)
[![Test Suite](https://img.shields.io/badge/Tests-25%2F25%20Passing-brightgreen.svg)](tests/run_tests.php)

---

## 1. Project Overview

**Smart Tax Planning Assistance** is a secure, production-grade web application developed in Core PHP 8.2, MySQL, Vanilla JavaScript, Bootstrap 5, and Chart.js.

The platform helps students, salaried employees, freelancers, and self-employed professionals understand their income tax liabilities under Indian tax law, compare the **Old Tax Regime** and the **New Tax Regime (Section 115BAC)** side-by-side, uncover personalized tax-saving avenues (Chapter VI-A, Section 80C, 80D, NPS), and run real-time "What-If" career simulations.

> **Legal Disclaimer:** Smart Tax Planning Assistance is an educational and financial-planning application. Calculations represent estimates based on statutory Indian tax provisions and do not replace a licensed Chartered Accountant, nor does the platform directly file tax returns on the official TRP portal.

---

## 2. Key Features

- **Side-by-Side Regime Comparison:** Evaluates both Old and New regimes using identical financial data and identifies the exact **Breakeven Deduction Point** needed for the Old Regime to match the New Regime.
- **Budget 2024 / FY 2024-25 Ready:** Implements the revised standard deduction of **₹75,000** for the New Regime, updated progressive slabs (up to ₹3L Nil, 3-7L 5%, 7-10L 10%, 10-12L 15%, 12-15L 20%, above 15L 30%), and the Section 87A rebate for taxable income up to ₹7,00,000.
- **5 Heads of Income Management:** Full tracking of Salary, House Property (with standard 30% statutory deduction and municipal taxes), Business/Profession, Capital Gains (STCG & LTCG), and Other Sources (Savings/FD Interest).
- **Chapter VI-A Deduction Tracker:** Automatic statutory ceiling enforcement for Section 80C (₹1.5 Lakhs), Section 80CCD(1B) NPS (₹50,000), Section 80D Health Insurance (₹25,000/₹50,000), Section 80CCD(2) Employer NPS (allowed under both regimes), Section 80E, 80G, and Section 24(b) Home Loan Interest.
- **Personalized Recommendations Engine:** Computes estimated tax savings using the taxpayer's **true marginal tax rate** (never misleading 1:1 assumptions), presenting instrument lock-in periods and risk levels.
- **Interactive "What-If" Simulator:** Sandbox allowing users to model salary increments, new insurance policies, or retirement investments with real-time delta tracking.
- **Statutory Tax Calendar:** Pre-configured with official CBDT deadlines (June 15, Sept 15, Dec 15, March 15 Advance Tax installments; July 31 ITR deadline; investment proof dates) plus personal task management.
- **Audited Reports & Secure CSV Exports:** Clean print-ready reports with formula injection protection (`sanitizeForCsv` neutralizes `=`, `+`, `-`, `@` triggers).
- **Full Administrator Control Panel:** Tax rule set management, progressive slab editor, user account toggling, and an immutable audit trail.

---

## 3. Technology Stack

- **Backend:** Core PHP 8.2 (Object-Oriented Services, PDO Prepared Statements, Secure PHP Sessions with `session_regenerate_id(true)`).
- **Database:** MySQL 8.0+ / MariaDB 10.4+ (`smart_tax_planning`).
- **Frontend:** HTML5, CSS3 Glassmorphism theme, Vanilla JavaScript, Bootstrap 5.3, Bootstrap Icons, Chart.js 4.4.
- **Server:** Apache (XAMPP) or PHP Built-in Server (`php -S`).

---

## 4. Pre-Configured Demonstration Accounts

The database comes pre-seeded with verified test accounts:

| Role | Email | Password | Description |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@smarttax.local` | `Admin@123` | Full access to tax rule sets, user accounts, and audit logs |
| **Salaried Demo** | `salaried.demo@smarttax.local` | `Demo@123` | Gross CTC ₹12 Lakhs with 80C, 80D, and NPS records pre-loaded |
| **Freelancer Demo** | `freelancer.demo@smarttax.local` | `Demo@123` | Professional consulting income profile |
| **Student Demo** | `student.demo@smarttax.local` | `Demo@123` | Internship income under ₹7 Lakhs (demonstrates ₹0 tax with 87A rebate) |

---

## 5. Local Setup Instructions (XAMPP on Windows)

### Prerequisites
- Install [XAMPP](https://www.apachefriends.org/) (with Apache and MySQL / MariaDB).
- Ensure PHP 8.2+ is accessible.

### Step 1: Place the Project in XAMPP
The project is configured at:
```
C:\xampp\htdocs\smart-tax-planning
```
*(A directory junction has already been linked from `C:\Users\Girish Jindal\.gemini\antigravity-ide\scratch\smart-tax-planning`).*

### Step 2: Configure Environment (`.env`)
Open `.env` in the project root and configure your MySQL credentials:
```env
APP_NAME="Smart Tax Planning Assistance"
APP_ENV="local"
APP_DEBUG=true
APP_URL="http://localhost/smart-tax-planning"

DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="smart_tax_planning"
DB_USER="root"
DB_PASS="YOUR_MYSQL_PASSWORD"  # Default XAMPP is empty: DB_PASS=""
```

### Step 3: Run Database Migrations & Seeds
From a command prompt or PowerShell, run:
```bash
php database/migrate.php
```
*This automatically creates the `smart_tax_planning` database, all 13 tables, verified tax rule sets, and demo accounts.*

Alternatively, import `database/schema.sql` followed by `database/seed.sql` inside **phpMyAdmin** (`http://localhost/phpmyadmin`).

### Step 4: Open in Your Browser
- Via XAMPP Apache: [http://localhost/smart-tax-planning/](http://localhost/smart-tax-planning/)
- Or via PHP Built-in CLI Server:
  ```bash
  php -S 127.0.0.1:8000
  ```
  Open: [http://127.0.0.1:8000/](http://127.0.0.1:8000/)

---

## 6. Supported Tax Provisions & Official References

| Provision | Description | Statutory Ceiling | Applicable Regime | Official Source |
| :--- | :--- | :--- | :--- | :--- |
| **Section 16(ia)** | Standard Deduction on Salary | ₹75,000 (New) / ₹50,000 (Old) | Both Regimes | Finance (No. 2) Act 2024 |
| **Section 115BAC** | Revised Concessional Slab Rates | Progressive Slabs (0-30%) | New Tax Regime | Finance (No. 2) Act 2024 |
| **Section 87A** | Tax Rebate on Net Taxable Income | ₹25,000 (New, up to ₹7L) / ₹12,500 (Old, up to ₹5L) | Both Regimes | Finance Act 2023 / 2024 |
| **Section 80C** | PPF, EPF, ELSS, Life Insurance, Tuition | ₹1,50,000 combined | Old Regime only | Income Tax Act 1961 |
| **Section 80CCD(1B)** | Voluntary Contribution to NPS (Tier 1) | ₹50,000 exclusive | Old Regime only | Income Tax Act 1961 |
| **Section 80CCD(2)** | Employer NPS Contribution | Up to 10% / 14% of Basic + DA | **Both Regimes** | Section 115BAC(2) proviso |
| **Section 80D** | Health Insurance (Self & Parents) | ₹25,000 (₹50k seniors) each | Old Regime only | Income Tax Act 1961 |
| **Section 24(b)** | Interest on Housing Loan (Self-Occupied) | ₹2,00,000 | Old Regime only | Income Tax Act 1961 |
| **Section 80E** | Higher Education Loan Interest | Entire interest (8 years) | Old Regime only | Income Tax Act 1961 |
| **Section 80TTA/80TTB**| Savings Account / Deposit Interest | ₹10,000 (TTA) / ₹50,000 (TTB) | Old Regime only | Income Tax Act 1961 |
| **Section 288A/288B** | Statutory Rounding to Nearest ₹10 | Exact statutory rounding | Both Regimes | Income Tax Act 1961 |

---

## 7. Automated Testing Suite

The application includes an automated test runner executing 25 unit and business-logic assertions:

```bash
php tests/run_tests.php
```

### Verified Test Cases:
1. Zero-income boundary case (tax liability is exactly ₹0).
2. FY 2024-25 New Regime Section 87A rebate (zero tax up to ₹7.75 Lakhs salary with ₹75k standard deduction).
3. Old Regime Section 87A rebate (zero tax up to ₹5.5 Lakhs salary with ₹50k standard deduction).
4. Standard deduction versioning (₹75k for FY 24-25 vs ₹50k for FY 23-24).
5. Section 80C ₹1.5 Lakh statutory ceiling enforcement.
6. Section 80CCD(1B) NPS ₹50,000 ceiling enforcement.
7. Section 80CCD(2) employer NPS allowance in both Old and New regimes.
8. Breakeven deduction binary search calculation.
9. What-If simulation delta tracking.
10. Health and Education Cess exact 4% statutory computation.
11. Formula injection sanitization in CSV exports.
12. Password hashing with Bcrypt and verification.

---

## 8. College Project & Viva Guide

When presenting this project for evaluation or viva examinations:
1. **Explain the Architecture:** Clarify the separation between presentation (`dashboard.php`, `tax_calculator.php`), services (`TaxCalculator.php`, `TaxComparisonService.php`, `DeductionEligibilityService.php`), and repositories (`UserRepository.php`, `IncomeRepository.php`).
2. **Demonstrate Breakeven Deductions:** Explain that taxpayers often struggle to decide between Old and New regimes. Show the calculator determining the exact rupee deduction amount needed under the Old Regime to match the New Regime.
3. **Highlight Security Controls:** Show that inputs are validated server-side, SQL queries use PDO prepared statements with parameter binding, session IDs regenerate upon login to prevent session fixation, and CSV exports neutralize spreadsheet formula injection.
4. **Demonstrate Rate-Limiting & CSRF:** Show that all state-changing forms pass CSRF verification tokens and failed login attempts trigger rate-limiting cooldowns.

---

## 9. License & Author

Developed for personal income tax planning and academic demonstration.
Complies with official CBDT rules and Finance (No. 2) Act 2024 provisions.
