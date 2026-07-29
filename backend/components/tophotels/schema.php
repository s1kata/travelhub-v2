<?php
declare(strict_types=1);

/**
 * Optional MySQL/SQLite tables for TopHotels match + ratings cache.
 * File cache under data/tophotels/ is the primary hot-path source.
 */

if (!function_exists('th_tophotels_ensure_schema')) {
    function th_tophotels_ensure_schema(PDO $pdo): void
    {
        $driver = strtolower((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

        if ($driver === 'mysql') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS tophotels_hotel_match (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tourvisor_hotel_id INT NOT NULL,
                tophotels_id VARCHAR(64) NOT NULL,
                hotel_name VARCHAR(255) NULL,
                country_name VARCHAR(128) NULL,
                match_score DECIMAL(5,2) NULL,
                match_source VARCHAR(32) NOT NULL DEFAULT 'manual',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_tv_hotel (tourvisor_hotel_id),
                KEY idx_th_id (tophotels_id),
                KEY idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS tophotels_ratings (
                tophotels_id VARCHAR(64) NOT NULL PRIMARY KEY,
                rating DECIMAL(4,2) NULL,
                scale TINYINT NOT NULL DEFAULT 10,
                reviews_count INT NULL,
                rating_food DECIMAL(4,2) NULL,
                rating_service DECIMAL(4,2) NULL,
                rating_placement DECIMAL(4,2) NULL,
                last_review_at DATETIME NULL,
                raw_json MEDIUMTEXT NULL,
                synced_at TIMESTAMP NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            return;
        }

        $pdo->exec('CREATE TABLE IF NOT EXISTS tophotels_hotel_match (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tourvisor_hotel_id INTEGER NOT NULL UNIQUE,
            tophotels_id TEXT NOT NULL,
            hotel_name TEXT,
            country_name TEXT,
            match_score REAL,
            match_source TEXT NOT NULL DEFAULT \'manual\',
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT,
            updated_at TEXT
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS tophotels_ratings (
            tophotels_id TEXT PRIMARY KEY,
            rating REAL,
            scale INTEGER NOT NULL DEFAULT 10,
            reviews_count INTEGER,
            rating_food REAL,
            rating_service REAL,
            rating_placement REAL,
            last_review_at TEXT,
            raw_json TEXT,
            synced_at TEXT,
            updated_at TEXT
        )');
    }
}
