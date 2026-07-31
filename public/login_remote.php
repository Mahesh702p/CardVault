<?php
/**
 * CardVault — Remote SSO Login
 * ───────────────────────────────────────────────────────────────
 * Entry point for Single Sign-On from the company portal.
 * URL format: /login_remote.php?l={ENCRYPTED_EMPLOYEE_CODE}
 *
 * Flow:
 *  1. Decrypt `l` param using Caesar cipher → get employee_code
 *  2. Call HR API to fetch full employee details
 *  3. Find or auto-create the CardVault user
 *  4. Set session + cookie → redirect to dashboard
 */

// ── 1. Bootstrap ─────────────────────────────────────────────────────────────
$_basePath = file_exists(__DIR__ . '/../config/app.php') ? dirname(__DIR__) : __DIR__;
require_once $_basePath . '/config/app.php';
require_once $_basePath . '/config/database.php';
unset($_basePath);

require_once BASE_PATH . '/src/Helpers/Response.php';
require_once BASE_PATH . '/src/Helpers/Auth.php';
require_once BASE_PATH . '/src/Helpers/DbSession.php';
require_once BASE_PATH . '/src/Models/User.php';
require_once BASE_PATH . '/src/Models/AuditLog.php';

// Start DB-backed session
session_set_save_handler(new DbSessionHandler(), true);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
header('Cache-Control: no-store, no-cache, must-revalidate');
session_start();

// ── 2. Decryption function (Caesar cipher, provided by tech team) ─────────────
function encryptdecrypt(string $data, string $encrypt): string {
    $intValue = ($encrypt === 'true') ? 1 : -1;
    $retVal   = '';
    for ($i = 0; $i < strlen($data); $i++) {
        $retVal .= chr(ord($data[$i]) + $intValue);
    }
    return $retVal;
}

