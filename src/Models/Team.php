<?php
/**
 * Team Model
 */

class Team {
    /**
     * Find team by ID
     */
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM teams WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find team by unique code
     */
    public static function findByCode(string $code): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM teams WHERE LOWER(team_code) = LOWER(:code)");
        $stmt->execute([':code' => trim($code)]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create a new team
     */
    public static function create(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO teams (team_name, team_code, team_password, created_by_user_id)
            VALUES (:team_name, :team_code, :team_password, :created_by_user_id)
        ");
        $stmt->execute([
            ':team_name'          => trim($data['team_name']),
            ':team_code'          => strtolower(trim($data['team_code'])),
            ':team_password'      => password_hash($data['team_password'], PASSWORD_DEFAULT),
            ':created_by_user_id' => $data['created_by_user_id']
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Join a user to a team
     */
    public static function join(int $userId, int $teamId, bool $isAdmin = false): void {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE users SET team_id = :team_id, is_team_admin = :is_admin WHERE id = :user_id");
            $stmt->execute([
                ':team_id'   => $teamId,
                ':is_admin'  => $isAdmin ? 1 : 0,
                ':user_id'   => $userId
            ]);

            $stmt2 = $db->prepare("UPDATE contacts SET team_id = :team_id WHERE added_by_user_id = :user_id");
            $stmt2->execute([
                ':team_id' => $teamId,
                ':user_id' => $userId
            ]);
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Remove a user from their team
     */
    public static function leave(int $userId): void {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE users SET team_id = NULL, is_team_admin = FALSE WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);

            $stmt2 = $db->prepare("UPDATE contacts SET team_id = NULL WHERE added_by_user_id = :user_id");
            $stmt2->execute([':user_id' => $userId]);
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get all members in a team
     */
    public static function getMembers(int $teamId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.employee_id, u.role, u.is_team_admin, d.name AS department_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.team_id = :team_id AND u.is_active = 1
            ORDER BY u.name
        ");
        $stmt->execute([':team_id' => $teamId]);
        return $stmt->fetchAll();
    }

    /**
     * Set a member's admin privilege status
     */
    public static function setAdminStatus(int $userId, bool $isAdmin): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET is_team_admin = :is_admin WHERE id = :user_id");
        $stmt->execute([
            ':is_admin' => $isAdmin ? 1 : 0,
            ':user_id'  => $userId
        ]);
    }

    /**
     * Update team name and code
     */
    public static function updateTeamName(int $teamId, string $newName, string $newCode): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE teams SET team_name = :name, team_code = :code WHERE id = :id");
        $stmt->execute([
            ':name' => trim($newName),
            ':code' => strtolower(trim($newCode)),
            ':id'   => $teamId
        ]);
    }

    /**
     * Get all active users who are not in any team currently and do not have a pending invite from this team
     */
    public static function getUsersWithoutTeam(int $teamId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.employee_id, d.name AS department_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.team_id IS NULL 
              AND u.is_active = 1
              AND u.id NOT IN (
                  SELECT user_id FROM team_invitations WHERE team_id = :team_id AND status = 'pending'
              )
            ORDER BY u.name
        ");
        $stmt->execute([':team_id' => $teamId]);
        return $stmt->fetchAll();
    }

    /**
     * Send a team invitation to a user
     */
    public static function sendInvite(int $teamId, int $userId, int $invitedByUserId): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO team_invitations (team_id, user_id, invited_by_user_id)
            VALUES (:team_id, :user_id, :invited_by_user_id)
            ON DUPLICATE KEY UPDATE status = 'pending', created_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            ':team_id' => $teamId,
            ':user_id' => $userId,
            ':invited_by_user_id' => $invitedByUserId
        ]);
    }

