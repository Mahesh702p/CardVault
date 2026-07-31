<?php
/**
 * Search Service — Full-text and filtered search
 */

class SearchService {
    /**
     * Search contacts across all relevant tables
     */
    public static function search(
        string $query,
        ?int $userId = null,
        ?int $deptId = null,
        ?string $industry = null,
        ?string $city = null,
        int $page = 1,
        int $perPage = ITEMS_PER_PAGE,
        ?int $currentUserId = null,
        bool $isAdmin = false,
        ?int $currentTeamId = null,
        ?int $scopeTeamId = null
    ): array {
        $db = Database::getConnection();
        $conditions = ["ct.is_deleted = 0"];
        $whereParams = [];
        $relevanceParams = [];



        // AI-powered smart search with intent-aware query expansion
        if (!empty(trim($query))) {
            $searchTerm = trim($query);

            // Check if search term matches a contact's name or company's name directly in our database
            $isDirectMatch = false;
            if (strlen($searchTerm) >= 2) {
                if ($isAdmin || $currentUserId === null) {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM contacts WHERE name LIKE :q AND is_deleted = 0");
                    $stmt->execute([':q' => '%' . $searchTerm . '%']);
                } else {
                    if ($currentTeamId !== null) {
                        $stmt = $db->prepare("
                            SELECT COUNT(*) FROM contacts ct
                            WHERE ct.name LIKE :q AND ct.is_deleted = 0
                              AND (ct.cards_visibility = 'public' OR ct.added_by_user_id = :current_uid OR (ct.cards_visibility = 'private_team' AND ct.team_id = :current_team_id))
                        ");
                        $stmt->execute([
                            ':q' => '%' . $searchTerm . '%',
                            ':current_uid' => $currentUserId,
                            ':current_team_id' => $currentTeamId
                        ]);
                    } else {
                        $stmt = $db->prepare("
                            SELECT COUNT(*) FROM contacts ct
                            WHERE ct.name LIKE :q AND ct.is_deleted = 0
                              AND (ct.cards_visibility = 'public' OR ct.added_by_user_id = :current_uid)
                        ");
                        $stmt->execute([
                            ':q' => '%' . $searchTerm . '%',
                            ':current_uid' => $currentUserId
                        ]);
                    }
                }
                $contactCount = (int)$stmt->fetchColumn();

                $stmt = $db->prepare("SELECT COUNT(*) FROM companies WHERE name LIKE :q");
                $stmt->execute([':q' => '%' . $searchTerm . '%']);
                $companyCount = (int)$stmt->fetchColumn();

                if ($contactCount > 0 || $companyCount > 0) {
                    $isDirectMatch = true;
                }
            }

            if ($isDirectMatch) {
                $primaryTerms   = [$searchTerm];
                $secondaryTerms = [];
                $excludeTerms   = [];
            } else {
                $expansion      = AIService::expandSearchQuery($searchTerm);
                $primaryTerms   = $expansion['primary'] ?? $expansion['include'] ?? [$searchTerm];
                $secondaryTerms = $expansion['secondary'] ?? [];
                $excludeTerms   = $expansion['exclude'] ?? [];
            }

            // ── Build INCLUDE conditions with relevance scoring ─────────────
            // Primary terms: weight 10, Secondary terms: weight 3
            $termConditions = [];
            $relevanceExprs = [];

            foreach ($primaryTerms as $i => $term) {
                $cleanedTerm = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $term);
                $cleanedTerm = preg_replace('/\s+/', ' ', trim($cleanedTerm));
                if (empty($cleanedTerm)) {
                    continue;
                }
                $likeTerm = '%' . $cleanedTerm . '%';
                $booleanTerm = '+' . str_replace(' ', ' +', $cleanedTerm) . '*';
                $idx = "p{$i}";

                $termConditions[] = "(
                    MATCH(ct.name, ct.designation, ct.phone_primary, ct.email_primary) AGAINST(:ft_a{$idx} IN BOOLEAN MODE)
                    OR MATCH(co.name, co.website, co.industry, co.address, co.notes) AGAINST(:ft_b{$idx} IN BOOLEAN MODE)
                    OR MATCH(ps.name, ps.category) AGAINST(:ft_c{$idx} IN BOOLEAN MODE)
                    OR ct.name LIKE :lk_1_{$idx}
                    OR co.name LIKE :lk_2_{$idx}
                    OR ps.name LIKE :lk_3_{$idx}
                    OR ps.category LIKE :lk_4_{$idx}
                    OR co.industry LIKE :lk_5_{$idx}
                    OR ct.designation LIKE :lk_6_{$idx}
                    OR t.name LIKE :lk_7_{$idx}
                )";
                $whereParams[":ft_a{$idx}"] = $whereParams[":ft_b{$idx}"] = $whereParams[":ft_c{$idx}"] = $booleanTerm;
                $whereParams[":lk_1_{$idx}"] = $whereParams[":lk_2_{$idx}"] = $whereParams[":lk_3_{$idx}"] = $likeTerm;
                $whereParams[":lk_4_{$idx}"] = $whereParams[":lk_5_{$idx}"] = $whereParams[":lk_6_{$idx}"] = $likeTerm;
                $whereParams[":lk_7_{$idx}"] = $likeTerm;

                // Relevance score: 10 points per primary term match
                $relevanceExprs[] = "MAX(CASE WHEN ps.name LIKE :rs_ps_{$idx} OR ps.category LIKE :rs_pc_{$idx} OR co.industry LIKE :rs_in_{$idx} OR t.name LIKE :rs_tg_{$idx} THEN 10 ELSE 0 END)";
                $relevanceParams[":rs_ps_{$idx}"] = $likeTerm;
                $relevanceParams[":rs_pc_{$idx}"] = $likeTerm;
                $relevanceParams[":rs_in_{$idx}"] = $likeTerm;
                $relevanceParams[":rs_tg_{$idx}"] = $likeTerm;
            }

            foreach ($secondaryTerms as $i => $term) {
                $cleanedTerm = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $term);
                $cleanedTerm = preg_replace('/\s+/', ' ', trim($cleanedTerm));
                if (empty($cleanedTerm)) {
                    continue;
                }
                $likeTerm = '%' . $cleanedTerm . '%';
                $booleanTerm = '+' . str_replace(' ', ' +', $cleanedTerm) . '*';
                $idx = "s{$i}";

                $termConditions[] = "(
                    MATCH(ct.name, ct.designation, ct.phone_primary, ct.email_primary) AGAINST(:ft_a{$idx} IN BOOLEAN MODE)
                    OR MATCH(co.name, co.website, co.industry, co.address, co.notes) AGAINST(:ft_b{$idx} IN BOOLEAN MODE)
                    OR MATCH(ps.name, ps.category) AGAINST(:ft_c{$idx} IN BOOLEAN MODE)
                    OR ps.name LIKE :lk_3_{$idx}
                    OR ps.category LIKE :lk_4_{$idx}
                    OR co.industry LIKE :lk_5_{$idx}
                    OR t.name LIKE :lk_7_{$idx}
                )";
                $whereParams[":ft_a{$idx}"] = $whereParams[":ft_b{$idx}"] = $whereParams[":ft_c{$idx}"] = $booleanTerm;
                $whereParams[":lk_3_{$idx}"] = $whereParams[":lk_4_{$idx}"] = $whereParams[":lk_5_{$idx}"] = $likeTerm;
                $whereParams[":lk_7_{$idx}"] = $likeTerm;

                // Relevance score: 3 points per secondary term match
                $relevanceExprs[] = "MAX(CASE WHEN ps.name LIKE :rs_ps_{$idx} OR ps.category LIKE :rs_pc_{$idx} OR co.industry LIKE :rs_in_{$idx} OR t.name LIKE :rs_tg_{$idx} THEN 3 ELSE 0 END)";
                $relevanceParams[":rs_ps_{$idx}"] = $likeTerm;
                $relevanceParams[":rs_pc_{$idx}"] = $likeTerm;
                $relevanceParams[":rs_in_{$idx}"] = $likeTerm;
                $relevanceParams[":rs_tg_{$idx}"] = $likeTerm;
            }

