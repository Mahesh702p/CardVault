<?php
/**
 * User Controller (Admin + self-service)
 */

class UserController {

    // ─── LIST ────────────────────────────────────────────────────────────────
    public static function list(): void {
        AuthMiddleware::requireAdmin();
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
                'email'         => !empty($_POST['email']) ? $_POST['email'] : null,
                'password'      => $_POST['password'],
                'department_id' => $departmentId,
                'role'          => $_POST['role'] ?? 'user'
            ]);
            AuditLog::log('create', 'user', $userId);
            Response::redirect('users', ['type' => 'success', 'message' => 'User created successfully!']);
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
                'email'         => !empty($_POST['email']) ? $_POST['email'] : null,
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
            Response::redirect('users', ['type' => 'success', 'message' => 'User updated successfully!']);
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

        $newPassword = $_POST['new_password'] ?? '';
        if (strlen($newPassword) < 6) {
            Response::redirect("users/{$id}/edit", ['type' => 'error', 'message' => 'Password must be at least 6 characters.']);
            return;
        }

        User::resetPassword($id, $newPassword);
        AuditLog::log('update', 'user', $id, [], ['action' => 'password_reset']);
        Response::redirect('users', ['type' => 'success', 'message' => 'Password reset successfully!']);
    }

    // ─── DEACTIVATE (Admin) ───────────────────────────────────────────────────
    public static function deactivate(int $id): void {
        AuthMiddleware::requireAdmin();
        if (!CSRF::validate()) {
            Response::redirect('users', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $currentUser = AuthMiddleware::user();
        if ($currentUser['id'] === $id) {
            Response::redirect('users', ['type' => 'error', 'message' => 'You cannot deactivate your own account.']);
            return;
        }

        User::deactivate($id);
        AuditLog::log('delete', 'user', $id);
        Response::redirect('users', ['type' => 'success', 'message' => 'User deactivated successfully.']);
    }

    // ─── CHANGE OWN PASSWORD (Any logged-in user) ────────────────────────────
    public static function changePasswordPage(): void {
        $view  = 'users/change_password';
        $pageTitle = 'Change Password';
        $flash = Response::flash();
        include VIEW_PATH . '/layouts/main.php';
    }

    public static function changePassword(): void {
        if (!CSRF::validate()) {
            Response::redirect('profile/password', ['type' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        $user        = AuthMiddleware::user();
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

        if (!User::changePassword($user['id'], $oldPassword, $newPassword)) {
            Response::redirect('profile/password', ['type' => 'error', 'message' => 'Current password is incorrect.']);
            return;
        }

        Response::redirect('dashboard', ['type' => 'success', 'message' => 'Password changed successfully!']);
    }

    // ─── PROFILE PAGE (Any logged-in user) ───────────────────────────────────
    public static function profilePage(): void {
        $currentUser = AuthMiddleware::user();
        // Fetch fresh user row to get cards_visibility
        $userRow = User::findById($currentUser['id']);
        $view = 'users/profile';
        $pageTitle = 'My Profile';
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
}
