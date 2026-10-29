<?php
/**
 * Income Repository
 * Manages income records across all 5 Indian tax heads with strict user data isolation.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/database.php';

class IncomeRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDb();
    }

    public function getByUserAndFy(int $userId, string $financialYear): array {
        $stmt = $this->db->prepare("
            SELECT * FROM `income_records`
            WHERE `user_id` = :uid AND `financial_year` = :fy
            ORDER BY `category` ASC, `id` DESC
        ");
        $stmt->execute([':uid' => $userId, ':fy' => $financialYear]);
        return $stmt->fetchAll();
    }

    public function getById(int $id, int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM `income_records`
            WHERE `id` = :id AND `user_id` = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getTotals(int $userId, string $financialYear): array {
        $stmt = $this->db->prepare("
            SELECT `category`, 
                   SUM(`gross_amount`) as total_gross,
                   SUM(`adjustments`) as total_adjustments,
                   SUM(`tds_deducted`) as total_tds,
                   SUM(`advance_tax_paid`) as total_advance_tax
            FROM `income_records`
            WHERE `user_id` = :uid AND `financial_year` = :fy
            GROUP BY `category`
        ");
        $stmt->execute([':uid' => $userId, ':fy' => $financialYear]);
        $rows = $stmt->fetchAll();

        $totals = [
            'salary' => 0.0,
            'house_property' => 0.0,
            'business_profession' => 0.0,
            'capital_gains_stcg' => 0.0,
            'capital_gains_ltcg' => 0.0,
            'other_sources' => 0.0,
            'total_gross' => 0.0,
            'total_tds' => 0.0,
            'total_advance_tax' => 0.0
        ];

        foreach ($rows as $r) {
            $cat = $r['category'];
            $net = (float)$r['total_gross'] - (float)$r['total_adjustments'];
            if (isset($totals[$cat])) {
                $totals[$cat] = $net;
            }
            $totals['total_gross'] += (float)$r['total_gross'];
            $totals['total_tds'] += (float)$r['total_tds'];
            $totals['total_advance_tax'] += (float)$r['total_advance_tax'];
        }

        return $totals;
    }

    public function create(int $userId, array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO `income_records`
            (`user_id`, `financial_year`, `category`, `sub_category`, `gross_amount`, `adjustments`, 
             `tds_deducted`, `advance_tax_paid`, `description`, `source_ref`, `income_date`)
            VALUES
            (:uid, :fy, :cat, :sub, :gross, :adj, :tds, :adv, :desc, :source, :date)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':fy' => $data['financial_year'] ?? '2024-25',
            ':cat' => $data['category'],
            ':sub' => $data['sub_category'] ?? null,
            ':gross' => max(0.0, (float)$data['gross_amount']),
            ':adj' => max(0.0, (float)($data['adjustments'] ?? 0.0)),
            ':tds' => max(0.0, (float)($data['tds_deducted'] ?? 0.0)),
            ':adv' => max(0.0, (float)($data['advance_tax_paid'] ?? 0.0)),
            ':desc' => $data['description'] ?? '',
            ':source' => $data['source_ref'] ?? null,
            ':date' => !empty($data['income_date']) ? $data['income_date'] : date('Y-m-d')
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE `income_records`
            SET `financial_year` = :fy,
                `category` = :cat,
                `sub_category` = :sub,
                `gross_amount` = :gross,
                `adjustments` = :adj,
                `tds_deducted` = :tds,
                `advance_tax_paid` = :adv,
                `description` = :desc,
                `source_ref` = :source,
                `income_date` = :date
            WHERE `id` = :id AND `user_id` = :uid
        ");
        return $stmt->execute([
            ':id' => $id,
            ':uid' => $userId,
            ':fy' => $data['financial_year'],
            ':cat' => $data['category'],
            ':sub' => $data['sub_category'] ?? null,
            ':gross' => max(0.0, (float)$data['gross_amount']),
            ':adj' => max(0.0, (float)($data['adjustments'] ?? 0.0)),
            ':tds' => max(0.0, (float)($data['tds_deducted'] ?? 0.0)),
            ':adv' => max(0.0, (float)($data['advance_tax_paid'] ?? 0.0)),
            ':desc' => $data['description'] ?? '',
            ':source' => $data['source_ref'] ?? null,
            ':date' => !empty($data['income_date']) ? $data['income_date'] : date('Y-m-d')
        ]);
    }

    public function delete(int $id, int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM `income_records` WHERE `id` = :id AND `user_id` = :uid");
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
}
