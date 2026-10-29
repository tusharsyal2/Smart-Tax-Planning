<?php
/**
 * User Repository
 * Handles authentication, user profile persistence, and security controls.
 * Smart Tax Planning Assistance
 */

require_once __DIR__ . '/../config/database.php';

class UserRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDb();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT u.*, p.financial_year, p.employment_category, p.residential_status, 
                   p.age_category, p.state, p.pan_number, p.default_regime
            FROM `users` u
            LEFT JOIN `financial_profiles` p ON u.id = p.user_id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM `users`
            WHERE `email` = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `status`)
                VALUES (:name, :email, :hash, :role, 'active')
            ");
            $stmt->execute([
                ':name' => trim($data['name']),
                ':email' => strtolower(trim($data['email'])),
                ':hash' => password_hash($data['password'], PASSWORD_BCRYPT),
                ':role' => $data['role'] ?? 'user'
            ]);
            $userId = (int)$this->db->lastInsertId();

            // Create initial financial profile
            $profileStmt = $this->db->prepare("
                INSERT INTO `financial_profiles` 
                (`user_id`, `financial_year`, `employment_category`, `residential_status`, `age_category`, `state`, `pan_number`, `default_regime`)
                VALUES
                (:uid, :fy, :emp, :res, :age, :state, :pan, :regime)
            ");
            $profileStmt->execute([
                ':uid' => $userId,
                ':fy' => $data['financial_year'] ?? '2024-25',
                ':emp' => $data['employment_category'] ?? 'salaried',
                ':res' => $data['residential_status'] ?? 'resident',
                ':age' => $data['age_category'] ?? 'below_60',
                ':state' => $data['state'] ?? 'Delhi',
                ':pan' => !empty($data['pan_number']) ? strtoupper(trim($data['pan_number'])) : null,
                ':regime' => $data['default_regime'] ?? 'new'
            ]);

            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updatePassword(int $userId, string $newPassword): bool {
        $stmt = $this->db->prepare("
            UPDATE `users`
            SET `password_hash` = :hash
            WHERE `id` = :id
        ");
        return $stmt->execute([
            ':id' => $userId,
            ':hash' => password_hash($newPassword, PASSWORD_BCRYPT)
        ]);
    }

    public function updateLastLogin(int $userId): void {
        $stmt = $this->db->prepare("UPDATE `users` SET `last_login` = NOW() WHERE `id` = :id");
        $stmt->execute([':id' => $userId]);
    }

    public function updateProfile(int $userId, array $data): bool {
        $this->db->beginTransaction();
        try {
            if (isset($data['name'])) {
                $uStmt = $this->db->prepare("UPDATE `users` SET `name` = :name WHERE `id` = :id");
                $uStmt->execute([':id' => $userId, ':name' => trim($data['name'])]);
            }

            // Check if profile exists
            $checkStmt = $this->db->prepare("SELECT id FROM `financial_profiles` WHERE `user_id` = :id");
            $checkStmt->execute([':id' => $userId]);
            if ($checkStmt->fetch()) {
                $pStmt = $this->db->prepare("
                    UPDATE `financial_profiles`
                    SET `financial_year` = :fy,
                        `employment_category` = :emp,
                        `residential_status` = :res,
                        `age_category` = :age,
                        `state` = :state,
                        `pan_number` = :pan,
                        `default_regime` = :regime
                    WHERE `user_id` = :id
                ");
            } else {
                $pStmt = $this->db->prepare("
                    INSERT INTO `financial_profiles`
                    (`financial_year`, `employment_category`, `residential_status`, `age_category`, `state`, `pan_number`, `default_regime`, `user_id`)
                    VALUES (:fy, :emp, :res, :age, :state, :pan, :regime, :id)
                ");
            }

            $pStmt->execute([
                ':id' => $userId,
                ':fy' => $data['financial_year'] ?? '2024-25',
                ':emp' => $data['employment_category'] ?? 'salaried',
                ':res' => $data['residential_status'] ?? 'resident',
                ':age' => $data['age_category'] ?? 'below_60',
                ':state' => $data['state'] ?? 'Delhi',
                ':pan' => !empty($data['pan_number']) ? strtoupper(trim($data['pan_number'])) : null,
                ':regime' => $data['default_regime'] ?? 'new'
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getAllUsers(int $limit = 50, int $offset = 0, string $search = ''): array {
        $sql = "SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, u.last_login,
                       p.financial_year, p.employment_category, p.default_regime
                FROM `users` u
                LEFT JOIN `financial_profiles` p ON u.id = p.user_id";
        
        $params = [];
        if (!empty($search)) {
            $sql .= " WHERE u.name LIKE :s OR u.email LIKE :s";
            $params[':s'] = "%{$search}%";
        }
        $sql .= " ORDER BY u.id DESC LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $userId, string $status): bool {
        $stmt = $this->db->prepare("UPDATE `users` SET `status` = :st WHERE `id` = :id");
        return $stmt->execute([':id' => $userId, ':st' => $status]);
    }

    public function deleteAccount(int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM `users` WHERE `id` = :id");
        return $stmt->execute([':id' => $userId]);
    }
}
