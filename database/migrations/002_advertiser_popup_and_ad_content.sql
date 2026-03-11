-- ============================================================================
-- Migration 002: Advertiser Popup Fields + Ad Content Column
-- Date: 2026-03-10
--
-- Adds:
--   1. description, logo_url, network_rank columns to advertisers table
--   2. ad_content column to ads table (for text export anchor text)
--
-- Safe to run multiple times (checks IF NOT EXISTS).
-- Works on MySQL 5.7+ / MariaDB 10.3+.
--
-- For SQLite: use `python setup-dev-db.py --reset` instead.
-- ============================================================================

-- 1. Add description column to advertisers
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'advertisers'
      AND COLUMN_NAME = 'description'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE advertisers ADD COLUMN description TEXT NULL COMMENT ''Advertiser/program description from network'' AFTER category',
    'SELECT ''description column already exists'' AS status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add logo_url column to advertisers
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'advertisers'
      AND COLUMN_NAME = 'logo_url'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE advertisers ADD COLUMN logo_url VARCHAR(2000) NULL COMMENT ''Advertiser logo image URL'' AFTER description',
    'SELECT ''logo_url column already exists'' AS status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add network_rank column to advertisers
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'advertisers'
      AND COLUMN_NAME = 'network_rank'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE advertisers ADD COLUMN network_rank DECIMAL(6,2) NULL COMMENT ''Network-reported ranking/quality score'' AFTER logo_url',
    'SELECT ''network_rank column already exists'' AS status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Add ad_content column to ads
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ads'
      AND COLUMN_NAME = 'ad_content'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE ads ADD COLUMN ad_content TEXT NULL COMMENT ''Promotional copy from network (CJ ad-content, etc.)'' AFTER raw_hash',
    'SELECT ''ad_content column already exists'' AS status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
