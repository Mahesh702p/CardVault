<?php
/**
 * CardVault — Front Controller
 * All requests route through this file
 */

// ── 1. Detect base path ────────────────────────────────────────────────────────
// Locally: config/ is one level above public/
// Production (Cloudways): config/ is in the same directory as index.php
$_basePath = file_exists(__DIR__ . '/../config/app.php') ? dirname(__DIR__) : __DIR__;

// ── 2. Load config + database FIRST (needed for DB session handler) ────────────
require_once $_basePath . '/config/app.php';
require_once $_basePath . '/config/database.php';
unset($_basePath);

// ── 3. Serve static files directly if using PHP built-in server ───────────────
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($filePath)) {
        return false;
    }
}

// ── 4. Load helpers ────────────────────────────────────────────────────────────
require_once BASE_PATH . '/src/Helpers/Response.php';
require_once BASE_PATH . '/src/Helpers/Validator.php';
require_once BASE_PATH . '/src/Helpers/CSRF.php';
require_once BASE_PATH . '/src/Helpers/Auth.php';
require_once BASE_PATH . '/src/Helpers/DbSession.php';
require_once BASE_PATH . '/src/Helpers/Pagination.php';

// ── 5. Start session using DB handler (shared across all server instances) ─────
session_set_save_handler(new DbSessionHandler(), true);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
// Only set secure if actually on HTTPS
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
header('Cache-Control: no-store, no-cache, must-revalidate');
session_start();

// ── 6. Load remaining services, models, controllers ───────────────────────────
require_once BASE_PATH . '/src/Services/AIService.php';
require_once BASE_PATH . '/src/Services/SearchService.php';
require_once BASE_PATH . '/src/Services/ImageService.php';
require_once BASE_PATH . '/src/Services/MailService.php';

require_once BASE_PATH . '/src/Models/User.php';
require_once BASE_PATH . '/src/Models/Contact.php';
require_once BASE_PATH . '/src/Models/Company.php';
require_once BASE_PATH . '/src/Models/ProductService.php';
require_once BASE_PATH . '/src/Models/AuditLog.php';
require_once BASE_PATH . '/src/Models/Rating.php';
require_once BASE_PATH . '/src/Models/Team.php';

require_once BASE_PATH . '/src/Controllers/AuthController.php';
require_once BASE_PATH . '/src/Controllers/DashboardController.php';
require_once BASE_PATH . '/src/Controllers/CardController.php';
require_once BASE_PATH . '/src/Controllers/SearchController.php';
require_once BASE_PATH . '/src/Controllers/UserController.php';
require_once BASE_PATH . '/src/Controllers/TeamController.php';

require_once BASE_PATH . '/src/Middleware/AuthMiddleware.php';

// Ensure upload directory exists
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}


