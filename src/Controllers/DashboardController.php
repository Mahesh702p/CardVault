<?php
/**
 * Dashboard Controller
 */

class DashboardController {
    /**
     * Show dashboard
     */
    public static function index(): void {
        $user = AuthMiddleware::user();
        $isAdmin = ($user['role'] === 'admin');
        $stats = Contact::getStats($user['id'], $user['department_id'], $user['id'], $isAdmin, $user['team_id'] ?? null);
        $filterOptions = SearchService::getFilterOptions();
        
        $view = 'dashboard/index';
        $pageTitle = 'Dashboard';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    /**
     * API endpoint for dashboard stats (AJAX)
     */
    public static function apiStats(): void {
        $user = AuthMiddleware::user();
        $isAdmin = ($user['role'] === 'admin');
        $stats = Contact::getStats($user['id'], $user['department_id'], $user['id'], $isAdmin, $user['team_id'] ?? null);
        Response::json($stats);
    }
}
