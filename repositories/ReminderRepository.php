<?php
/**
 * Tax Reminder Repository
 * Handles statutory tax calendar dates and user-defined planning reminders.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/database.php';

class ReminderRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDb();
    }

    /**
     * Get combined statutory and user-specific reminders for a financial year
     */
    public function getRemindersForUser(int $userId, string $financialYear): array {
        $stmt = $this->db->prepare("
            SELECT * FROM `tax_reminders`
            WHERE `financial_year` = :fy AND (`user_id` = :uid OR `user_id` IS NULL)
            ORDER BY `due_date` ASC
        ");
        $stmt->execute([':fy' => $financialYear, ':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function createCustomReminder(int $userId, array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO `tax_reminders`
            (`user_id`, `financial_year`, `title`, `description`, `due_date`, `category`, `status`, `is_statutory`)
            VALUES
            (:uid, :fy, :title, :desc, :due, :cat, 'pending', 0)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':fy' => $data['financial_year'] ?? '2024-25',
            ':title' => trim($data['title']),
            ':desc' => $data['description'] ?? null,
            ':due' => $data['due_date'],
            ':cat' => $data['category'] ?? 'custom'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $id, int $userId, string $status): bool {
        // Can only update status for reminders owned by user or toggle completion
        $stmt = $this->db->prepare("
            UPDATE `tax_reminders`
            SET `status` = :st
            WHERE `id` = :id AND (`user_id` = :uid OR `user_id` IS NULL)
        ");
        return $stmt->execute([':id' => $id, ':uid' => $userId, ':st' => $status]);
    }

    public function delete(int $id, int $userId): bool {
        // Only non-statutory reminders belonging to user can be deleted
        $stmt = $this->db->prepare("
            DELETE FROM `tax_reminders`
            WHERE `id` = :id AND `user_id` = :uid AND `is_statutory` = 0
        ");
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }
}
