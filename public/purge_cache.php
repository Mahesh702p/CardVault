<?php
// Reset PHP OPcache so new code is picked up
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache cleared.\n";
} else {
    echo "ℹ️ OPcache not available or not enabled.\n";
}

// Optionally show DB diagnostics (remove after debugging)
if (isset($_GET['debug'])) {
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../src/Database/Database.php';
    $db = Database::getConnection();
    $users = $db->query("SELECT id, name, employee_id, department_id FROM users ORDER BY id DESC LIMIT 10")->fetchAll();
    echo "\nUsers in DB:\n";
    foreach ($users as $u) {
        echo "  id={$u['id']} name={$u['name']} emp={$u['employee_id']} dept_id={$u['department_id']}\n";
    }
    $depts = $db->query("SELECT id, name FROM departments")->fetchAll();
    echo "\nDepartments:\n";
    foreach ($depts as $d) {
        echo "  id={$d['id']} name={$d['name']}\n";
    }
}
echo "\nDone.";
