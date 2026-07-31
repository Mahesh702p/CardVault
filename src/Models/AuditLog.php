<?php
/**
 * Audit Log Model
 */

class AuditLog {
    /**
     * Record an audit event
     */
    public static function log(string $action, string $entityType = '', int $entityId = 0, array $oldValues = [], array $newValues = []): void {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO audit_log (user_id, action, entity_type, entity_id, old_values, new_values, ip_address)
                VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address)
            ");
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':action' => $action,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                ':new_values' => !empty($newValues) ? json_encode($newValues) : null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
        }
    }

    /**
     * Get paginated audit logs with user names
     */
    public static function getLogs(int $page = 1, int $perPage = 50): array {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;
        
        $totalStmt = $db->query("SELECT COUNT(*) FROM audit_log");
        $total = (int)$totalStmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT al.*, u.name AS user_name, u.email AS user_email
            FROM audit_log al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        return [
            'data' => $logs,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
}