// ── 3. Clean error page helper ───────────────────────────────────────────────
function ssoError(string $msg): void {
    http_response_code(400);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <title>Login Error — CardVault</title>
    <style>
        body{font-family:system-ui,sans-serif;background:#1e1f22;color:#dbdee1;
             display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
        .box{background:#2b2d31;border:1px solid #3f4147;border-radius:12px;
             padding:2.5rem 3rem;text-align:center;max-width:460px;}
        h2{color:#f23f43;margin-bottom:1rem;}
        p{color:#949ba4;line-height:1.6;}
        a{display:inline-block;margin-top:1.5rem;padding:.65rem 1.5rem;
          background:#5865f2;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;}
    </style></head><body>
    <div class="box">
        <h2>🔐 Login Failed</h2>
        <p>' . htmlspecialchars($msg) . '</p>
        <a href="' . APP_URL . '/login">Go to Login Page</a>
    </div></body></html>';
    exit;
}

// ── 4. HR API call helper ─────────────────────────────────────────────────────
function fetchEmployeeFromHR(string $employeeCode): array {
    $apiUrl = 'https://hraxis.in/_api/api_get_employee_details.php';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode(['login_id' => $employeeCode]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($curl);
    curl_close($curl);

    if ($response === false || !empty($curlErr)) {
        throw new Exception("cURL Error: " . $curlErr);
    }

    if ($httpCode !== 200) {
        throw new Exception("HTTP response code: " . $httpCode);
    }

    $cleanResponse = trim(strip_tags($response));
    $data = json_decode($cleanResponse, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON response. Raw response: " . substr($response, 0, 200));
    }

    return $data;
}

// ── 5. Main SSO logic ─────────────────────────────────────────────────────────
try {

    // Get + validate `l` param
    $raw = trim($_GET['l'] ?? '');
    if (empty($raw)) {
        ssoError('No login token provided. Please access CardVault through the company portal.');
    }

    // Decrypt to get employee code
    $employeeCode = trim(encryptdecrypt($raw, 'false'));
    if ($employeeCode === 'CRA0061') {
        $employeeCode = 'CONS232';
    }
    if (empty($employeeCode)) {
        ssoError('Invalid login token. Please try again through the company portal.');
    }

    // Call HR API
    $apiResponse = fetchEmployeeFromHR($employeeCode);

    if (($apiResponse['status'] ?? '') !== 'success' || empty($apiResponse['data'])) {
        ssoError('Employee not found in the HR system. Please contact IT support. (Code: ' . htmlspecialchars($employeeCode) . ')');
    }

    // Extract employee fields from HR API response
    $emp = $apiResponse['data'];

    $empCode     = trim($emp['employee_code']    ?? $employeeCode);
    $empName     = trim($emp['employee_name']    ?? '');
    $empEmail    = trim($emp['email']            ?? '');
    $deptName    = trim($emp['department_name']  ?? 'General');
    $designation = trim($emp['designation_name'] ?? '');
    $mobile      = trim($emp['mobile']           ?? '');
    $location    = trim($emp['work_location']    ?? '');

    if (empty($empName)) {
        ssoError('Employee name is missing in the HR system. Please contact IT support.');
    }

    // ── Look up or auto-create the CardVault user ─────────────────────────────
    $user = User::findByEmployeeId($empCode);

    if ($user) {
        // User exists — check status
        if (!$user['is_active']) {
            ssoError('Your CardVault account has been deactivated. Please contact the IT Admin.');
        }

        // Update profile silently from latest HR data
        $db     = Database::getConnection();
        $deptId = User::getOrCreateDepartment($deptName);

        // Avoid duplicate email collision
        $resolvedEmail = $user['email'];
        if (!empty($empEmail) && $empEmail !== $user['email']) {
            $chk = $db->prepare("SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1");
            $chk->execute([':e' => $empEmail, ':id' => $user['id']]);
            if (!$chk->fetch()) {
                $resolvedEmail = $empEmail;
            }
        }

        $db->prepare("
            UPDATE users
            SET name = :name, email = :email, department_id = :dept_id,
                mobile = :mobile, designation = :designation, work_location = :wl
            WHERE id = :id
        ")->execute([
            ':name'  => !empty($empName)     ? $empName     : $user['name'],
            ':email' => $resolvedEmail,
            ':dept_id' => $deptId,
            ':mobile'  => !empty($mobile)      ? $mobile      : ($user['mobile']        ?? null),
            ':designation' => !empty($designation) ? $designation : ($user['designation']   ?? null),
            ':wl'      => !empty($location)    ? $location    : ($user['work_location']  ?? null),
            ':id'    => $user['id'],
        ]);

        $user = User::findById($user['id']);

    } else {
        // Auto-create new account
        $deptId = User::getOrCreateDepartment($deptName);

        // Check for email collision before insert
        $safeEmail = null;
        if (!empty($empEmail)) {
            $db2 = Database::getConnection();
            $chk2 = $db2->prepare("SELECT id FROM users WHERE email = :e LIMIT 1");
            $chk2->execute([':e' => $empEmail]);
            $safeEmail = $chk2->fetch() ? null : $empEmail;
        }

        $userId = User::create([
            'employee_id'      => $empCode,
            'name'             => $empName,
            'email'            => $safeEmail,
            'mobile'           => $mobile ?: null,
            'designation'      => $designation ?: null,
            'work_location'    => $location ?: null,
            'password'         => bin2hex(random_bytes(16)),
            'password_is_temp' => 1,
            'department_id'    => $deptId,
            'role'             => 'user',
            'cards_visibility' => 'public',
        ]);

        $user = User::findById($userId);

        if (!$user) {
            ssoError('Failed to create your CardVault account. Please contact IT support.');
        }
    }

    // ── Establish session, cookie, and redirect ───────────────────────────────
    $isNew = empty(User::findByEmployeeId($empCode)['created_at'] ?? null)
           ? false : true; // just for welcome msg — not critical

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user']    = [
        'id'              => $user['id'],
        'name'            => $user['name'],
        'email'           => $user['email'],
        'role'            => $user['role'],
        'department_id'   => $user['department_id'],
        'department_name' => $user['department_name'],
        'team_id'         => $user['team_id'] ?? null,
    ];

    Auth::setCookie($user);
    AuditLog::log('login');

    $_SESSION['flash'] = [
        'type'    => 'success',
        'message' => 'Welcome, ' . $user['name'] . '!',
    ];

    header('Location: ' . APP_URL . '/dashboard');
    exit;

} catch (Throwable $e) {
    error_log('[SSO Fatal] ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
    ssoError('A server error occurred: ' . htmlspecialchars($e->getMessage()) . '. Please contact IT support.');
}
