<?php
/**
 * Cookie-based Auth Token
 * Stateless authentication that works across multiple server instances.
 * The signed cookie replaces the need for server-side session storage for auth.
 */
class Auth {
    private const COOKIE_NAME = 'cv_auth';
    private const COOKIE_LIFE = 28800; // 8 hours

    /**
     * Issue a signed auth cookie after successful login.
     */
    public static function setCookie(array $user): void {
        $payload = base64_encode(json_encode([
            'uid'  => (int)$user['id'],
            'name' => $user['name'],
            'email'=> $user['email'],
            'role' => $user['role'],
            'dep'  => (int)($user['department_id'] ?? 0),
            'depn' => $user['department_name'] ?? '',
            'team' => !empty($user['team_id']) ? (int)$user['team_id'] : null,
            'exp'  => time() + self::COOKIE_LIFE,
        ]));

        $sig   = hash_hmac('sha256', $payload, self::secret());
        $token = $payload . '.' . $sig;

        setcookie(self::COOKIE_NAME, $token, [
            'expires'  => time() + self::COOKIE_LIFE,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Verify the auth cookie and return user data, or null if invalid/missing.
     */
    public static function verify(): ?array {
        $token = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (empty($token) || !str_contains($token, '.')) {
            return null;
        }

        [$payload, $sig] = explode('.', $token, 2);

        // Verify signature
        $expected = hash_hmac('sha256', $payload, self::secret());
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        // Decode and check expiry
        $data = json_decode(base64_decode($payload), true);
        if (!$data || ($data['exp'] ?? 0) < time()) {
            return null;
        }

        return $data;
    }

    /**
     * Clear the auth cookie (logout).
     */
    public static function clearCookie(): void {
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Get the signing secret from env or fallback default.
     */
    private static function secret(): string {
        return $_ENV['APP_SECRET'] ?? 'cardvault_s3cr3t_2024_#kJ9';
    }
}
