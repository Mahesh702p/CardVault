<?php
/**
 * Authentication Middleware
 */

class AuthMiddleware {
    /**
     * Check if user is authenticated, redirect to login if not
     */
    public static function check(): void {
        if (!self::isLoggedIn()) {
            Response::redirect('login');
        }
    }

    /**
     * Check if user is logged in — tries session first, then signed cookie.
     * Cookie fallback makes auth work across multi-instance Cloudways setups.
     */
    public static function isLoggedIn(): bool {
        // Fast path: session already populated
        if (!empty($_SESSION['user_id'])) {
            return true;
        }

        // Fallback: verify signed auth cookie and repopulate session
        $data = Auth::verify();
        if ($data) {
            $_SESSION['user_id'] = $data['uid'];
            $_SESSION['user']    = [
                'id'              => $data['uid'],
                'name'            => $data['name'],
                'email'           => $data['email'],
                'role'            => $data['role'],
                'department_id'   => $data['dep'],
                'department_name' => $data['depn'],
            ];
            return true;
        }

        return false;
    }

    /**
     * Get current user ID
     */
    public static function userId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user data
     */
    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Check if user has a specific role
     */
    public static function hasRole(string $role): bool {
        return ($_SESSION['user']['role'] ?? '') === $role;
    }

    /**
     * Require admin role
     */
    public static function requireAdmin(): void {
        if (!self::hasRole('admin')) {
            http_response_code(403);
            die('Access denied. Admin privileges required.');
        }
    }
}
