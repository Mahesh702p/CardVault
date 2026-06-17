<?php
/**
 * CSRF Protection Helper
 *
 * On multi-instance deployments (like Cloudways), the session isn't reliable
 * across requests. This implementation derives the CSRF token from the signed
 * auth cookie (which is always present after login), making it fully stateless.
 *
 * The token is: HMAC-SHA256(user_id|expiry_day, APP_SECRET)
 * - Unique per user
 * - Changes daily (expiry-day rotates every 8 hours with the auth token)
 * - Works identically on ALL server instances — no shared state needed
 */

class CSRF {
    /**
     * Generate (or retrieve) the CSRF token for the current user.
     */
    public static function generate(): string {
        // Fast path: already in session
        if (!empty($_SESSION[CSRF_TOKEN_NAME])) {
            return $_SESSION[CSRF_TOKEN_NAME];
        }

        // Derive from auth cookie — stateless, works across all server instances
        $token = self::tokenFromAuthCookie();

        if ($token !== null) {
            $_SESSION[CSRF_TOKEN_NAME] = $token;
            return $token;
        }

        // Fallback for unauthenticated pages (login form etc.)
        $token = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME] = $token;
        return $token;
    }

    /**
     * Output a hidden input field with the CSRF token.
     */
    public static function field(): string {
        $token = self::generate();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Validate the CSRF token from a POST request.
     * Accepts if form token matches session token OR the auth-cookie-derived token.
     */
    public static function validate(): bool {
        $formToken = $_POST[CSRF_TOKEN_NAME] ?? '';

        if (empty($formToken)) {
            return false;
        }

        // Check session token
        $sessionToken = $_SESSION[CSRF_TOKEN_NAME] ?? '';
        if (!empty($sessionToken) && hash_equals($sessionToken, $formToken)) {
            return true;
        }

        // Check auth-cookie-derived token (works across server instances)
        $cookieDerivedToken = self::tokenFromAuthCookie();
        if ($cookieDerivedToken !== null && hash_equals($cookieDerivedToken, $formToken)) {
            return true;
        }

        return false;
    }

    /**
     * Derive a deterministic CSRF token from the signed auth cookie.
     * Returns null if no valid auth cookie exists.
     */
    private static function tokenFromAuthCookie(): ?string {
        $data = Auth::verify();
        if (!$data) {
            return null;
        }
        // Token is unique per user + changes with each auth cookie issuance (expiry changes)
        $secret = $_ENV['APP_SECRET'] ?? 'cardvault_s3cr3t_2024_#kJ9';
        return hash_hmac('sha256', $data['uid'] . '|' . $data['exp'], $secret);
    }
}
