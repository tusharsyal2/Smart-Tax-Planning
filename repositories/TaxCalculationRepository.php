<?php
/**
 * Tax Calculation Repository
 * Preserves historical calculation snapshots and What-If simulation scenarios.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/database.php';

class TaxCalculationRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDb();
    }

    public function saveCalculation(int $userId, array $calcData): int {
        $stmt = $this->db->prepare("
            INSERT INTO `tax_calculations`
            (`user_id`, `financial_year`, `gross_income`, 
             `exemptions_deductions_old`, `taxable_income_old`, `tax_liability_old`, 
             `exemptions_deductions_new`, `taxable_income_new`, `tax_liability_new`, 
             `recommended_regime`, `potential_savings`, `taxes_paid`, 
             `remaining_payable_or_refund`, `effective_tax_rate`, `rule_version`, 
             `input_snapshot_json`, `result_snapshot_json`)
            VALUES
            (:uid, :fy, :gross, 
             :ded_old, :taxable_old, :tax_old, 
             :ded_new, :taxable_new, :tax_new, 
             :rec, :savings, :paid, 
             :bal, :eff, :ver, 
             :in_json, :res_json)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':fy' => $calcData['financial_year'] ?? '2024-25',
            ':gross' => (float)$calcData['gross_income'],
            ':ded_old' => (float)($calcData['exemptions_deductions_old'] ?? 0.0),
            ':taxable_old' => (float)($calcData['taxable_income_old'] ?? 0.0),
            ':tax_old' => (float)($calcData['tax_liability_old'] ?? 0.0),
            ':ded_new' => (float)($calcData['exemptions_deductions_new'] ?? 0.0),
            ':taxable_new' => (float)($calcData['taxable_income_new'] ?? 0.0),
            ':tax_new' => (float)($calcData['tax_liability_new'] ?? 0.0),
            ':rec' => $calcData['recommended_regime'] ?? 'new',
            ':savings' => (float)($calcData['potential_savings'] ?? 0.0),
            ':paid' => (float)($calcData['taxes_paid'] ?? 0.0),
            ':bal' => (float)($calcData['remaining_payable_or_refund'] ?? 0.0),
            ':eff' => (float)($calcData['effective_tax_rate'] ?? 0.0),
            ':ver' => $calcData['rule_version'] ?? '1.0',
            ':in_json' => is_string($calcData['input_snapshot_json']) ? $calcData['input_snapshot_json'] : json_encode($calcData['input_snapshot_json']),
            ':res_json' => is_string($calcData['result_snapshot_json']) ? $calcData['result_snapshot_json'] : json_encode($calcData['result_snapshot_json'])
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getHistory(int $userId, ?string $financialYear = null, int $limit = 50): array {
        $sql = "SELECT * FROM `tax_calculations` WHERE `user_id` = :uid";
        $params = [':uid' => $userId];
        if ($financialYear) {
            $sql .= " AND `financial_year` = :fy";
            $params[':fy'] = $financialYear;
        }
        $sql .= " ORDER BY `id` DESC LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id, int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM `tax_calculations`
            WHERE `id` = :id AND `user_id` = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getLatestCalculation(int $userId, ?string $financialYear = null): ?array {
        $sql = "SELECT * FROM `tax_calculations` WHERE `user_id` = :uid";
        $params = [':uid' => $userId];
        if ($financialYear) {
            $sql .= " AND `financial_year` = :fy";
            $params[':fy'] = $financialYear;
        }
        $sql .= " ORDER BY `id` DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function saveScenario(int $userId, string $fy, string $name, array $inputs, array $results): int {
        $stmt = $this->db->prepare("
            INSERT INTO `tax_scenarios`
            (`user_id`, `financial_year`, `scenario_name`, `scenario_inputs_json`, `scenario_results_json`)
            VALUES
            (:uid, :fy, :name, :in_json, :res_json)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':fy' => $fy,
            ':name' => $name,
            ':in_json' => json_encode($inputs),
            ':res_json' => json_encode($results)
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getScenarios(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM `tax_scenarios` WHERE `user_id` = :uid ORDER BY `id` DESC");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }
}
