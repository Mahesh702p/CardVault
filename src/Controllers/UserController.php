<?php
/**
 * User Controller (Admin + self-service)
 */

class UserController {

    // ─── LIST ────────────────────────────────────────────────────────────────
    public static function list(): void {
        AuthMiddleware::requireAdmin();
        $_SESSION['last_users_list_url'] = $_SERVER['REQUEST_URI'];
        $users = User::all();
        $view  = 'users/list';
        $pageTitle = 'Manage Users';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    // ─── CREATE ──────────────────────────────────────────────────────────────
    public static function createPage(): void {
        AuthMiddleware::requireAdmin();
        $departments = User::getDepartments();
        $view  = 'users/create';
        $pageTitle = 'Add User';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    public static function create(): void {
        AuthMiddleware::requireAdmin();
        if (!CSRF::validate()) {
            Response::redirect('users/create', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $validator = new Validator();
        $validator->required('name',        $_POST['name']        ?? '', 'Name')
                  ->required('employee_id', $_POST['employee_id'] ?? '', 'Employee ID')
                  ->required('password',    $_POST['password']    ?? '', 'Password')
                  ->minLength('password',   $_POST['password']    ?? '', 6, 'Password')
                  ->required('department_name', $_POST['department_name'] ?? '', 'Department');

        // Email is optional, but must be valid if provided
        if (!empty($_POST['email'])) {
            $validator->email('email', $_POST['email'], 'Email');
        }

        if (!$validator->passes()) {
            Response::redirect('users/create', ['type' => 'error', 'message' => implode(' ', $validator->errors())]);
            return;
        }

        // Check Employee ID uniqueness
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE employee_id = :emp_id AND is_active = 1");
        $stmt->execute([':emp_id' => $_POST['employee_id']]);
        if ((int)$stmt->fetchColumn() > 0) {
            Response::redirect('users/create', ['type' => 'error', 'message' => 'Employee ID is already in use.']);
            return;
        }

        try {
            $departmentId = User::getOrCreateDepartment($_POST['department_name'] ?? '');

            $userId = User::create([
                'employee_id'   => trim($_POST['employee_id']),
                'name'          => $_POST['name'],
                'email'         => !empty($_POST['email']) ? trim($_POST['email']) : null,
                'mobile'        => !empty($_POST['mobile']) ? trim($_POST['mobile']) : null,
                'designation'   => !empty($_POST['designation']) ? trim($_POST['designation']) : null,
                'work_location' => !empty($_POST['work_location']) ? trim($_POST['work_location']) : null,
                'password'      => $_POST['password'],
                'department_id' => $departmentId,
                'role'          => $_POST['role'] ?? 'user'
            ]);
            AuditLog::log('create', 'user', $userId);
            $redirectUrl = $_SESSION['last_users_list_url'] ?? 'users';
            Response::redirect($redirectUrl, ['type' => 'success', 'message' => 'User created successfully!']);
        } catch (Exception $e) {
            Response::redirect('users/create', ['type' => 'error', 'message' => 'Failed to create user: ' . $e->getMessage()]);
        }
    }

    // ─── EDIT ────────────────────────────────────────────────────────────────
    public static function editPage(int $id): void {
        AuthMiddleware::requireAdmin();
        $editUser    = User::findById($id);
        if (!$editUser) {
            Response::redirect('users', ['type' => 'error', 'message' => 'User not found.']);
            return;
        }
        $departments = User::getDepartments();
        $view  = 'users/edit';
        $pageTitle = 'Edit User';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    public static function update(int $id): void {
        AuthMiddleware::requireAdmin();
        if (!CSRF::validate()) {
            Response::redirect("users/{$id}/edit", ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $validator = new Validator();
        $validator->required('name',        $_POST['name']        ?? '', 'Name')
                  ->required('employee_id', $_POST['employee_id'] ?? '', 'Employee ID')
                  ->required('department_name', $_POST['department_name'] ?? '', 'Department');

        // Email is optional, but must be valid if provided
        if (!empty($_POST['email'])) {
            $validator->email('email', $_POST['email'], 'Email');
        }

        if (!$validator->passes()) {
            Response::redirect("users/{$id}/edit", ['type' => 'error', 'message' => implode(' ', $validator->errors())]);
            return;
        }

        // Check Employee ID uniqueness (exclude self)
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE employee_id = :emp_id AND id != :id AND is_active = 1");
        $stmt->execute([':emp_id' => $_POST['employee_id'], ':id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            Response::redirect("users/{$id}/edit", ['type' => 'error', 'message' => 'Employee ID is already in use by another user.']);
            return;
        }

        try {
            $departmentId = User::getOrCreateDepartment($_POST['department_name'] ?? '');

            User::update($id, [
                'name'          => $_POST['name'],
                'email'         => !empty($_POST['email']) ? trim($_POST['email']) : null,
                'mobile'        => !empty($_POST['mobile']) ? trim($_POST['mobile']) : null,
                'designation'   => !empty($_POST['designation']) ? trim($_POST['designation']) : null,
                'work_location' => !empty($_POST['work_location']) ? trim($_POST['work_location']) : null,
                'department_id' => $departmentId,
                'role'          => $_POST['role'] ?? 'user',
                'employee_id'   => trim($_POST['employee_id'])
            ]);

            // If updating current user's profile, refresh session and auth cookie
            if ($id === AuthMiddleware::userId()) {
                $user = User::findById($id);
                if ($user) {
                    $_SESSION['user'] = [
                        'id'              => $user['id'],
                        'name'            => $user['name'],
                        'email'           => $user['email'],
                        'role'            => $user['role'],
                        'department_id'   => $user['department_id'],
                        'department_name' => $user['department_name']
                    ];
                    Auth::setCookie($user);
                }
            }

            AuditLog::log('update', 'user', $id);
            $redirectUrl = $_SESSION['last_users_list_url'] ?? 'users';
            Response::redirect($redirectUrl, ['type' => 'success', 'message' => 'User updated successfully!']);
        } catch (Exception $e) {
            Response::redirect("users/{$id}/edit", ['type' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
        }
    }

    // ─── RESET PASSWORD (Admin) ───────────────────────────────────────────────
    public static function resetPassword(int $id): void {
        AuthMiddleware::requireAdmin();
        if (!CSRF::validate()) {
            Response::redirect('users', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $newPassword    = $_POST['new_password']    ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 6) {
            Response::redirect("users/{$id}/edit", ['type' => 'error', 'message' => 'Password must be at least 6 characters.']);
            return;
        }
        if ($newPassword !== $confirmPassword) {
            Response::redirect("users/{$id}/edit", ['type' => 'error', 'message' => 'Passwords do not match.']);
            return;
        }

        User::resetPassword($id, $newPassword);
        AuditLog::log('update', 'user', $id, [], ['action' => 'password_reset']);
        $redirectUrl = $_SESSION['last_users_list_url'] ?? 'users';
        Response::redirect($redirectUrl, ['type' => 'success', 'message' => 'Password reset successfully!']);
    }

    // ─── DEACTIVATE (Admin) ───────────────────────────────────────────────────
    public static function deactivate(int $id): void {
        AuthMiddleware::requireAdmin();
        $redirectUrl = $_SESSION['last_users_list_url'] ?? 'users';
        if (!CSRF::validate()) {
            Response::redirect($redirectUrl, ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        if ($currentUser['id'] === $id) {
            Response::redirect($redirectUrl, ['type' => 'error', 'message' => 'You cannot deactivate your own account.']);
            return;
        }

        User::deactivate($id);
        AuditLog::log('delete', 'user', $id);
        Response::redirect($redirectUrl, ['type' => 'success', 'message' => 'User deactivated successfully.']);
     }

    // ─── ACTIVATE (Admin) ─────────────────────────────────────────────────────
    public static function activate(int $id): void {
        AuthMiddleware::requireAdmin();
        $redirectUrl = $_SESSION['last_users_list_url'] ?? 'users';
        if (!CSRF::validate()) {
            Response::redirect($redirectUrl, ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        User::activate($id);
        AuditLog::log('update', 'user', $id, [], ['action' => 'reactivate']);
        Response::redirect($redirectUrl, ['type' => 'success', 'message' => 'User reactivated successfully.']);
    }

    // ─── CHANGE OWN PASSWORD (Any logged-in user) ────────────────────────────
    public static function changePasswordPage(): void {
        $currentUser = AuthMiddleware::user();
        $userRow     = User::findById($currentUser['id']);
        $passwordIsTemp        = $userRow ? ((int)$userRow['password_is_temp'] === 1) : false;
        $passwordChangedByUser = $userRow ? ((bool)$userRow['password_changed_by_user']) : false;
        $needsPasswordSet      = $passwordIsTemp || !$passwordChangedByUser;

        $view  = 'users/change_password';
        $pageTitle = $needsPasswordSet ? 'Set Password' : 'Change Password';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    public static function changePassword(): void {
        if (!CSRF::validate()) {
            Response::redirect('profile/password', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $user                  = AuthMiddleware::user();
        $userRow               = User::findById($user['id']);
        $passwordIsTemp        = $userRow ? ((int)$userRow['password_is_temp'] === 1) : false;
        $passwordChangedByUser = $userRow ? ((bool)$userRow['password_changed_by_user']) : false;
        // Bypass old-password check if: temp password OR user has never consciously set their own password
        $needsPasswordSet      = $passwordIsTemp || !$passwordChangedByUser;

        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPass) {
            Response::redirect('profile/password', ['type' => 'error', 'message' => 'New passwords do not match.']);
            return;
        }
        if (strlen($newPassword) < 6) {
            Response::redirect('profile/password', ['type' => 'error', 'message' => 'New password must be at least 6 characters.']);
            return;
        }

        if ($needsPasswordSet) {
            // Bypass old password validation — user is setting their own password for the first time
            User::resetPassword($user['id'], $newPassword);
        } else {
            // Verify old password — user has already set their password before
            if (!User::changePassword($user['id'], $oldPassword, $newPassword)) {
                Response::redirect('profile/password', ['type' => 'error', 'message' => 'Current password is incorrect.']);
                return;
            }
        }

        // Permanently mark that this user has consciously set their own password.
        // This prevents the popup from ever appearing again, even if password_is_temp gets reset.
        User::markPasswordChanged($user['id']);

        Response::redirect('dashboard', ['type' => 'success', 'message' => 'Password set successfully!']);
    }

    // ─── PROFILE PAGE (Any logged-in user) ───────────────────────────────────
    public static function profilePage(): void {
        $currentUser = AuthMiddleware::user();
        // Fetch fresh user row — LEFT JOIN so users without a department still load
        $userRow = User::findById($currentUser['id']);
        // Last resort fallback: query without join if department is missing
        if (!$userRow) {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $currentUser['id']]);
            $userRow = $stmt->fetch() ?: [];
            $userRow['department_name'] = '';
        }
        $view = 'users/profile';
        $pageTitle = 'My Profile';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    // ─── EXPORT PAGE (Any logged-in user) ─────────────────────────────────────

    public static function exportPage(): void {
        $currentUser = AuthMiddleware::user();
        $userRow = User::findById($currentUser['id']);
        if (!$userRow) {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $currentUser['id']]);
            $userRow = $stmt->fetch() ?: [];
            $userRow['department_name'] = '';
        }
        $view = 'users/export';
        $pageTitle = 'Export My Data';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    // ─── UPDATE VISIBILITY (Any logged-in user) ───────────────────────────────
    public static function updateVisibility(): void {
        if (!CSRF::validate()) {
            Response::redirect('profile', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        $visibility = $_POST['cards_visibility'] ?? 'public';
        User::updateVisibility($currentUser['id'], $visibility);
        AuditLog::log('update', 'user', $currentUser['id'], [], ['cards_visibility' => $visibility]);
        Response::redirect('profile', ['type' => 'success', 'message' => 'Privacy setting updated successfully!']);
    }

    // ─── CSV EXPORT (Admin) ────────────────────────────────────────────────────

    public static function exportCSV(): void {
        AuthMiddleware::requireAdmin();

        $db   = Database::getConnection();
        $rows = $db->query("
            SELECT ct.name AS contact_name, ct.designation, ct.department_in_company,
                   ct.phone_primary, ct.phone_secondary, ct.email_primary, ct.email_secondary,
                   ct.linkedin_url, ct.is_verified, ct.created_at,
                   co.name AS company, co.website, co.industry, co.city, co.state,
                   co.address, co.gst_number,
                   GROUP_CONCAT(DISTINCT ps.name SEPARATOR '; ') AS products_services,
                   u.name AS added_by, d.name AS added_by_dept
            FROM contacts ct
            LEFT JOIN companies co ON ct.company_id = co.id
            LEFT JOIN users u ON ct.added_by_user_id = u.id
            LEFT JOIN departments d ON ct.added_by_department_id = d.id
            LEFT JOIN company_products cp ON co.id = cp.company_id
            LEFT JOIN products_services ps ON cp.product_service_id = ps.id
            WHERE ct.is_deleted = 0
            GROUP BY ct.id
            ORDER BY ct.created_at DESC
        ")->fetchAll();

        $filename = 'cardvault_export_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        // BOM for Excel UTF-8 compatibility
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'Name', 'Designation', 'Department (at company)', 'Phone', 'Phone (Alt)',
            'Email', 'Email (Alt)', 'LinkedIn', 'Verified',
            'Company', 'Website', 'Industry', 'City', 'State', 'Address', 'GST No.',
            'Products & Services', 'Added By', 'Added By Dept', 'Date Added'
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['contact_name'], $r['designation'], $r['department_in_company'],
                $r['phone_primary'], $r['phone_secondary'],
                $r['email_primary'], $r['email_secondary'], $r['linkedin_url'],
                $r['is_verified'] ? 'Yes' : 'No',
                $r['company'], $r['website'], $r['industry'],
                $r['city'], $r['state'], $r['address'], $r['gst_number'],
                $r['products_services'], $r['added_by'], $r['added_by_dept'],
                date('Y-m-d H:i', strtotime($r['created_at']))
            ]);
        }

        fclose($out);
        exit;
    }

    // ─── MY CARDS CSV EXPORT (Any logged-in user) ─────────────────────────────

    public static function exportMyCSV(): void {
        AuthMiddleware::check();
        $currentUser = AuthMiddleware::user();
        $userId      = $currentUser['id'];

        $since = $_GET['since'] ?? '';
        $time  = $_GET['time'] ?? '00:00';
        $sinceClause = '';
        $params = [':uid' => $userId];
        if (!empty($since) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
            if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
                $time = '00:00';
            }
            $sinceClause = " AND ct.created_at >= :since ";
            $params[':since'] = $since . ' ' . $time . ':00';
        }

        $db   = Database::getConnection();
        $stmt = $db->prepare("
            SELECT ct.name AS contact_name, ct.designation, ct.department_in_company,
                   ct.phone_primary, ct.phone_secondary,
                   ct.email_primary, ct.email_secondary,
                   ct.linkedin_url, ct.is_verified, ct.created_at,
                   co.name AS company, co.website, co.industry,
                   co.city, co.state, co.address
            FROM contacts ct
            LEFT JOIN companies co ON ct.company_id = co.id
            WHERE ct.added_by_user_id = :uid AND ct.is_deleted = 0 {$sinceClause}
            ORDER BY ct.created_at DESC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $safeName = preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($currentUser['name']));
        $filename = 'my_cards_' . $safeName . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel UTF-8

        fputcsv($out, [
            'Name', 'Designation', 'Department', 'Company',
            'Phone', 'Email',
            'Phone (Alt)', 'Email (Alt)',
            'Website', 'Industry',
            'Address', 'City', 'State',
            'Date Added', 'LinkedIn'
        ]);

        foreach ($rows as $r) {
            $phone1 = preg_replace('/[^0-9]/', '', $r['phone_primary'] ?? '');
            if (strlen($phone1) > 10) $phone1 = substr($phone1, -10);
            $phone2 = preg_replace('/[^0-9]/', '', $r['phone_secondary'] ?? '');
            if (strlen($phone2) > 10) $phone2 = substr($phone2, -10);

            fputcsv($out, [
                $r['contact_name'], $r['designation'], $r['department_in_company'], $r['company'],
                $phone1, $r['email_primary'],
                $phone2, $r['email_secondary'],
                $r['website'], $r['industry'],
                $r['address'], $r['city'], $r['state'],
                date('Y-m-d H:i', strtotime($r['created_at'])),
                $r['linkedin_url']
            ]);
        }

        fclose($out);
        AuditLog::log('export', 'contacts_csv', $userId);
        exit;
    }

    // ─── MY CARDS BULK vCARD EXPORT (Any logged-in user) ──────────────────────

    public static function exportMyVCF(): void {
        // Validate one-time DB token (iOS Safari download manager sends no session cookie)
        $urlToken = trim($_GET['_t'] ?? '');
        $userId   = null;
        if ($urlToken !== '') {
            $tdb  = Database::getConnection();
            $tdb->exec("CREATE TABLE IF NOT EXISTS `vcf_download_tokens` (`token` VARCHAR(64) NOT NULL, `user_id` INT UNSIGNED NOT NULL, `type` VARCHAR(16) NOT NULL, `expires_at` INT UNSIGNED NOT NULL, PRIMARY KEY (`token`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $tstmt = $tdb->prepare("SELECT user_id FROM vcf_download_tokens WHERE token = ? AND type = 'my' AND expires_at >= ?");
            $tstmt->execute([$urlToken, time()]);
            $row = $tstmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $userId = (int) $row['user_id'];
                $tdb->prepare("DELETE FROM vcf_download_tokens WHERE token = ?")->execute([$urlToken]);
            }
        }
        if ($userId === null) {
            AuthMiddleware::check();
            $userId = (int) AuthMiddleware::user()['id'];
        }

        $since = $_GET['since'] ?? '';
        $time  = $_GET['time'] ?? '00:00';
        $sinceClause = '';
        $params = [':uid' => $userId];
        if (!empty($since) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
            if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
                $time = '00:00';
            }
            $sinceClause = " AND ct.created_at >= :since ";
            $params[':since'] = $since . ' ' . $time . ':00';
        }

        $db   = Database::getConnection();
        $stmt = $db->prepare("
            SELECT ct.name, ct.designation,
                   ct.phone_primary, ct.phone_secondary,
                   ct.email_primary, ct.email_secondary,
                   ct.linkedin_url,
                   co.name AS company_name,
                   co.address AS company_address,
                   co.city AS company_city,
                   co.state AS company_state,
                   co.website
            FROM contacts ct
            LEFT JOIN companies co ON ct.company_id = co.id
            WHERE ct.added_by_user_id = :uid AND ct.is_deleted = 0 {$sinceClause}
            ORDER BY ct.name ASC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $userInfo = User::findById($userId);
        $safeName = preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($userInfo['name'] ?? 'user'));
        $filename = 'my_contacts_' . $safeName . '_' . date('Y-m-d') . '.vcf';

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        $vcf = '';
        foreach ($rows as $r) {
            $fullName = trim($r['name'] ?? '');
            if (empty($fullName)) continue;

            $parts     = explode(' ', $fullName, 2);
            $firstName = $parts[0] ?? '';
            $lastName  = $parts[1] ?? '';

            // Clean phone numbers to 10 digits
            $phone1 = preg_replace('/[^0-9]/', '', $r['phone_primary'] ?? '');
            if (strlen($phone1) > 10) $phone1 = substr($phone1, -10);

            $phone2 = preg_replace('/[^0-9]/', '', $r['phone_secondary'] ?? '');
            if (strlen($phone2) > 10) $phone2 = substr($phone2, -10);

            $vcf .= "BEGIN:VCARD\r\n";
            $vcf .= "VERSION:3.0\r\n";
            $vcf .= "PRODID:-//CardVault//EN\r\n";
            $vcf .= "FN:" . str_replace([";", "\n", "\r"], " ", $fullName) . "\r\n";
            $vcf .= "N:" . str_replace([";", "\n", "\r"], " ", $lastName) . ";" . str_replace([";", "\n", "\r"], " ", $firstName) . ";;;\r\n";

            if (!empty($r['company_name'])) {
                $vcf .= "ORG:" . str_replace([";", "\n", "\r"], " ", $r['company_name']) . "\r\n";
            }
            if (!empty($r['designation'])) {
                $vcf .= "TITLE:" . str_replace([";", "\n", "\r"], " ", $r['designation']) . "\r\n";
            }
            if (!empty($phone1)) {
                $vcf .= "TEL;TYPE=CELL:" . $phone1 . "\r\n";
            }
            if (!empty($phone2)) {
                $vcf .= "TEL;TYPE=WORK:" . $phone2 . "\r\n";
            }
            if (!empty($r['email_primary'])) {
                $vcf .= "EMAIL;TYPE=INTERNET:" . trim($r['email_primary']) . "\r\n";
            }
            if (!empty($r['email_secondary'])) {
                $vcf .= "EMAIL;TYPE=WORK:" . trim($r['email_secondary']) . "\r\n";
            }
            if (!empty($r['linkedin_url'])) {
                $vcf .= "URL:" . trim($r['linkedin_url']) . "\r\n";
            }
            if (!empty($r['website'])) {
                $vcf .= "URL;TYPE=WORK:" . trim($r['website']) . "\r\n";
            }

            $addr  = str_replace([";", "\n", "\r"], " ", $r['company_address'] ?? '');
            $city  = str_replace([";", "\n", "\r"], " ", $r['company_city'] ?? '');
            $state = str_replace([";", "\n", "\r"], " ", $r['company_state'] ?? '');

            if (!empty($addr) || !empty($city) || !empty($state)) {
                $vcf .= "ADR;TYPE=WORK:;;{$addr};{$city};{$state};;;India\r\n";
            }

            $vcf .= "END:VCARD\r\n";
        }

        echo $vcf;
        AuditLog::log('export', 'contacts_vcf', $userId);
        exit;
    }

    // ─── MY TEAM CARDS EXPORT (Any user who is in a team) ────────────────────

    public static function exportTeamCSV(): void {
        AuthMiddleware::check();
        $currentUser = AuthMiddleware::user();
        $userRow     = User::findById($currentUser['id']);

        if (empty($userRow['team_id'])) {
            Response::redirect('profile', ['type' => 'error', 'message' => 'You are not in a team.']);
            return;
        }

        $teamId = (int)$userRow['team_id'];
        $db     = Database::getConnection();

        // Fetch team name for the filename
        $teamStmt = $db->prepare("SELECT team_name FROM teams WHERE id = :id");
        $teamStmt->execute([':id' => $teamId]);
        $teamName = $teamStmt->fetchColumn() ?: 'team';

        $since = $_GET['since'] ?? '';
        $time  = $_GET['time'] ?? '00:00';
        $sinceClause = '';
        $params = [':team_id' => $teamId];
        if (!empty($since) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
            if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
                $time = '00:00';
            }
            $sinceClause = " AND ct.created_at >= :since ";
            $params[':since'] = $since . ' ' . $time . ':00';
        }

        $stmt = $db->prepare("
            SELECT ct.name AS contact_name, ct.designation, ct.department_in_company,
                   ct.phone_primary, ct.phone_secondary,
                   ct.email_primary, ct.email_secondary,
                   ct.linkedin_url, ct.is_verified, ct.created_at,
                   co.name AS company, co.website, co.industry,
                   co.city, co.state, co.address,
                   u.name AS added_by
            FROM contacts ct
            LEFT JOIN companies co ON ct.company_id = co.id
            LEFT JOIN users u ON ct.added_by_user_id = u.id
            WHERE ct.team_id = :team_id AND ct.is_deleted = 0 {$sinceClause}
            ORDER BY ct.created_at DESC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $safeTeam = preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($teamName));
        $filename = 'team_cards_' . $safeTeam . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel UTF-8

        fputcsv($out, [
            'Name', 'Designation', 'Department', 'Company',
            'Phone', 'Email',
            'Phone (Alt)', 'Email (Alt)',
            'Website', 'Industry',
            'Address', 'City', 'State',
            'Date Added', 'LinkedIn',
            'Added By'
        ]);

        foreach ($rows as $r) {
            $phone1 = preg_replace('/[^0-9]/', '', $r['phone_primary'] ?? '');
            if (strlen($phone1) > 10) $phone1 = substr($phone1, -10);
            $phone2 = preg_replace('/[^0-9]/', '', $r['phone_secondary'] ?? '');
            if (strlen($phone2) > 10) $phone2 = substr($phone2, -10);

            fputcsv($out, [
                $r['contact_name'], $r['designation'], $r['department_in_company'], $r['company'],
                $phone1, $r['email_primary'],
                $phone2, $r['email_secondary'],
                $r['website'], $r['industry'],
                $r['address'], $r['city'], $r['state'],
                date('Y-m-d H:i', strtotime($r['created_at'])),
                $r['linkedin_url'],
                $r['added_by']
            ]);
        }

        fclose($out);
        AuditLog::log('export', 'team_contacts_csv', $teamId);
        exit;
    }

    // ─── MY TEAM CARDS VCF EXPORT ────────────────────────────────────────────

    public static function exportTeamVCF(): void {
        // Validate one-time DB token (iOS Safari download manager sends no session cookie)
        $urlToken = trim($_GET['_t'] ?? '');
        $userRow  = null;
        if ($urlToken !== '') {
            $tdb  = Database::getConnection();
            $tdb->exec("CREATE TABLE IF NOT EXISTS `vcf_download_tokens` (`token` VARCHAR(64) NOT NULL, `user_id` INT UNSIGNED NOT NULL, `type` VARCHAR(16) NOT NULL, `expires_at` INT UNSIGNED NOT NULL, PRIMARY KEY (`token`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $tstmt = $tdb->prepare("SELECT user_id FROM vcf_download_tokens WHERE token = ? AND type = 'team' AND expires_at >= ?");
            $tstmt->execute([$urlToken, time()]);
            $row = $tstmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $userRow = User::findById((int) $row['user_id']);
                $tdb->prepare("DELETE FROM vcf_download_tokens WHERE token = ?")->execute([$urlToken]);
            }
        }
        if ($userRow === null) {
            AuthMiddleware::check();
            $userRow = User::findById((int) AuthMiddleware::user()['id']);
        }

        if (empty($userRow['team_id'])) {
            Response::redirect('profile', ['type' => 'error', 'message' => 'You are not in a team.']);
            return;
        }

        $teamId = (int)$userRow['team_id'];
        $db     = Database::getConnection();

        $teamStmt = $db->prepare("SELECT team_name FROM teams WHERE id = :id");
        $teamStmt->execute([':id' => $teamId]);
        $teamName = $teamStmt->fetchColumn() ?: 'team';

        $since = $_GET['since'] ?? '';
        $time  = $_GET['time']  ?? '00:00';
        $sinceClause = '';
        $params = [':team_id' => $teamId];
        if (!empty($since) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
            if (!preg_match('/^\d{2}:\d{2}$/', $time)) { $time = '00:00'; }
            $sinceClause = " AND ct.created_at >= :since ";
            $params[':since'] = $since . ' ' . $time . ':00';
        }

        $stmt = $db->prepare("
            SELECT ct.name, ct.designation,
                   ct.phone_primary, ct.phone_secondary,
                   ct.email_primary, ct.email_secondary,
                   ct.linkedin_url,
                   co.name AS company_name,
                   co.address AS company_address,
                   co.city AS company_city,
                   co.state AS company_state,
                   co.website
            FROM contacts ct
            LEFT JOIN companies co ON ct.company_id = co.id
            WHERE ct.team_id = :team_id AND ct.is_deleted = 0 {$sinceClause}
            ORDER BY ct.name ASC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $safeTeam = preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($teamName));
        $filename = 'team_contacts_' . $safeTeam . '_' . date('Y-m-d') . '.vcf';

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        $vcf = '';
        foreach ($rows as $r) {
            $fullName = trim($r['name'] ?? '');
            if (empty($fullName)) continue;

            $parts     = explode(' ', $fullName, 2);
            $firstName = $parts[0] ?? '';
            $lastName  = $parts[1] ?? '';

            // Clean phone numbers to 10 digits
            $phone1 = preg_replace('/[^0-9]/', '', $r['phone_primary'] ?? '');
            if (strlen($phone1) > 10) $phone1 = substr($phone1, -10);

            $phone2 = preg_replace('/[^0-9]/', '', $r['phone_secondary'] ?? '');
            if (strlen($phone2) > 10) $phone2 = substr($phone2, -10);

            $vcf .= "BEGIN:VCARD\r\n";
            $vcf .= "VERSION:3.0\r\n";
            $vcf .= "PRODID:-//CardVault//EN\r\n";
            $vcf .= "FN:" . str_replace([";", "\n", "\r"], " ", $fullName) . "\r\n";
            $vcf .= "N:" . str_replace([";", "\n", "\r"], " ", $lastName) . ";" . str_replace([";", "\n", "\r"], " ", $firstName) . ";;;\r\n";

            if (!empty($r['company_name'])) {
                $vcf .= "ORG:" . str_replace([";", "\n", "\r"], " ", $r['company_name']) . "\r\n";
            }
            if (!empty($r['designation'])) {
                $vcf .= "TITLE:" . str_replace([";", "\n", "\r"], " ", $r['designation']) . "\r\n";
            }
            if (!empty($phone1)) {
                $vcf .= "TEL;TYPE=CELL:" . $phone1 . "\r\n";
            }
            if (!empty($phone2)) {
                $vcf .= "TEL;TYPE=WORK:" . $phone2 . "\r\n";
            }
            if (!empty($r['email_primary'])) {
                $vcf .= "EMAIL;TYPE=INTERNET:" . trim($r['email_primary']) . "\r\n";
            }
            if (!empty($r['email_secondary'])) {
                $vcf .= "EMAIL;TYPE=WORK:" . trim($r['email_secondary']) . "\r\n";
            }
            if (!empty($r['linkedin_url'])) {
                $vcf .= "URL:" . trim($r['linkedin_url']) . "\r\n";
            }
            if (!empty($r['website'])) {
                $vcf .= "URL;TYPE=WORK:" . trim($r['website']) . "\r\n";
            }

            $addr  = str_replace([";", "\n", "\r"], " ", $r['company_address'] ?? '');
            $city  = str_replace([";", "\n", "\r"], " ", $r['company_city'] ?? '');
            $state = str_replace([";", "\n", "\r"], " ", $r['company_state'] ?? '');

            if (!empty($addr) || !empty($city) || !empty($state)) {
                $vcf .= "ADR;TYPE=WORK:;;{$addr};{$city};{$state};;;India\r\n";
            }

            $vcf .= "END:VCARD\r\n";
        }

        echo $vcf;
        AuditLog::log('export', 'team_contacts_vcf', $teamId);
        exit;
    }

    /**
     * View system audit logs (Admin only)
     */

    public static function auditLogs(): void {
        AuthMiddleware::requireAdmin();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = AuditLog::getLogs($page, 50);
        $logs = $result['data'];
        $total = $result['total'];
        $totalPages = $result['totalPages'];

        $view = 'users/audit_logs';
        $pageTitle = 'Audit Logs';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }
}
