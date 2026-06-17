<?php
/**
 * Rating Model — Contact rating with Bayesian average
 */

class Rating {
    /** Minimum votes before a Bayesian score stabilizes */
    const CONFIDENCE_THRESHOLD = 3;

    /**
     * Insert or update a rating (one per user per contact)
     */
    public static function upsert(int $contactId, int $userId, int $rating): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO contact_ratings (contact_id, user_id, rating)
            VALUES (:contact_id, :user_id, :rating)
            ON DUPLICATE KEY UPDATE rating = :rating2, updated_at = NOW()
        ");
        $stmt->execute([
            ':contact_id' => $contactId,
            ':user_id'    => $userId,
            ':rating'     => $rating,
            ':rating2'    => $rating
        ]);

        self::recalculate($contactId);
    }

    /**
     * Remove a user's rating for a contact
     */
    public static function remove(int $contactId, int $userId): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM contact_ratings WHERE contact_id = :cid AND user_id = :uid");
        $stmt->execute([':cid' => $contactId, ':uid' => $userId]);

        self::recalculate($contactId);
    }

    /**
     * Get the current user's rating for a contact (null if not rated)
     */
    public static function getUserRating(int $contactId, int $userId): ?int {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT rating FROM contact_ratings WHERE contact_id = :cid AND user_id = :uid");
        $stmt->execute([':cid' => $contactId, ':uid' => $userId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : null;
    }

    /**
     * Get all ratings for a contact
     */
    public static function getForContact(int $contactId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT cr.rating, cr.created_at, u.name AS user_name
            FROM contact_ratings cr
            LEFT JOIN users u ON cr.user_id = u.id
            WHERE cr.contact_id = :cid
            ORDER BY cr.created_at DESC
        ");
        $stmt->execute([':cid' => $contactId]);
        return $stmt->fetchAll();
    }

    /**
     * Get global average rating across all contacts (the C value)
     */
    public static function getGlobalAverage(): float {
        $db = Database::getConnection();
        $avg = $db->query("SELECT AVG(rating) FROM contact_ratings")->fetchColumn();
        return $avg ? (float)$avg : 3.5; // Default prior when no ratings exist
    }

    /**
     * Recalculate cached rating columns on the contacts table
     * Uses Bayesian Average: weighted = (v*R + m*C) / (v + m)
     */
    public static function recalculate(int $contactId): void {
        $db = Database::getConnection();
        $m = self::CONFIDENCE_THRESHOLD;
        $C = self::getGlobalAverage();

        // Get this contact's stats
        $stmt = $db->prepare("
            SELECT COUNT(*) AS cnt, AVG(rating) AS avg_rating
            FROM contact_ratings
            WHERE contact_id = :cid
        ");
        $stmt->execute([':cid' => $contactId]);
        $stats = $stmt->fetch();

        $v = (int)$stats['cnt'];
        $R = $stats['avg_rating'] ? (float)$stats['avg_rating'] : 0;

        $bayesian = ($v > 0) ? ($v * $R + $m * $C) / ($v + $m) : null;
        $avg = ($v > 0) ? round($R, 2) : null;

        $stmt = $db->prepare("
            UPDATE contacts
            SET rating_count = :cnt, rating_avg = :avg, rating_bayesian = :bayesian
            WHERE id = :cid
        ");
        $stmt->execute([
            ':cnt'      => $v,
            ':avg'      => $avg,
            ':bayesian' => $bayesian ? round($bayesian, 2) : null,
            ':cid'      => $contactId
        ]);
    }
}
