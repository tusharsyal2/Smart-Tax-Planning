<?php
/**
 * Audit Repository
 * Tracks critical administrative actions, rule modifications, and security activities.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/database.php';

class AuditRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDb();
    }

    public function log(?int $userId, string $action, string $recordType, ?int $recordId = null, ?array $metadata = null): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $metaJson = $metadata ? json_encode($metadata) : null;

            $stmt = $this->db->prepare("
                INSERT INTO `audit_logs`
                (`user_id`, `action`, `affected_record_type`, `affected_record_id`, `metadata_json`, `ip_address`)
                VALUES
                (:uid, :action, :type, :rec_id, :meta, :ip)
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':action' => $action,
                ':type' => $recordType,
                ':rec_id' => $recordId,
                ':meta' => $metaJson,
                ':ip' => $ip
            ]);
        } catch (Exception $e) {
            // Failsafe: avoid crashing the main application if logging encounters an issue
            error_log("Audit log failed: " . $e->getMessage());
        }
    }

    public function getRecentLogs(int $limit = 100): array {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name as user_name, u.email as user_email
            FROM `audit_logs` a
            LEFT JOIN `users` u ON a.user_id = u.id
            ORDER BY a.id DESC
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
