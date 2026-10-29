<?php
/**
 * Report Generation Service
 * Aggregates complete tax planning reports and produces secure CSV exports.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/TaxComparisonService.php';
require_once __DIR__ . '/TaxRecommendationService.php';
require_once __DIR__ . '/../repositories/IncomeRepository.php';
require_once __DIR__ . '/../repositories/DeductionRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class ReportService {
    private TaxComparisonService $comparisonService;
    private TaxRecommendationService $recommendationService;
    private IncomeRepository $incomeRepo;
    private DeductionRepository $deductionRepo;
    private UserRepository $userRepo;

    public function __construct(
        ?TaxComparisonService $comparisonService = null,
        ?TaxRecommendationService $recommendationService = null,
        ?IncomeRepository $incomeRepo = null,
        ?DeductionRepository $deductionRepo = null,
        ?UserRepository $userRepo = null
    ) {
        $this->comparisonService = $comparisonService ?? new TaxComparisonService();
        $this->recommendationService = $recommendationService ?? new TaxRecommendationService();
        $this->incomeRepo = $incomeRepo ?? new IncomeRepository();
        $this->deductionRepo = $deductionRepo ?? new DeductionRepository();
        $this->userRepo = $userRepo ?? new UserRepository();
    }

    /**
     * Generate complete report data structure for a user and financial year
     */
    public function generateReport(int $userId, string $financialYear): array {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw new RuntimeException("User not found.");
        }

        $incomeRecords = $this->incomeRepo->getByUserAndFy($userId, $financialYear);
        $incomeTotals = $this->incomeRepo->getTotals($userId, $financialYear);
        $deductions = $this->deductionRepo->getByUserAndFy($userId, $financialYear);

        // Prepare calculation parameters
        $calcParams = [
            'financial_year' => $financialYear,
            'age_category' => $user['age_category'] ?? 'below_60',
            'salary_income' => $incomeTotals['salary'] ?? 0.0,
            'house_property_income' => $incomeTotals['house_property'] ?? 0.0,
            'business_income' => $incomeTotals['business_profession'] ?? 0.0,
            'stcg_income' => $incomeTotals['capital_gains_stcg'] ?? 0.0,
            'ltcg_income' => $incomeTotals['capital_gains_ltcg'] ?? 0.0,
            'other_income' => $incomeTotals['other_sources'] ?? 0.0,
            'deductions' => $deductions,
            'tds_paid' => $incomeTotals['total_tds'] ?? 0.0,
            'advance_tax_paid' => $incomeTotals['total_advance_tax'] ?? 0.0
        ];

        // Comparison & Recommendations
        $comparison = $this->comparisonService->compare($calcParams);
        $recommendations = $this->recommendationService->generateRecommendations($calcParams);

        return [
            'user' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'employment_category' => $user['employment_category'] ?? 'salaried',
                'age_category' => $user['age_category'] ?? 'below_60',
                'state' => $user['state'] ?? 'India',
                'pan' => !empty($user['pan_number']) ? substr($user['pan_number'], 0, 2) . 'XXXXXX' . substr($user['pan_number'], -2) : 'Not Provided'
            ],
            'financial_year' => $financialYear,
            'generated_at' => date('d M Y, h:i A'),
            'income_totals' => $incomeTotals,
            'income_records' => $incomeRecords,
            'deduction_records' => $deductions,
            'comparison' => $comparison,
            'recommendations' => $recommendations,
            'disclaimer' => 'This report is generated automatically by Smart Tax Planning Assistance for personal financial planning and educational analysis only. It does not constitute a certified tax return or legal tax filing under Section 139 of the Income Tax Act 1961.'
        ];
    }

    /**
     * Export Income records to CSV
     */
    public function exportIncomeCsv(int $userId, string $financialYear): string {
        $records = $this->incomeRepo->getByUserAndFy($userId, $financialYear);
        $output = fopen('php://temp', 'r+');

        // CSV Header
        fputcsv($output, ['ID', 'Category', 'Sub Category', 'Gross Amount (INR)', 'Adjustments (INR)', 'Net Amount (INR)', 'TDS Deducted (INR)', 'Advance Tax (INR)', 'Date', 'Description']);

        foreach ($records as $r) {
            $net = (float)$r['gross_amount'] - (float)$r['adjustments'];
            fputcsv($output, [
                $r['id'],
                sanitizeForCsv($r['category']),
                sanitizeForCsv($r['sub_category'] ?? ''),
                sanitizeForCsv($r['gross_amount']),
                sanitizeForCsv($r['adjustments']),
                sanitizeForCsv($net),
                sanitizeForCsv($r['tds_deducted']),
                sanitizeForCsv($r['advance_tax_paid']),
                sanitizeForCsv($r['income_date']),
                sanitizeForCsv($r['description'])
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    /**
     * Export Deduction records to CSV
     */
    public function exportDeductionsCsv(int $userId, string $financialYear): string {
        $records = $this->deductionRepo->getByUserAndFy($userId, $financialYear);
        $output = fopen('php://temp', 'r+');

        fputcsv($output, ['ID', 'Section Code', 'Category Description', 'Amount Entered (INR)', 'Potentially Eligible Amount (INR)', 'Verification Status', 'Notes']);

        foreach ($records as $r) {
            fputcsv($output, [
                $r['id'],
                sanitizeForCsv($r['section_code']),
                sanitizeForCsv($r['category']),
                sanitizeForCsv($r['amount_entered']),
                sanitizeForCsv($r['eligible_amount']),
                sanitizeForCsv($r['verification_status']),
                sanitizeForCsv($r['notes'])
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }
}