    /**
     * Get pending team invitations for a user
     */
    public static function getPendingInvitesForUser(int $userId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT ti.id, ti.team_id, ti.invited_by_user_id, ti.created_at, t.team_name, u.name AS inviter_name
            FROM team_invitations ti
            JOIN teams t ON ti.team_id = t.id
            JOIN users u ON ti.invited_by_user_id = u.id
            WHERE ti.user_id = :user_id AND ti.status = 'pending'
            ORDER BY ti.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get pending invitations sent by a team
     */
    public static function getPendingInvitesForTeam(int $teamId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT ti.id, ti.user_id, ti.created_at, u.name AS user_name, u.employee_id, d.name AS department_name
            FROM team_invitations ti
            JOIN users u ON ti.user_id = u.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE ti.team_id = :team_id AND ti.status = 'pending'
            ORDER BY ti.created_at DESC
        ");
        $stmt->execute([':team_id' => $teamId]);
        return $stmt->fetchAll();
    }

    /**
     * Find invite by ID
     */
    public static function findInviteById(int $inviteId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM team_invitations WHERE id = :id");
        $stmt->execute([':id' => $inviteId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Accept a team invitation
     */
    public static function acceptInvite(int $inviteId): void {
        $db = Database::getConnection();

        // Safeguard: roll back any stale transaction left from a previous failed request
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        try {
            $db->beginTransaction();
            
            // Get the invite details
            $stmt = $db->prepare("SELECT * FROM team_invitations WHERE id = :id");
            $stmt->execute([':id' => $inviteId]);
            $invite = $stmt->fetch();
            if (!$invite) {
                throw new Exception("Invitation not found.");
            }

            // Clean up old invitations for the same user and team to avoid unique constraint violations (e.g. duplicate accepted)
            $stmtClean = $db->prepare("DELETE FROM team_invitations WHERE user_id = :user_id AND team_id = :team_id AND id != :id");
            $stmtClean->execute([
                ':user_id' => $invite['user_id'],
                ':team_id' => $invite['team_id'],
                ':id'      => $inviteId
            ]);

            // Update status to accepted
            $stmtUpdate = $db->prepare("UPDATE team_invitations SET status = 'accepted' WHERE id = :id");
            $stmtUpdate->execute([':id' => $inviteId]);

            // Decline all other pending invites for this user
            $stmtDeclineOthers = $db->prepare("UPDATE team_invitations SET status = 'declined' WHERE user_id = :user_id AND id != :id AND status = 'pending'");
            $stmtDeclineOthers->execute([
                ':user_id' => $invite['user_id'],
                ':id'      => $inviteId
            ]);

            // Join the team — inline to avoid nested transaction conflict
            $stmtUser = $db->prepare("UPDATE users SET team_id = :team_id, is_team_admin = 0 WHERE id = :user_id");
            $stmtUser->execute([
                ':team_id'  => $invite['team_id'],
                ':user_id'  => $invite['user_id']
            ]);

            $stmtContacts = $db->prepare("UPDATE contacts SET team_id = :team_id WHERE added_by_user_id = :user_id");
            $stmtContacts->execute([
                ':team_id'  => $invite['team_id'],
                ':user_id'  => $invite['user_id']
            ]);

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Decline a team invitation
     */
    public static function declineInvite(int $inviteId): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE team_invitations SET status = 'declined' WHERE id = :id");
        $stmt->execute([':id' => $inviteId]);
    }

    /**
     * Cancel a team invitation (sent by admin)
     */
    public static function cancelInvite(int $inviteId): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM team_invitations WHERE id = :id");
        $stmt->execute([':id' => $inviteId]);
    }

    /**
     * Disband team: remove all users and delete the team row
     */
    public static function disband(int $teamId): void {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();
            
            // Remove team from all users and strip admin privilege
            $stmt = $db->prepare("UPDATE users SET team_id = NULL, is_team_admin = FALSE WHERE team_id = :team_id");
            $stmt->execute([':team_id' => $teamId]);

            // Remove team from all contacts in this team
            $stmt2 = $db->prepare("UPDATE contacts SET team_id = NULL WHERE team_id = :team_id");
            $stmt2->execute([':team_id' => $teamId]);

            // Delete the team
            $stmt3 = $db->prepare("DELETE FROM teams WHERE id = :team_id");
            $stmt3->execute([':team_id' => $teamId]);

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update team password
     */
    public static function updatePassword(int $teamId, string $newPassword): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE teams SET team_password = :password WHERE id = :id");
        $stmt->execute([
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id' => $teamId
        ]);
    }

    /**
     * Update team admin (created_by_user_id)
     */
    public static function updateAdmin(int $teamId, int $newAdminUserId): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE teams SET created_by_user_id = :created_by_user_id WHERE id = :id");
        $stmt->execute([
            ':created_by_user_id' => $newAdminUserId,
            ':id' => $teamId
        ]);
    }

    /**
     * Get all teams with stats (creator name and member counts)
     */
    public static function allWithStats(): array {
        $db = Database::getConnection();
        return $db->query("
            SELECT t.id, t.team_name, t.team_code, t.created_at, u.name AS creator_name, COUNT(u_mem.id) AS member_count
            FROM teams t
            LEFT JOIN users u ON t.created_by_user_id = u.id
            LEFT JOIN users u_mem ON t.id = u_mem.team_id
            GROUP BY t.id
            ORDER BY t.created_at DESC
        ")->fetchAll();
    }
}
