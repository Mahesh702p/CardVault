<?php
/**
 * Truncated Pagination Helper
 */

class Pagination {
    /**
     * Generate list of pages with ellipses
     * 
     * @param int $currentPage
     * @param int $totalPages
     * @param int $adjacent
     * @return array
     */
    public static function getPages(int $currentPage, int $totalPages, int $adjacent = 2): array {
        if ($totalPages <= 1) {
            return [1];
        }

        $pages = [];
        $pages[] = 1;

        $start = max(2, $currentPage - $adjacent);
        $end = min($totalPages - 1, $currentPage + $adjacent);

        if ($start > 2) {
            if ($start == 3) {
                $pages[] = 2;
            } else {
                $pages[] = '...';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($end < $totalPages - 1) {
            if ($end == $totalPages - 2) {
                $pages[] = $totalPages - 1;
            } else {
                $pages[] = '...';
            }
        }

        $pages[] = $totalPages;
        return $pages;
    }
}
