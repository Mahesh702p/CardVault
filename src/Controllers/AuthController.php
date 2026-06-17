<?php
/**
 * Auth Controller
 * Login is via Employee ID + Password only.
 * User creation is an admin-only action.
 */

class AuthController {
    /**
     * Show login page
     */
    public static function loginPage(): void {
        $flash = Response::flash();
        include VIEW_PATH . '/auth/login.php';
    }

    /**
     * Handle login form submission (Employee ID + Password)
     */
    public static function login(): void {
        $employeeId = trim($_POST['employee_id'] ?? '');
        $password   = $_POST['password'] ?? '';

        // Validate
        $validator = new Validator();
        $validator->required('employee_id', $employeeId, 'Employee ID')
                  ->required('password',    $password,    'Password');

        if (!$validator->passes()) {
            Response::redirect('login', [
                'type'    => 'error',
                'message' => implode(' ', $validator->errors())
            ]);
            return;
        }

        // Find user by employee ID
        $user = User::findByEmployeeId($employeeId);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::redirect('login', [
                'type'    => 'error',
                'message' => 'Invalid Employee ID or password.'
            ]);
            return;
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user']    = [
            'id'              => $user['id'],
            'name'            => $user['name'],
            'email'           => $user['email'],
            'role'            => $user['role'],
            'department_id'   => $user['department_id'],
            'department_name' => $user['department_name']
        ];

        // Issue signed auth cookie (stateless — survives across server instances)
        Auth::setCookie($user);

        // Log audit
        AuditLog::log('login');

        Response::redirect('dashboard', [
            'type'    => 'success',
            'message' => 'Welcome back, ' . $user['name'] . '!'
        ]);
    }

    /**
     * Show registration page
     */
    public static function registerPage(): void {
        $flash = Response::flash();
        $departments = User::getDepartments();
        include VIEW_PATH . '/auth/register.php';
    }

    /**
     * Handle registration form submission
     */
    public static function register(): void {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $employeeId = trim($_POST['employee_id'] ?? '');
        $departmentName = trim($_POST['department_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = 'user'; // Hardcode as user

        // Validate
        $validator = new Validator();
        $validator->required('first_name', $firstName, 'First Name')
                  ->required('last_name', $lastName, 'Last Name')
                  ->required('employee_id', $employeeId, 'Employee ID')
                  ->required('department_name', $departmentName, 'Department')
                  ->required('password', $password, 'Password')
                  ->minLength('password', $password, 6, 'Password');

        if ($password !== $confirmPassword) {
            $validator->addError('confirm_password', 'Passwords do not match.');
        }

        if (!$validator->passes()) {
            Response::redirect('register', [
                'type' => 'error',
                'message' => implode(' ', $validator->errors())
            ]);
            return;
        }

        // Check if employee_id already exists and is active
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE employee_id = :emp_id AND is_active = 1");
        $stmt->execute([':emp_id' => $employeeId]);
        if ((int)$stmt->fetchColumn() > 0) {
            Response::redirect('register', [
                'type' => 'error',
                'message' => 'Employee ID is already registered.'
            ]);
            return;
        }

        try {
            $departmentId = User::getOrCreateDepartment($departmentName);

            $userId = User::create([
                'employee_id' => $employeeId,
                'name' => trim($firstName . ' ' . $lastName),
                'password' => $password,
                'department_id' => $departmentId,
                'role' => $role
            ]);

            // Automatically log in the newly registered user
            $user = User::findById($userId);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'department_id' => $user['department_id'],
                    'department_name' => $user['department_name']
                ];
                $_SESSION['show_pwa_install_prompt'] = true;
                AuditLog::log('register');
            }

            Response::redirect('dashboard', [
                'type' => 'success',
                'message' => 'Registration successful! Welcome to CardVault.'
            ]);
        } catch (Exception $e) {
            Response::redirect('register', [
                'type' => 'error',
                'message' => 'Registration failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Logout
     */
    public static function logout(): void {
        AuditLog::log('login');
        Auth::clearCookie();
        session_destroy();
        header('Location: ' . APP_URL . '/login');
        exit;
    }
}
