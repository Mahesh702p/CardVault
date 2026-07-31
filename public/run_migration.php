<?php
/**
 * Migration runner script
 */
$_basePath = file_exists(__DIR__ . '/../config/app.php') ? dirname(__DIR__) : __DIR__;
require_once $_basePath . '/config/app.php';
require_once $_basePath . '/config/database.php';
unset($_basePath);

header('Content-Type: text/plain');

try {
    $db = Database::getConnection();
    echo "Connected to database successfully.\n\n";

    // 1. Ensure email is nullable
    echo "1. Ensuring email column is nullable...\n";
    try {
        $db->exec("ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NULL");
        echo "   ✓ Email column updated.\n";
    } catch (Exception $e) {
        echo "   ⏭  Skipped or already done: " . $e->getMessage() . "\n";
    }

    // 2. Create teams table
    echo "\n2. Creating teams table if it doesn't exist...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_name VARCHAR(150) NOT NULL UNIQUE,
            team_code VARCHAR(50) NOT NULL UNIQUE,
            team_password VARCHAR(255) NOT NULL,
            created_by_user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   ✓ Teams table ready.\n";

    // 3. Add team_id to users
    echo "\n3. Adding team_id column to users...\n";
    $columns = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('team_id', $columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN team_id INT NULL DEFAULT NULL AFTER department_id");
        $db->exec("ALTER TABLE users ADD CONSTRAINT fk_user_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL");
        echo "   ✓ Column team_id and foreign key added successfully.\n";
    } else {
        echo "   ✓ Column team_id already exists.\n";
    }

    // 4. Create user_visit_history table
    echo "\n4. Creating user_visit_history table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_visit_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            contact_id INT NOT NULL,
            visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_contact (user_id, contact_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   ✓ user_visit_history table ready.\n";

    // 5. Upgrade cards_visibility column to support 3-tier privacy
    echo "\n5. Upgrading cards_visibility column to support 3-tier privacy...\n";
    try {
        $db->exec("ALTER TABLE users MODIFY COLUMN cards_visibility VARCHAR(50) NOT NULL DEFAULT 'public'");
        $db->exec("UPDATE users SET cards_visibility = 'private_team' WHERE cards_visibility = 'private'");
        $db->exec("ALTER TABLE users MODIFY COLUMN cards_visibility ENUM('public', 'private_team', 'private_user') NOT NULL DEFAULT 'public'");
        echo "   ✓ Upgraded successfully to (public, private_team, private_user).\n";
    } catch (Exception $e) {
        echo "   ❌ Failed to upgrade cards_visibility: " . $e->getMessage() . "\n";
    }

    // 6. Add cards_visibility and team_id to contacts table
    echo "\n6. Adding cards_visibility and team_id to contacts table...\n";
    try {
        $checkCols = $db->query("SHOW COLUMNS FROM contacts LIKE 'cards_visibility'")->fetchAll();
        if (empty($checkCols)) {
            $db->exec("ALTER TABLE contacts ADD COLUMN cards_visibility ENUM('public', 'private_team', 'private_user') NOT NULL DEFAULT 'public' AFTER added_by_department_id");
            echo "   ✓ Added cards_visibility column.\n";
        } else {
            echo "   ✓ Column cards_visibility already exists.\n";
        }

        $checkTeamId = $db->query("SHOW COLUMNS FROM contacts LIKE 'team_id'")->fetchAll();
        if (empty($checkTeamId)) {
            $db->exec("ALTER TABLE contacts ADD COLUMN team_id INT NULL AFTER cards_visibility");
            echo "   ✓ Added team_id column.\n";
        } else {
            echo "   ✓ Column team_id already exists.\n";
        }

        // Migrate existing data
        echo "   Migrating existing user visibility and team_id values...\n";
        $migrated = $db->exec("
            UPDATE contacts ct
            JOIN users u ON ct.added_by_user_id = u.id
            SET ct.cards_visibility = u.cards_visibility,
                ct.team_id = u.team_id
        ");
        echo "   ✓ Migration complete (rows updated: {$migrated}).\n";
    } catch (Exception $e) {
        echo "   ❌ Failed to add/migrate columns on contacts: " . $e->getMessage() . "\n";
    }

    // 7. Add SSO profile fields (mobile, designation, work_location) to users
    echo "\n7. Adding SSO profile fields to users table...\n";
    try {
        $userCols = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('mobile', $userCols)) {
            $db->exec("ALTER TABLE users ADD COLUMN mobile VARCHAR(20) NULL DEFAULT NULL AFTER email");
            echo "   ✓ Added column: mobile\n";
        } else {
            echo "   ✓ Column mobile already exists.\n";
        }
        if (!in_array('designation', $userCols)) {
            $db->exec("ALTER TABLE users ADD COLUMN designation VARCHAR(200) NULL DEFAULT NULL AFTER mobile");
            echo "   ✓ Added column: designation\n";
        } else {
            echo "   ✓ Column designation already exists.\n";
        }
        if (!in_array('work_location', $userCols)) {
            $db->exec("ALTER TABLE users ADD COLUMN work_location VARCHAR(100) NULL DEFAULT NULL AFTER designation");
            echo "   ✓ Added column: work_location\n";
        } else {
            echo "   ✓ Column work_location already exists.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Failed: " . $e->getMessage() . "\n";
    }

    // 8. Add password_is_temp column to users table
    echo "\n8. Adding password_is_temp column to users table...\n";
    try {
        $userCols = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('password_is_temp', $userCols)) {
            $db->exec("ALTER TABLE users ADD COLUMN password_is_temp BOOLEAN DEFAULT TRUE AFTER password_hash");
            echo "   ✓ Added column: password_is_temp\n";
            // For all existing users who have passwords, set it to false
            $db->exec("UPDATE users SET password_is_temp = FALSE WHERE password_hash IS NOT NULL");
            echo "   ✓ Updated existing users' password_is_temp to FALSE.\n";
        } else {
            echo "   ✓ Column password_is_temp already exists.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Failed: " . $e->getMessage() . "\n";
    }

    // 9. Update audit_log foreign key to ON DELETE SET NULL
    echo "\n9. Updating audit_log foreign key to ON DELETE SET NULL...\n";
    try {
        // We try to drop both possible constraint names (default auto-generated name and custom name)
        try {
            $db->exec("ALTER TABLE audit_log DROP FOREIGN KEY audit_log_ibfk_1");
            echo "   ✓ Dropped constraint: audit_log_ibfk_1\n";
        } catch (Exception $e) {
            // Ignore if not found
        }
        try {
            $db->exec("ALTER TABLE audit_log DROP FOREIGN KEY fk_audit_log_user");
            echo "   ✓ Dropped constraint: fk_audit_log_user\n";
        } catch (Exception $e) {
            // Ignore if not found
        }

        $db->exec("ALTER TABLE audit_log ADD CONSTRAINT fk_audit_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "   ✓ Added foreign key constraint with ON DELETE SET NULL.\n";
    } catch (Exception $e) {
        echo "   ❌ Failed to update audit_log foreign key: " . $e->getMessage() . "\n";
    }

    // 10. Flag existing users as having a temporary password (idempotent — only touches NULL rows)
    // Users who have already set their password have password_is_temp = FALSE and must NOT be touched.
    echo "\n10. Flagging users who have not yet set their password...\n";
    try {
        $affected = $db->exec("UPDATE users SET password_is_temp = TRUE WHERE password_is_temp IS NULL");
        echo "   ✓ Flagged " . $affected . " user(s) with unset passwords.\n";
    } catch (Exception $e) {
        echo "   ❌ Failed: " . $e->getMessage() . "\n";
    }

    // 11. Add is_team_admin column to users
    echo "\n11. Adding is_team_admin column to users...\n";
    try {
        $columns = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('is_team_admin', $columns)) {
            $db->exec("ALTER TABLE users ADD COLUMN is_team_admin BOOLEAN DEFAULT FALSE AFTER team_id");
            echo "   ✓ Column is_team_admin added to users table.\n";
        } else {
            echo "   ✓ Column is_team_admin already exists.\n";
        }
        
        // Sync team admins: set creators of teams as admin
        $db->exec("UPDATE users u JOIN teams t ON u.id = t.created_by_user_id SET u.is_team_admin = TRUE");
        echo "   ✓ Sync'ed team admin privileges for team creators.\n";
    } catch (Exception $e) {
        echo "   ❌ Failed: " . $e->getMessage() . "\n";
    }

    // 12. Create team_invitations table
    echo "\n12. Creating team_invitations table if it doesn't exist...\n";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS team_invitations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                team_id INT NOT NULL,
                user_id INT NOT NULL,
                invited_by_user_id INT NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (invited_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY uq_team_user_pending (team_id, user_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "   ✓ Team invitations table ready.\n";
    } catch (Exception $e) {
        echo "   ❌ Failed to create team_invitations: " . $e->getMessage() . "\n";
    }

    // 13. One-time repair: fix password_is_temp broken by old buggy Step 10
    // The old Step 10 set ALL users' password_is_temp = TRUE, including users who had already set their password.
    // Step 8 originally set password_is_temp = FALSE for all users with a hash.
    // We fix this by: creating a migration_flags table and running this repair only once.
    echo "\n13. Repairing password_is_temp damage from old Step 10...\n";
    try {
        // Create a migration_flags table to record one-time migrations
        $db->exec("
            CREATE TABLE IF NOT EXISTS migration_flags (
                flag_key VARCHAR(100) PRIMARY KEY,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $flagExists = $db->query("SELECT COUNT(*) FROM migration_flags WHERE flag_key = 'step10_password_repair'")->fetchColumn();
        if (!$flagExists) {
            // Repair: set password_is_temp = FALSE for ALL users who have a password_hash
            // (Step 8 originally did this correctly; Step 10 broke it)
            // Users who truly need to set password (SSO-created since Step 10 ran) will already have
            // password_is_temp = TRUE from their creation, and we DO NOT touch those here intentionally.
            // We only fix users who had it wrongly flipped from FALSE → TRUE by the old migration.
            // The safest heuristic: reset everyone to FALSE, then re-flag only users created
            // AFTER the migration column was added who haven't logged in and changed it themselves.
            // Since we can't perfectly detect this, we simply reset all to FALSE.
            // Users who genuinely haven't set a password will see the popup again exactly once.
            $affected = $db->exec("UPDATE users SET password_is_temp = FALSE WHERE password_is_temp = TRUE AND password_hash IS NOT NULL");
            $db->exec("INSERT INTO migration_flags (flag_key) VALUES ('step10_password_repair')");
            echo "   ✓ Repair complete. Reset password_is_temp for " . $affected . " user(s).\n";
            echo "   ✓ Migration flag recorded — this step will not run again.\n";
        } else {
            echo "   ✓ Already applied. Skipping.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Failed: " . $e->getMessage() . "\n";
    }

    // 14. Add password_changed_by_user column to users table
    echo "\n14. Adding password_changed_by_user column to users table...\n";
    try {
        $userCols = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('password_changed_by_user', $userCols)) {
            $db->exec("ALTER TABLE users ADD COLUMN password_changed_by_user BOOLEAN DEFAULT FALSE AFTER password_is_temp");
            echo "   ✓ Added column: password_changed_by_user\n";
            // Initialize existing users to FALSE (0) so they are prompted to set password once
            $db->exec("UPDATE users SET password_changed_by_user = FALSE");
            echo "   ✓ Initialized all existing users to FALSE.\n";
        } else {
            echo "   ✓ Column password_changed_by_user already exists.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Failed: " . $e->getMessage() . "\n";
    }

    echo "\n🎉 All migrations executed successfully!";
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage();
}

