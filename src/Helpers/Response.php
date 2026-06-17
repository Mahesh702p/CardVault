<?php
/**
 * JSON Response Helper
 */

class Response {
    /**
     * Send JSON response
     */
    public static function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to a URL
     */
    public static function redirect(string $path, array $flash = []): void {
        if (!empty($flash)) {
            $_SESSION['flash'] = $flash;
        }
        header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
        exit;
    }

    /**
     * Get and clear flash message
     */
    public static function flash(): ?array {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    /**
     * Render a view with data
     */
    public static function view(string $view, array $data = []): void {
        extract($data);
        $flash = self::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    /**
     * Render a view without the main layout (for modals, partials)
     */
    public static function partial(string $view, array $data = []): void {
        extract($data);
        include VIEW_PATH . '/' . $view . '.php';
    }
}
