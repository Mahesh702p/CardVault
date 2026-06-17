<?php
/**
 * Database-backed PHP Session Handler
 * Fixes session persistence on multi-instance Cloudways deployments
 * Uses MySQL as shared session store instead of local filesystem
 */
class DbSessionHandler implements SessionHandlerInterface {
    private PDO $db;
    private int $lifetime;

    private static string $createTableSql = "
        CREATE TABLE IF NOT EXISTS `php_sessions` (
            `session_id` VARCHAR(128) NOT NULL,
            `session_data` TEXT NOT NULL,
            `session_expiry` INT(10) UNSIGNED NOT NULL,
            PRIMARY KEY (`session_id`),
            KEY `session_expiry` (`session_expiry`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    public function open(string $path, string $name): bool {
        $this->db      = Database::getConnection();
        $this->lifetime = (int)ini_get('session.gc_maxlifetime') ?: 7200;
        // Auto-create table on ANY MySQL instance that's missing it
        try {
            $this->db->exec(self::$createTableSql);
        } catch (\Exception $e) {
            error_log('DbSession: Could not ensure table exists: ' . $e->getMessage());
        }
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string {
        try {
            $stmt = $this->db->prepare(
                "SELECT session_data FROM php_sessions WHERE session_id = ? AND session_expiry > ?"
            );
            $stmt->execute([$id, time()]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (string)$row['session_data'] : '';
        } catch (\Exception $e) {
            error_log('DbSession read error: ' . $e->getMessage());
            return '';
        }
    }

    public function write(string $id, string $data): bool {
        try {
            $expiry = time() + $this->lifetime;
            $stmt = $this->db->prepare(
                "REPLACE INTO php_sessions (session_id, session_data, session_expiry) VALUES (?, ?, ?)"
            );
            return $stmt->execute([$id, $data, $expiry]);
        } catch (\Exception $e) {
            error_log('DbSession write error: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM php_sessions WHERE session_id = ?");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false {
        try {
            $stmt = $this->db->prepare("DELETE FROM php_sessions WHERE session_expiry < ?");
            $stmt->execute([time()]);
            return $stmt->rowCount();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