// Parse route
$route = $_GET['route'] ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = trim($route, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Public routes (no auth required)
$publicRoutes = ['login', 'auth/login', '', 'register', 'auth/register'];

// VCF download routes self-authenticate via a one-time DB token — skip global auth gate
$isTokenVcf = in_array($route, ['my-cards/vcf', 'my-team/vcf']) && !empty($_GET['_t']);

// Check authentication for protected routes
if (!$isTokenVcf && !in_array($route, $publicRoutes)) {
    AuthMiddleware::check();
}

// Route handling
switch ($route) {
    // === AUTH ===
    case '':
    case 'login':
        if (AuthMiddleware::isLoggedIn()) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
        AuthController::loginPage();
        break;

    case 'auth/login':
        if ($method === 'POST') {
            AuthController::login();
        }
        break;

    case 'register':
        if (AuthMiddleware::isLoggedIn()) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
        AuthController::registerPage();
        break;

    case 'auth/register':
        if ($method === 'POST') {
            AuthController::register();
        }
        break;

    case 'auth/logout':
        AuthController::logout();
        break;

    // === DASHBOARD ===
    case 'dashboard':
        DashboardController::index();
        break;

    // === CARDS ===
    case 'cards':
        CardController::list();
        break;

    case 'cards/upload':
        if ($method === 'GET') {
            CardController::uploadPage();
        }
        break;

    case 'cards/scan':
        if ($method === 'POST') {
            CardController::scan();
        }
        break;

    case 'cards/save':
        if ($method === 'POST') {
            CardController::save();
        }
        break;

    case 'cards/cleanup-temp':
        if ($method === 'POST') {
            CardController::cleanupTemp();
        }
        break;



    case (preg_match('/^cards\/(\d+)$/', $route, $m) ? true : false):
        CardController::detail((int)$m[1]);
        break;

    case (preg_match('/^cards\/(\d+)\/edit$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            CardController::update((int)$m[1]);
        } else {
            CardController::editPage((int)$m[1]);
        }
        break;

    case (preg_match('/^cards\/(\d+)\/delete$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            CardController::delete((int)$m[1]);
        }
        break;

    case (preg_match('/^cards\/(\d+)\/verify$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            CardController::verify((int)$m[1]);
        }
        break;

    case (preg_match('/^cards\/(\d+)\/vcard$/', $route, $m) ? true : false):
        CardController::vcard((int)$m[1]);
        break;

    case (preg_match('/^cards\/(\d+)\/rate$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            CardController::rate((int)$m[1]);
        }
        break;

    // === SEARCH ===
    case 'search':
        SearchController::search();
        break;

    case 'search/suggestions':
        SearchController::suggestions();
        break;

    // === USERS (Admin only) ===
    case 'users':
        UserController::list();
        break;

    case 'users/create':
        if ($method === 'POST') {
            UserController::create();
        } else {
            UserController::createPage();
        }
        break;

    case (preg_match('/^users\/(\d+)\/edit$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            UserController::update((int)$m[1]);
        } else {
            UserController::editPage((int)$m[1]);
        }
        break;

    case (preg_match('/^users\/(\d+)\/reset-password$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            UserController::resetPassword((int)$m[1]);
        }
        break;

    case (preg_match('/^users\/(\d+)\/deactivate$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            UserController::deactivate((int)$m[1]);
        }
        break;

    case (preg_match('/^users\/(\d+)\/activate$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            UserController::activate((int)$m[1]);
        }
        break;

    case 'cards/export':
        UserController::exportCSV();
        break;

    case 'my-cards/export':
        UserController::exportMyCSV();
        break;

    case 'my-cards/vcf':
        UserController::exportMyVCF();
        break;

    case 'my-team/export':
        UserController::exportTeamCSV();
        break;

    case 'my-team/vcf':
        UserController::exportTeamVCF();
        break;


    case 'profile/password':
        if ($method === 'POST') {
            UserController::changePassword();
        } else {
            UserController::changePasswordPage();
        }
        break;

    case 'profile':
        if ($method === 'POST') {
            UserController::updateVisibility();
        } else {
            UserController::profilePage();
        }
        break;

    case 'export':
        UserController::exportPage();
        break;

    case 'help':
        $view = 'help/index';
        $pageTitle = 'Support & Help';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
        break;

    // === TEAMS ===
    case 'admin/teams':
        TeamController::adminList();
        break;

    case 'admin/audit-logs':
        UserController::auditLogs();
        break;

    case (preg_match('/^admin\/teams\/disband\/(\d+)$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            TeamController::adminDisband((int)$m[1]);
        }
        break;

    case 'team':
        TeamController::indexPage();
        break;

    case 'team/create':
        if ($method === 'POST') {
            TeamController::create();
        }
        break;

    case 'team/join':
        if ($method === 'POST') {
            TeamController::join();
        }
        break;

    case 'team/leave':
        if ($method === 'POST') {
            TeamController::leave();
        }
        break;

    case 'team/disband':
        if ($method === 'POST') {
            TeamController::disband();
        }
        break;

    case 'team/change-password':
        if ($method === 'POST') {
            TeamController::changePassword();
        }
        break;

    case (preg_match('/^team\/remove-member\/(\d+)$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            TeamController::removeMember((int)$m[1]);
        }
        break;

    case (preg_match('/^team\/make-admin\/(\d+)$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            TeamController::makeAdmin((int)$m[1]);
        }
        break;

    case 'team/invite-member':
        if ($method === 'POST') {
            TeamController::inviteMember();
        }
        break;

    case (preg_match('/^team\/accept-invite\/(\d+)$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            TeamController::acceptInvite((int)$m[1]);
        }
        break;

    case (preg_match('/^team\/decline-invite\/(\d+)$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            TeamController::declineInvite((int)$m[1]);
        }
        break;

    case (preg_match('/^team\/cancel-invite\/(\d+)$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            TeamController::cancelInvite((int)$m[1]);
        }
        break;

    case 'team/update-details':
        if ($method === 'POST') {
            TeamController::updateDetails();
        }
        break;

    case (preg_match('/^team\/toggle-admin\/(\d+)$/', $route, $m) ? true : false):
        if ($method === 'POST') {
            TeamController::toggleAdminStatus((int)$m[1]);
        }
        break;

    // === API ENDPOINTS ===
    case 'api/stats':
        DashboardController::apiStats();
        break;

    default:
        http_response_code(404);
        include VIEW_PATH . '/errors/404.php';
        break;
}
