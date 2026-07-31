<?php
/**
 * Contact Model — Core visiting card data
 */

class Contact {
    /**
     * Create a new contact from card scan data
     */
    public static function create(array $data): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO contacts (
                company_id, name, designation, department_in_company,
                phone_primary, phone_secondary, email_primary, email_secondary,
                linkedin_url, card_front_image, card_back_image,
                added_by_user_id, added_by_department_id, cards_visibility, team_id,
                ai_confidence_score, is_verified
            ) VALUES (
                :company_id, :name, :designation, :department_in_company,
                :phone_primary, :phone_secondary, :email_primary, :email_secondary,
                :linkedin_url, :card_front_image, :card_back_image,
                :added_by_user_id, :added_by_department_id, :cards_visibility, :team_id,
                :ai_confidence_score, :is_verified
            )
        ");
        $stmt->execute([
            ':company_id' => $data['company_id'],
            ':name' => $data['name'],
            ':designation' => $data['designation'] ?? '',
            ':department_in_company' => $data['department_in_company'] ?? '',
            ':phone_primary' => $data['phone_primary'] ?? '',
            ':phone_secondary' => $data['phone_secondary'] ?? '',
            ':email_primary' => $data['email_primary'] ?? '',
            ':email_secondary' => $data['email_secondary'] ?? '',
            ':linkedin_url' => $data['linkedin_url'] ?? '',
            ':card_front_image' => $data['card_front_image'] ?? '',
            ':card_back_image' => $data['card_back_image'] ?? '',
            ':added_by_user_id' => $data['added_by_user_id'],
            ':added_by_department_id' => $data['added_by_department_id'],
            ':cards_visibility' => $data['cards_visibility'] ?? 'public',
            ':team_id' => $data['team_id'] ?? null,
            ':ai_confidence_score' => $data['ai_confidence_score'] ?? null,
            ':is_verified' => $data['is_verified'] ?? false
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Find a duplicate contact by name and company scanned/created by the current user
     */
    public static function findDuplicate(string $name, int $companyId, int $currentUserId, ?int $currentUserTeamId = null): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT ct.id FROM contacts ct
            WHERE LOWER(TRIM(ct.name)) = LOWER(TRIM(:name)) 
              AND ct.company_id = :company_id 
              AND ct.is_deleted = 0 
              AND ct.added_by_user_id = :current_user_id
            LIMIT 1
        ");
        $stmt->execute([
            ':name' => $name,
            ':company_id' => $companyId,
            ':current_user_id' => $currentUserId
        ]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find contact by ID with company data
     */
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT ct.*, co.name AS company_name, co.website, co.industry, co.city AS company_city,
                   co.address AS company_address, co.gst_number, co.notes AS company_notes,
                   u.name AS added_by_name, u.cards_visibility AS user_default_visibility, u.team_id AS added_by_user_team_id,
                   d.name AS added_by_dept_name,
                   GROUP_CONCAT(DISTINCT ps.name SEPARATOR ', ') AS products_services,
                   GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') AS tags
            FROM contacts ct
            LEFT JOIN companies co ON ct.company_id = co.id
            LEFT JOIN users u ON ct.added_by_user_id = u.id
            LEFT JOIN departments d ON ct.added_by_department_id = d.id
            LEFT JOIN company_products cp ON co.id = cp.company_id
            LEFT JOIN products_services ps ON cp.product_service_id = ps.id
            LEFT JOIN contact_tags ctt ON ct.id = ctt.contact_id
            LEFT JOIN tags t ON ctt.tag_id = t.id
            WHERE ct.id = :id AND ct.is_deleted = 0
            GROUP BY ct.id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get paginated contacts with filters
     */
    public static function getFiltered(
        ?int $userId = null,
        ?int $deptId = null,
        int $page = 1,
        int $perPage = ITEMS_PER_PAGE,
        ?int $currentUserId = null,
        bool $isAdmin = false,
        ?int $currentTeamId = null,
        ?int $scopeTeamId = null
    ): array {
        $db = Database::getConnection();
        $conditions = ["ct.is_deleted = 0"];
        $params = [];

        if ($userId) {
            // My Cards: only cards scanned from my account
            $conditions[] = "ct.added_by_user_id = :uid";
            $params[':uid'] = $userId;
        } elseif ($scopeTeamId) {
            // My Team: all cards of my team (public or private_team only)
            $conditions[] = "ct.team_id = :scope_team_id AND ct.cards_visibility IN ('public', 'private_team')";
            $params[':scope_team_id'] = $scopeTeamId;
        } else {
            // All Cards: all publicly available cards (or all if admin)
            if (!$isAdmin) {
                if ($currentTeamId !== null) {
                    $conditions[] = "(ct.cards_visibility = 'public' OR ct.added_by_user_id = :cur_uid OR (ct.cards_visibility = 'private_team' AND ct.team_id = :cur_team_id))";
                    $params[':cur_uid'] = $currentUserId;
                    $params[':cur_team_id'] = $currentTeamId;
                } else {
                    $conditions[] = "(ct.cards_visibility = 'public' OR ct.added_by_user_id = :cur_uid)";
                    $params[':cur_uid'] = $currentUserId;
                }
            }
        }

        if ($deptId) {
            $conditions[] = "ct.added_by_department_id = :did";
            $params[':did'] = $deptId;
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        // Get total count (must include users JOIN for privacy filter)
        $countSql = "SELECT COUNT(DISTINCT ct.id) FROM contacts ct
                     LEFT JOIN users u ON ct.added_by_user_id = u.id
                     WHERE {$where}";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Get results
        $sql = "
            SELECT ct.id, ct.name, ct.designation, ct.phone_primary, ct.email_primary,
                   ct.card_front_image, ct.ai_confidence_score, ct.is_verified, ct.created_at,
                   ct.added_by_user_id, ct.cards_visibility,
                   ct.rating_count, ct.rating_avg, ct.rating_bayesian,
                   co.name AS company_name, co.industry, co.city AS company_city,
                   u.name AS added_by_name, u.cards_visibility AS user_default_visibility, d.name AS dept_name,
                   GROUP_CONCAT(DISTINCT ps.name SEPARATOR ', ') AS products_services
            FROM contacts ct
            LEFT JOIN companies co ON ct.company_id = co.id
            LEFT JOIN users u ON ct.added_by_user_id = u.id
            LEFT JOIN departments d ON ct.added_by_department_id = d.id
            LEFT JOIN company_products cp ON co.id = cp.company_id
            LEFT JOIN products_services ps ON cp.product_service_id = ps.id
            WHERE {$where}
            GROUP BY ct.id
            ORDER BY
                CASE WHEN ct.rating_bayesian IS NOT NULL THEN 0 ELSE 1 END,
                ct.rating_bayesian DESC,
                ct.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }


    /**
     * Update a contact
     */
    public static function update(int $id, array $data): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE contacts SET
                name = :name, designation = :designation, department_in_company = :department_in_company,
                phone_primary = :phone_primary, phone_secondary = :phone_secondary,
                email_primary = :email_primary, email_secondary = :email_secondary,
                linkedin_url = :linkedin_url, is_verified = :is_verified,
                cards_visibility = :cards_visibility, team_id = :team_id
            WHERE id = :id AND is_deleted = 0
        ");
        $data[':id'] = $id;
        $stmt->execute($data);
    }

    /**
     * Soft delete a contact
     */
    public static function delete(int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE contacts SET is_deleted = 1, deleted_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Mark a contact as verified
     */
    public static function verify(int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE contacts SET is_verified = 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public static function getStats(?int $userId = null, ?int $deptId = null, ?int $currentUserId = null, bool $isAdmin = false, ?int $currentTeamId = null): array {
        $db = Database::getConnection();
        
        // Build privacy condition
        $privacyCond = "";
        $params = [];
        if (!$isAdmin && $currentUserId !== null) {
            if ($currentTeamId !== null) {
                $privacyCond = " AND (ct.cards_visibility = 'public' OR ct.added_by_user_id = :cur_uid OR (ct.cards_visibility = 'private_team' AND ct.team_id = :cur_team_id))";
                $params[':cur_uid'] = $currentUserId;
                $params[':cur_team_id'] = $currentTeamId;
            } else {
                $privacyCond = " AND (ct.cards_visibility = 'public' OR ct.added_by_user_id = :cur_uid)";
                $params[':cur_uid'] = $currentUserId;
            }
        }

        // 1. Total Cards count (Complete count of all non-deleted cards across the entire application)
        $totalStmt = $db->query("SELECT COUNT(*) FROM contacts ct WHERE ct.is_deleted = 0");
        $total = (int)$totalStmt->fetchColumn();

        // 2. Total Public Cards Accessible count (Cards which are public)
        $publicStmt = $db->query("
            SELECT COUNT(*) FROM contacts ct
            WHERE ct.is_deleted = 0 AND ct.cards_visibility = 'public'
        ");
        $publicCards = (int)$publicStmt->fetchColumn();

        // 3. Recent Cards list
        if ($privacyCond) {
            $recentStmt = $db->prepare("
                SELECT ct.id, ct.name, ct.designation, co.name AS company_name, ct.created_at,
                       u.name AS added_by_name, ct.card_front_image
                FROM contacts ct
                LEFT JOIN companies co ON ct.company_id = co.id
                LEFT JOIN users u ON ct.added_by_user_id = u.id
                WHERE ct.is_deleted = 0 {$privacyCond}
                ORDER BY ct.created_at DESC
                LIMIT 5
            ");
            $recentStmt->execute($params);
            $recentCards = $recentStmt->fetchAll();
        } else {
            $recentCards = $db->query("
                SELECT ct.id, ct.name, ct.designation, co.name AS company_name, ct.created_at,
                       u.name AS added_by_name, ct.card_front_image
                FROM contacts ct
                LEFT JOIN companies co ON ct.company_id = co.id
                LEFT JOIN users u ON ct.added_by_user_id = u.id
                WHERE ct.is_deleted = 0
                ORDER BY ct.created_at DESC
                LIMIT 5
            ")->fetchAll();
        }

        // 4. User's own cards count
        $myCards = 0;
        if ($userId) {
            $myStmt = $db->prepare("SELECT COUNT(*) FROM contacts WHERE added_by_user_id = :uid AND is_deleted = 0");
            $myStmt->execute([':uid' => $userId]);
            $myCards = (int)$myStmt->fetchColumn();
        }

        // 5. Team cards count (Public and private_team only, excluding private_user)
        $teamCards = 0;
        if ($currentTeamId !== null) {
            $teamStmt = $db->prepare("
                SELECT COUNT(*) FROM contacts ct
                WHERE ct.team_id = :team_id AND ct.is_deleted = 0 AND ct.cards_visibility IN ('public', 'private_team')
            ");
            $teamStmt->execute([':team_id' => $currentTeamId]);
            $teamCards = (int)$teamStmt->fetchColumn();
        }

        return [
            'total_cards' => $total,
            'public_cards' => $publicCards,
            'my_cards' => $myCards,
            'team_cards' => $teamCards,
            'recent_cards' => $recentCards
        ];
    }
}
