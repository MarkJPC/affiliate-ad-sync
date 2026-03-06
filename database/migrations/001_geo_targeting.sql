-- ============================================================================
-- Migration 001: Geo-Targeting Support
-- Date: 2026-03-05
--
-- Adds:
--   1. country_code column to advertisers table
--   2. geo_regions table with 5 pre-seeded regions
--
-- Safe to run multiple times (uses IF NOT EXISTS / IF NOT EXISTS patterns).
-- Works on MySQL 5.7+ / MariaDB 10.3+.
--
-- For SQLite: use `python setup-dev-db.py --reset` instead.
-- ============================================================================

-- 1. Add country_code column to advertisers (if not already present)
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'advertisers'
      AND COLUMN_NAME = 'country_code'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE advertisers ADD COLUMN country_code VARCHAR(10) NULL COMMENT ''Advertiser home country (ISO 2-letter)'' AFTER category',
    'SELECT ''country_code column already exists'' AS status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 2. Create geo_regions table
CREATE TABLE IF NOT EXISTS geo_regions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)    NOT NULL UNIQUE,
    priority        INT             NOT NULL        COMMENT 'Lower = more specific (1=Canada, 5=USA catch-all)',
    country_codes   TEXT            NOT NULL        COMMENT 'Comma-separated ISO 2-letter codes belonging to this region',
    adrotate_value  TEXT            NOT NULL        COMMENT 'PHP serialized array for AdRotate geo_countries',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. Seed geo_regions (skip if already populated)
INSERT IGNORE INTO geo_regions (name, priority, country_codes, adrotate_value) VALUES
    ('Canada',        1, 'CA',
     'a:1:{i:0;s:2:"CA";}'),
    ('Australasia',   2, 'AU,NZ',
     'a:8:{i:0;s:2:"AU";i:1;s:2:"NZ";i:2;s:2:"SG";i:3;s:2:"MY";i:4;s:2:"TH";i:5;s:2:"PH";i:6;s:2:"ID";i:7;s:2:"IN";}'),
    ('Europe',        3, 'GB,DE,FR,IT,ES,NL,BE,AT,CH,SE,NO,DK,FI,IE,PT,PL',
     'a:16:{i:0;s:2:"GB";i:1;s:2:"DE";i:2;s:2:"FR";i:3;s:2:"IT";i:4;s:2:"ES";i:5;s:2:"NL";i:6;s:2:"BE";i:7;s:2:"AT";i:8;s:2:"CH";i:9;s:2:"SE";i:10;s:2:"NO";i:11;s:2:"DK";i:12;s:2:"FI";i:13;s:2:"IE";i:14;s:2:"PT";i:15;s:2:"PL";}'),
    ('North America', 4, 'US,CA',
     'a:23:{i:0;s:2:"US";i:1;s:2:"CA";i:2;s:2:"MX";i:3;s:2:"GT";i:4;s:2:"BZ";i:5;s:2:"HN";i:6;s:2:"SV";i:7;s:2:"NI";i:8;s:2:"CR";i:9;s:2:"PA";i:10;s:2:"CO";i:11;s:2:"VE";i:12;s:2:"EC";i:13;s:2:"PE";i:14;s:2:"BR";i:15;s:2:"BO";i:16;s:2:"PY";i:17;s:2:"UY";i:18;s:2:"AR";i:19;s:2:"CL";i:20;s:2:"DO";i:21;s:2:"JM";i:22;s:2:"TT";}'),
    ('USA',           5, 'US',
     'a:55:{i:0;s:2:"US";i:1;s:2:"CA";i:2;s:2:"MX";i:3;s:2:"GB";i:4;s:2:"DE";i:5;s:2:"FR";i:6;s:2:"IT";i:7;s:2:"ES";i:8;s:2:"NL";i:9;s:2:"BE";i:10;s:2:"AT";i:11;s:2:"CH";i:12;s:2:"SE";i:13;s:2:"NO";i:14;s:2:"DK";i:15;s:2:"FI";i:16;s:2:"IE";i:17;s:2:"PT";i:18;s:2:"PL";i:19;s:2:"AU";i:20;s:2:"NZ";i:21;s:2:"SG";i:22;s:2:"MY";i:23;s:2:"TH";i:24;s:2:"PH";i:25;s:2:"ID";i:26;s:2:"IN";i:27;s:2:"GT";i:28;s:2:"BZ";i:29;s:2:"HN";i:30;s:2:"SV";i:31;s:2:"NI";i:32;s:2:"CR";i:33;s:2:"PA";i:34;s:2:"CO";i:35;s:2:"VE";i:36;s:2:"EC";i:37;s:2:"PE";i:38;s:2:"BR";i:39;s:2:"BO";i:40;s:2:"PY";i:41;s:2:"UY";i:42;s:2:"AR";i:43;s:2:"CL";i:44;s:2:"DO";i:45;s:2:"JM";i:46;s:2:"TT";i:47;s:2:"JP";i:48;s:2:"KR";i:49;s:2:"TW";i:50;s:2:"HK";i:51;s:2:"IL";i:52;s:2:"AE";i:53;s:2:"SA";i:54;s:2:"ZA";}');
