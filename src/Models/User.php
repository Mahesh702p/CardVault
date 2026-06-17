<?php
/**
 * User Model
 */

class User {
    /**
     * Find user by Employee ID (used for login)
     */
    public static function findByEmployeeId(string $employeeId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, d.name as department_name
            FROM users u
            JOIN departments d ON u.department_id = d.id
            WHERE u.employee_id = :emp_id AND u.is_active = 1
        ");
        $stmt->execute([':emp_id' => $employeeId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find user by email (kept for admin use)
     */
    public static function findByEmail(string $email): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, d.name as department_name 
            FROM users u 
            JOIN departments d ON u.department_id = d.id 
            WHERE u.email = :email AND u.is_active = 1
        ");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find user by ID
     */
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.*, d.name as department_name 
            FROM users u 
            JOIN departments d ON u.department_id = d.id 
            WHERE u.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all active users
     */
    public static function all(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT u.*, d.name as department_name 
            FROM users u 
            JOIN departments d ON u.department_id = d.id 
            WHERE u.is_active = 1
            ORDER BY u.name
        ");
        return $stmt->fetchAll();
    }

    public static function create(array $data): int {
        $db = Database::getConnection();

        // Check if user with this employee_id exists and is inactive (soft-deactivated)
        if (!empty($data['employee_id'])) {
            $stmt = $db->prepare("SELECT id, is_active FROM users WHERE employee_id = :emp_id LIMIT 1");
            $stmt->execute([':emp_id' => $data['employee_id']]);
            $existing = $stmt->fetch();

            if ($existing && !$existing['is_active']) {
                // Reactivate the old deactivated account
                $stmt = $db->prepare("
                    UPDATE users SET
                        name = :name,
                        email = :email,
                        password_hash = :password_hash,
                        department_id = :department_id,
                        role = :role,
                        is_active = 1
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':name'          => $data['name'],
                    ':email'         => $data['email'] ?? null,
                    ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                    ':department_id' => $data['department_id'],
                    ':role'          => $data['role'] ?? 'user',
                    ':id'            => $existing['id']
                ]);
                return (int)$existing['id'];
            }
        }

        $stmt = $db->prepare("
            INSERT INTO users (employee_id, name, email, password_hash, department_id, role)
            VALUES (:employee_id, :name, :email, :password_hash, :department_id, :role)
        ");
        $stmt->execute([
            ':employee_id'   => $data['employee_id'] ?: null,
            ':name'          => $data['name'],
            ':email'         => $data['email'] ?? null,
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':department_id' => $data['department_id'],
            ':role'          => $data['role'] ?? 'user'
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Update user profile (name, email, department, role, employee_id)
     */
    public static function update(int $id, array $data): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE users SET
                name = :name,
                email = :email,
                department_id = :department_id,
                role = :role,
                employee_id = :employee_id
            WHERE id = :id
        ");
        $stmt->execute([
            ':name'          => $data['name'],
            ':email'         => $data['email'] ?? null,
            ':department_id' => $data['department_id'],
            ':role'          => $data['role'],
            ':employee_id'   => $data['employee_id'] ?: null,
            ':id'            => $id
        ]);
    }

    /**
     * Reset a user's password (admin action)
     */
    public static function resetPassword(int $id, string $newPassword): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $stmt->execute([
            ':hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id'   => $id
        ]);
    }

    /**
     * Change own password (requires old password verification)
     */
    public static function changePassword(int $id, string $oldPassword, string $newPassword): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($oldPassword, $row['password_hash'])) {
            return false;
        }

        self::resetPassword($id, $newPassword);
        return true;
    }

    /**
     * Update a user's card visibility preference (public/private)
     */
    public static function updateVisibility(int $id, string $visibility): void {
        $db = Database::getConnection();
        $v = $visibility === 'private' ? 'private' : 'public';
        $stmt = $db->prepare("UPDATE users SET cards_visibility = :v WHERE id = :id");
        $stmt->execute([':v' => $v, ':id' => $id]);
    }

    /**
     * Soft-deactivate a user (does not delete DB row or their cards)
     */
    public static function deactivate(int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Get all departments
     */
    public static function getDepartments(): array {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM departments ORDER BY name")->fetchAll();
    }

    /**
     * Get or Create Department by Name
     */
    public static function getOrCreateDepartment(string $name): int {
        $db = Database::getConnection();
        $name = trim($name);
        
        $stmt = $db->prepare("SELECT id FROM departments WHERE name LIKE :name");
        $stmt->execute([':name' => $name]);
        $id = $stmt->fetchColumn();
        
        if ($id) {
            return (int)$id;
        }
        
        // Create new
        $stmt = $db->prepare("INSERT INTO departments (name, description) VALUES (:name, :desc)");
        $stmt->execute([
            ':name' => $name,
            ':desc' => $name . ' Department'
        ]);
        return (int)$db->lastInsertId();
    }
}
