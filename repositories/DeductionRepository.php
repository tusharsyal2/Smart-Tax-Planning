<?php
/**
 * Deduction Repository
 * Manages tax-saving investment and deduction records with verification statuses.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/database.php';

class DeductionRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDb();
    }

    public function getByUserAndFy(int $userId, string $financialYear): array {
        $stmt = $this->db->prepare("
            SELECT * FROM `deduction_records`
            WHERE `user_id` = :uid AND `financial_year` = :fy
            ORDER BY `section_code` ASC, `id` DESC
        ");
        $stmt->execute([':uid' => $userId, ':fy' => $financialYear]);
        return $stmt->fetchAll();
    }

    public function getById(int $id, int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM `deduction_records`
            WHERE `id` = :id AND `user_id` = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $userId, array $data): int {
        $entered = max(0.0, (float)$data['amount_entered']);
        $section = strtoupper(trim($data['section_code']));

        // Compute preliminary eligible amount based on section
        $eligible = $entered;
        if ($section === '80C') {
            $eligible = min($entered, 150000.0);
        } elseif ($section === '80CCD_1B' || $section === '80CCD(1B)') {
            $eligible = min($entered, 50000.0);
        } elseif ($section === '80TTA') {
            $eligible = min($entered, 10000.0);
        } elseif ($section === '80TTB') {
            $eligible = min($entered, 50000.0);
        } elseif ($section === '24B' || $section === '24(B)') {
            $eligible = min($entered, 200000.0);
        }

        $stmt = $this->db->prepare("
            INSERT INTO `deduction_records`
            (`user_id`, `financial_year`, `section_code`, `category`, `amount_entered`, `eligible_amount`, `verification_status`, `notes`)
            VALUES
            (:uid, :fy, :sec, :cat, :amt, :elig, :status, :notes)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':fy' => $data['financial_year'] ?? '2024-25',
            ':sec' => $section,
            ':cat' => $data['category'] ?? $section,
            ':amt' => $entered,
            ':elig' => $eligible,
            ':status' => $data['verification_status'] ?? 'pending_verification',
            ':notes' => $data['notes'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $data): bool {
        $entered = max(0.0, (float)$data['amount_entered']);
        $section = strtoupper(trim($data['section_code']));

        $eligible = $entered;
        if ($section === '80C') {
            $eligible = min($entered, 150000.0);
        } elseif ($section === '80CCD_1B' || $section === '80CCD(1B)') {
            $eligible = min($entered, 50000.0);
        } elseif ($section === '80TTA') {
            $eligible = min($entered, 10000.0);
        } elseif ($section === '80TTB') {
            $eligible = min($entered, 50000.0);
        } elseif ($section === '24B' || $section === '24(B)') {
            $eligible = min($entered, 200000.0);
        }

        $stmt = $this->db->prepare("
            UPDATE `deduction_records`
            SET `financial_year` = :fy,
                `section_code` = :sec,
                `category` = :cat,
                `amount_entered` = :amt,
                `eligible_amount` = :elig,
                `verification_status` = :status,
                `notes` = :notes
            WHERE `id` = :id AND `user_id` = :uid
        ");
        return $stmt->execute([
            ':id' => $id,
            ':uid' => $userId,
            ':fy' => $data['financial_year'],
            ':sec' => $section,
            ':cat' => $data['category'] ?? $section,
            ':amt' => $entered,
            ':elig' => $eligible,
            ':status' => $data['verification_status'] ?? 'pending_verification',
            ':notes' => $data['notes'] ?? null
        ]);
    }

    public function delete(int $id, int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM `deduction_records` WHERE `id` = :id AND `user_id` = :uid");
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
}
