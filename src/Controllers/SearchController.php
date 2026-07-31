<?php
/**
 * Search Controller
 */

class SearchController {
    /**
     * Handle search requests
     */
    public static function search(): void {
        $user = AuthMiddleware::user();
        $_SESSION['last_cards_list_url'] = $_SERVER['REQUEST_URI'];
        $query = trim($_GET['q'] ?? '');
        $scope = $_GET['scope'] ?? 'all';
        $industry = $_GET['industry'] ?? null;
        $city = $_GET['city'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $userId = null;
        $deptId = null;
        $scopeTeamId = null;

        if ($scope === 'mine') {
            $userId = $user['id'];
        } elseif ($scope === 'team' && !empty($user['team_id'])) {
            $scopeTeamId = $user['team_id'];
        }

        $isAdmin = ($user['role'] === 'admin');
        $result = SearchService::search(
            $query, $userId, $deptId, $industry, $city, $page,
            ITEMS_PER_PAGE, $user['id'], $isAdmin,
            $user['team_id'] ?? null, $scopeTeamId
        );
        $filterOptions = SearchService::getFilterOptions();

        // Log search for analytics
        if (!empty($query)) {
            AuditLog::log('search', 'search', 0, [], ['query' => $query, 'results' => $result['total']]);
        }

        $view = 'search/results';
        $pageTitle = $query ? "Search: {$query}" : 'Search';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    /**
     * Autocomplete suggestions (AJAX)
     */
    public static function suggestions(): void {
        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            Response::json([]);
            return;
        }
        $suggestions = SearchService::suggestions($query);
        Response::json($suggestions);
    }
}
