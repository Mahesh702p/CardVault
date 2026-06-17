<?php
/**
 * Company Model
 */

class CompanyModel {
    /**
     * Find or create a company by name
     */
    public static function findOrCreate(array $data): int {
        $db = Database::getConnection();
        
        // Try to find existing company
        if (!empty($data['name'])) {
            $stmt = $db->prepare("SELECT id FROM companies WHERE name = :name LIMIT 1");
            $stmt->execute([':name' => $data['name']]);
            $existing = $stmt->fetch();
            if ($existing) {
                return (int)$existing['id'];
            }
        }

        // Create new company
        $stmt = $db->prepare("
            INSERT INTO companies (name, website, industry, address, city, state, pincode, country, gst_number, notes) 
            VALUES (:name, :website, :industry, :address, :city, :state, :pincode, :country, :gst_number, :notes)
        ");
        $stmt->execute([
            ':name' => $data['name'] ?? 'Unknown Company',
            ':website' => $data['website'] ?? '',
            ':industry' => $data['industry'] ?? '',
            ':address' => $data['address'] ?? '',
            ':city' => $data['city'] ?? '',
            ':state' => $data['state'] ?? '',
            ':pincode' => $data['pincode'] ?? '',
            ':country' => $data['country'] ?? 'India',
            ':gst_number' => $data['gst_number'] ?? '',
            ':notes' => $data['notes'] ?? ''
        ]);
        
        return (int)$db->lastInsertId();
    }

    /**
     * Get company by ID
     */
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM companies WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update company details
     */
    public static function update(int $id, array $data): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE companies 
            SET name = :name, website = :website, industry = :industry, 
                address = :address, city = :city, state = :state, 
                pincode = :pincode, gst_number = :gst_number, notes = :notes
            WHERE id = :id
        ");
        $data[':id'] = $id;
        $stmt->execute($data);
    }

    /**
     * Get all companies
     */
    public static function all(): array {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM companies ORDER BY name")->fetchAll();
    }
}
