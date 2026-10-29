<?php
/**
 * Tax Rule Service
 * Loads, manages, and validates financial-year-specific tax rules, slabs, and provisions.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/database.php';

class TaxRuleService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDb();
    }

    /**
     * Get active tax rule set for a financial year, regime, and category
     */
    public function getRuleSet(string $financialYear, string $regime, string $category = 'individual'): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM `tax_rule_sets`
            WHERE `financial_year` = :fy
              AND `tax_regime` = :regime
              AND `taxpayer_category` = :cat
            ORDER BY `rule_version` DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':fy' => $financialYear,
            ':regime' => strtolower($regime),
            ':cat' => strtolower($category)
        ]);
        $ruleSet = $stmt->fetch();

        if (!$ruleSet) {
            return null;
        }

        // Fetch progressive slabs for this rule set
        $slabStmt = $this->db->prepare("
            SELECT * FROM `tax_slabs`
            WHERE `rule_set_id` = :rule_id
            ORDER BY `slab_order` ASC, `lower_bound` ASC
        ");
        $slabStmt->execute([':rule_id' => $ruleSet['id']]);
        $ruleSet['slabs'] = $slabStmt->fetchAll();

        return $ruleSet;
    }

    /**
     * Get all supported financial years in database
     */
    public function getAvailableFinancialYears(): array {
        $stmt = $this->db->query("
            SELECT DISTINCT `financial_year`, `assessment_year`, `verification_status`
            FROM `tax_rule_sets`
            ORDER BY `financial_year` DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get all statutory tax provisions (Chapter VI-A, Sec 24, Standard Deduction)
     */
    public function getAllProvisions(bool $activeOnly = true): array {
        $sql = "SELECT * FROM `tax_provisions`";
        if ($activeOnly) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `provision_code` ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get specific provision by code
     */
    public function getProvisionByCode(string $code): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `tax_provisions` WHERE `provision_code` = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Admin: Create or update a tax rule set
     */
    public function saveRuleSet(array $data, ?int $id = null): int {
        if ($id) {
            $stmt = $this->db->prepare("
                UPDATE `tax_rule_sets`
                SET `financial_year` = :fy,
                    `assessment_year` = :ay,
                    `tax_regime` = :regime,
                    `standard_deduction` = :std_ded,
                    `rebate_87a_limit` = :rebate_limit,
                    `rebate_87a_max` = :rebate_max,
                    `cess_rate` = :cess,
                    `verification_status` = :status,
                    `source_reference` = :source,
                    `notes` = :notes,
                    `rule_version` = :version
                WHERE `id` = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':fy' => $data['financial_year'],
                ':ay' => $data['assessment_year'],
                ':regime' => $data['tax_regime'],
                ':std_ded' => (float)$data['standard_deduction'],
                ':rebate_limit' => (float)$data['rebate_87a_limit'],
                ':rebate_max' => (float)$data['rebate_87a_max'],
                ':cess' => (float)$data['cess_rate'],
                ':status' => $data['verification_status'] ?? 'verified',
                ':source' => $data['source_reference'] ?? '',
                ':notes' => $data['notes'] ?? '',
                ':version' => $data['rule_version'] ?? '1.0'
            ]);
            return $id;
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO `tax_rule_sets`
                (`financial_year`, `assessment_year`, `tax_regime`, `taxpayer_category`, `rule_version`, 
                 `standard_deduction`, `rebate_87a_limit`, `rebate_87a_max`, `cess_rate`, `effective_date`, 
                 `verification_status`, `source_reference`, `notes`)
                VALUES
                (:fy, :ay, :regime, 'individual', :version, 
                 :std_ded, :rebate_limit, :rebate_max, :cess, :eff_date, 
                 :status, :source, :notes)
            ");
            $stmt->execute([
                ':fy' => $data['financial_year'],
                ':ay' => $data['assessment_year'],
                ':regime' => $data['tax_regime'],
                ':version' => $data['rule_version'] ?? '1.0',
                ':std_ded' => (float)$data['standard_deduction'],
                ':rebate_limit' => (float)$data['rebate_87a_limit'],
                ':rebate_max' => (float)$data['rebate_87a_max'],
                ':cess' => (float)($data['cess_rate'] ?? 0.04),
                ':eff_date' => $data['effective_date'] ?? date('Y-04-01'),
                ':status' => $data['verification_status'] ?? 'verified',
                ':source' => $data['source_reference'] ?? 'Official Finance Act',
                ':notes' => $data['notes'] ?? ''
            ]);
            return (int)$this->db->lastInsertId();
        }
    }
}
