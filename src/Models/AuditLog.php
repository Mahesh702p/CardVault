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
}