            $conditions[] = "(" . implode("\n                OR ", $termConditions) . ")";

            // ── Build EXCLUDE conditions ──────────────────────────────────────
            foreach ($excludeTerms as $j => $exTerm) {
                $exLike = '%' . $exTerm . '%';
                $conditions[] = "(
                    co.name NOT LIKE :ex_co_{$j}
                    AND COALESCE(ps.name,'') NOT LIKE :ex_ps_{$j}
                    AND COALESCE(t.name,'') NOT LIKE :ex_tg_{$j}
                    AND COALESCE(co.industry,'') NOT LIKE :ex_in_{$j}
                    AND COALESCE(co.notes,'') NOT LIKE :ex_nt_{$j}
                )";
                $whereParams[":ex_co_{$j}"] = $exLike;
                $whereParams[":ex_ps_{$j}"] = $exLike;
                $whereParams[":ex_tg_{$j}"] = $exLike;
                $whereParams[":ex_in_{$j}"] = $exLike;
                $whereParams[":ex_nt_{$j}"] = $exLike;
            }

            // Build the relevance score expression for ORDER BY
            $relevanceScore = !empty($relevanceExprs) ? implode(' + ', $relevanceExprs) : '0';
        } else {
            $relevanceScore = '0';
        }

        if ($userId) {
            // My Cards: only cards scanned from my account
            $conditions[] = "ct.added_by_user_id = :uid";
            $whereParams[':uid'] = $userId;
        } elseif ($scopeTeamId) {
            // My Team: all cards of my team (public or private_team only)
            $conditions[] = "ct.team_id = :scope_team_id AND ct.cards_visibility IN ('public', 'private_team')";
            $whereParams[':scope_team_id'] = $scopeTeamId;
        } else {
            // All Cards: all publicly available cards (or all if admin)
            if (!$isAdmin) {
                if ($currentTeamId !== null) {
                    $conditions[] = "(ct.cards_visibility = 'public' OR ct.added_by_user_id = :current_uid OR (ct.cards_visibility = 'private_team' AND ct.team_id = :current_team_id))";
                    $whereParams[':current_uid'] = $currentUserId;
                    $whereParams[':current_team_id'] = $currentTeamId;
                } else {
                    $conditions[] = "(ct.cards_visibility = 'public' OR ct.added_by_user_id = :current_uid)";
                    $whereParams[':current_uid'] = $currentUserId;
                }
            }
        }
        if ($deptId) {
            $conditions[] = "ct.added_by_department_id = :did";
            $whereParams[':did'] = $deptId;
        }
        if ($industry) {
            $conditions[] = "co.industry = :industry";
            $whereParams[':industry'] = $industry;
        }
        if ($city) {
            $conditions[] = "co.city = :city";
            $whereParams[':city'] = $city;
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        // Count total results (Only bind where conditions to avoid HY093 parameter mismatch)
        $countSql = "
            SELECT COUNT(DISTINCT ct.id)
            FROM contacts ct
            LEFT JOIN companies co ON ct.company_id = co.id
            LEFT JOIN users u_owner ON ct.added_by_user_id = u_owner.id
            LEFT JOIN company_products cp ON co.id = cp.company_id
            LEFT JOIN products_services ps ON cp.product_service_id = ps.id
            LEFT JOIN contact_tags ctg ON ct.id = ctg.contact_id
            LEFT JOIN tags t ON ctg.tag_id = t.id
            WHERE {$where}
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($whereParams);
        $total = (int)$countStmt->fetchColumn();

        // Get results — ordered by RELEVANCE SCORE first, then rating
        $sql = "
            SELECT ct.id, ct.name AS contact_name, ct.designation,
                   ct.phone_primary, ct.email_primary, ct.card_front_image,
                   ct.ai_confidence_score, ct.is_verified, ct.created_at,
                   ct.added_by_user_id,
                   ct.rating_count, ct.rating_avg, ct.rating_bayesian,
                   co.name AS company_name, co.industry, co.city AS company_city,
                   co.website, u_owner.name AS added_by_name, u_owner.cards_visibility AS user_default_visibility,
                   ct.cards_visibility,
                   d.name AS dept_name,
                   GROUP_CONCAT(DISTINCT ps.name ORDER BY ps.name SEPARATOR ', ') AS products_services,
                   ({$relevanceScore}) AS relevance_score
            FROM contacts ct
            LEFT JOIN companies co ON ct.company_id = co.id
            LEFT JOIN users u_owner ON ct.added_by_user_id = u_owner.id
            LEFT JOIN departments d ON ct.added_by_department_id = d.id
            LEFT JOIN company_products cp ON co.id = cp.company_id
            LEFT JOIN products_services ps ON cp.product_service_id = ps.id
            LEFT JOIN contact_tags ctg ON ct.id = ctg.contact_id
            LEFT JOIN tags t ON ctg.tag_id = t.id
            WHERE {$where}
            GROUP BY ct.id
            ORDER BY
                relevance_score DESC,
                ct.rating_bayesian DESC,
                ct.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge($whereParams, $relevanceParams));
        $results = $stmt->fetchAll();

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
            'query' => $query
        ];
    }

    /**
     * Get search suggestions (for autocomplete)
     */
    public static function suggestions(string $query): array {
        $db = Database::getConnection();
        $like = '%' . trim($query) . '%';

        // Search across products, companies, and contacts
        $stmt = $db->prepare("
            (SELECT name AS label, 'product' AS type FROM products_services WHERE name LIKE :q1 LIMIT 5)
            UNION
            (SELECT name AS label, 'company' AS type FROM companies WHERE name LIKE :q2 LIMIT 5)
            UNION
            (SELECT DISTINCT industry AS label, 'industry' AS type FROM companies WHERE industry LIKE :q3 AND industry != '' LIMIT 5)
            LIMIT 10
        ");
        $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
        return $stmt->fetchAll();
    }

    /**
     * Get available filter options
     */
    public static function getFilterOptions(): array {
        $db = Database::getConnection();

        $industries = $db->query("SELECT DISTINCT industry FROM companies WHERE industry != '' ORDER BY industry")->fetchAll(PDO::FETCH_COLUMN);
        $cities = $db->query("SELECT DISTINCT city FROM companies WHERE city != '' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
        $departments = $db->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();

        return [
            'industries' => $industries,
            'cities' => $cities,
            'departments' => $departments
        ];
    }
}
