<?php
/**
 * ProductService Model — Manages products/services for search
 */

class ProductServiceModel {
    /**
     * Find or create a product/service
     */
    public static function findOrCreate(string $name, string $category = ''): int {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT id FROM products_services WHERE name = :name AND (category = :category OR category IS NULL) LIMIT 1");
        $stmt->execute([':name' => $name, ':category' => $category]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            return (int)$existing['id'];
        }

        $stmt = $db->prepare("INSERT INTO products_services (name, category) VALUES (:name, :category)");
        $stmt->execute([':name' => $name, ':category' => $category]);
        return (int)$db->lastInsertId();
    }

    /**
     * Link a product/service to a company
     */
    public static function linkToCompany(int $companyId, int $productServiceId, string $description = ''): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT IGNORE INTO company_products (company_id, product_service_id, description) 
            VALUES (:cid, :pid, :desc)
        ");
        $stmt->execute([':cid' => $companyId, ':pid' => $productServiceId, ':desc' => $description]);
    }

    /**
     * Get all products/services for a company
     */
    public static function getByCompany(int $companyId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT ps.* FROM products_services ps
            JOIN company_products cp ON ps.id = cp.product_service_id
            WHERE cp.company_id = :cid
            ORDER BY ps.name
        ");
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all unique categories
     */
    public static function getCategories(): array {
        $db = Database::getConnection();
        return $db->query("SELECT DISTINCT category FROM products_services WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get all products/services
     */
    public static function all(): array {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM products_services ORDER BY name")->fetchAll();
    }
}
