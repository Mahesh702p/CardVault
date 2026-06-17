<?php
/**
 * Temporary migration script
 */
$_basePath = file_exists(__DIR__ . '/../config/app.php') ? dirname(__DIR__) : __DIR__;
require_once $_basePath . '/config/app.php';
require_once $_basePath . '/config/database.php';
unset($_basePath);

try {
    $db = Database::getConnection();
    $db->exec("ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NULL");
    echo "Migration executed successfully! Column email changed to NULL (nullable) in users table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
