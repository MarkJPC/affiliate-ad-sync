-- ============================================================================
-- Affiliate Ad Sync System — Database Schema v2.0
-- February 18, 2026
-- Author: Mark Cena
--
-- Run this entire file in phpMyAdmin to create all tables and seed data.
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================================

-- Use a clean slate (drop tables in reverse dependency order)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS export_logs;
DROP TABLE IF EXISTS sync_logs;
DROP TABLE IF EXISTS site_advertiser_rules;
DROP TABLE IF EXISTS placements;
DROP TABLE IF EXISTS ads;
DROP TABLE IF EXISTS advertisers;
DROP TABLE IF EXISTS sites;
DROP TABLE IF EXISTS geo_regions;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- TABLE: sites
-- Purpose: Your WordPress websites.
-- ============================================================================
CREATE TABLE sites (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255)    NOT NULL,
    domain          VARCHAR(255)    NOT NULL UNIQUE,
    wordpress_url   VARCHAR(500)    NULL,
    is_active       BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE: advertisers
-- Purpose: Companies/brands whose products we promote through affiliate programs.
--
-- Key v2 changes:
--   - Added default_weight (advertiser-level weight, inheritable by ads)
--   - Added is_active for soft-delete when advertiser disappears from network
--   - Added commission_rate for network comparison
--   - network + network_advertiser_id is the unique key per network record
--
-- Note on duplicates across networks:
--   The same brand may exist on multiple networks (e.g., Camping World on
--   FlexOffers AND Awin). Each gets its own row. Deduplication is handled
--   at the application layer — the dashboard groups them visually and
--   site_advertiser_rules apply to all rows sharing the same brand.
-- ============================================================================
CREATE TABLE advertisers (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    network                 ENUM('flexoffers', 'awin', 'cj', 'impact') NOT NULL,
    network_advertiser_id   VARCHAR(255)    NOT NULL,
    name                    VARCHAR(255)    NOT NULL,
    website_url             VARCHAR(500)    NULL,
    category                VARCHAR(255)    NULL,

    -- Performance metrics (network-reported, updated each sync)
    total_clicks            INT             NOT NULL DEFAULT 0,
    total_revenue           DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    epc                     DECIMAL(8,4)    NOT NULL DEFAULT 0.0000,
    commission_rate         VARCHAR(100)    NULL        COMMENT 'Commission info from network (e.g., "8%" or "$5 flat")',

    -- Geo: advertiser's home country (ISO 2-letter code, e.g. "US", "CA", "GB")
    -- Extracted from network API during sync; used to resolve geo_countries on ads.
    country_code            VARCHAR(10)     NULL        COMMENT 'Advertiser home country (ISO 2-letter)',

    -- Weight: Richard assigns this in the advertiser grid.
    -- All ads from this advertiser inherit this weight unless overridden.
    -- Valid values: 2, 4, 6, 8, 10. NULL = not yet assigned (defaults to 2 at export).
    default_weight          TINYINT         NULL        COMMENT 'AdRotate weight (2/4/6/8/10). Inherited by ads.',

    -- Soft-delete: when advertiser disappears from network sync
    is_active               BOOLEAN         NOT NULL DEFAULT TRUE,

    -- Sync tracking
    last_synced_at          TIMESTAMP       NULL,
    raw_hash                VARCHAR(64)     NULL        COMMENT 'SHA-256 of raw API response for change detection',

    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- One row per network + network ID combination
    UNIQUE KEY uq_network_advertiser (network, network_advertiser_id),

    -- Common query patterns
    INDEX idx_name (name),
    INDEX idx_network (network),
    INDEX idx_is_active (is_active),
    INDEX idx_epc (epc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE: ads
-- Purpose: Individual ad creatives pulled from affiliate networks.
--
-- Key v2 changes:
--   - approval_status + approval_reason moved HERE (global, not per-site)
--   - Ads default to 'approved' (MVP: deny bad ones, not approve every one)
--   - Removed deny_is_permanent (denied = denied, period)
--   - Added weight_override for ad-level weight override
--   - Stale ads are HARD DELETED during sync (not soft-deleted)
--
-- Design principle: AdRotate fields are stored 1:1 so CSV export is a
-- straight SELECT of those columns.
-- ============================================================================
CREATE TABLE ads (
    id                  INT AUTO_INCREMENT PRIMARY KEY,

    -- === INTERNAL FIELDS (not exported to AdRotate) ===
    network             ENUM('flexoffers', 'awin', 'cj', 'impact') NOT NULL,
    network_ad_id       VARCHAR(255)    NOT NULL,
    advertiser_id       INT             NOT NULL,
    creative_type       ENUM('banner', 'text', 'html') NOT NULL DEFAULT 'banner',
    tracking_url        VARCHAR(2000)   NOT NULL,
    destination_url     VARCHAR(2000)   NULL,
    html_snippet        TEXT            NULL,
    status              ENUM('active', 'paused', 'expired') NOT NULL DEFAULT 'active',

    -- Ad-level performance (network-reported)
    clicks              INT             NOT NULL DEFAULT 0,
    revenue             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    epc                 DECIMAL(8,4)    NOT NULL DEFAULT 0.0000,

    -- Approval: GLOBAL (not per-site). Ads default to approved.
    -- Richard only denies bad images/creatives. Denied = permanent unless re-approved.
    approval_status     ENUM('approved', 'denied') NOT NULL DEFAULT 'approved',
    approval_reason     VARCHAR(500)    NULL        COMMENT 'Why this ad was denied',

    -- Weight override: if set, this takes priority over the advertiser default_weight.
    -- Valid values: 2, 4, 6, 8, 10. NULL = use advertiser default.
    weight_override     TINYINT         NULL        COMMENT 'Override advertiser weight (2/4/6/8/10)',

    -- Sync tracking
    last_synced_at      TIMESTAMP       NULL,
    raw_hash            VARCHAR(64)     NULL        COMMENT 'SHA-256 of raw API response for change detection',

    -- === ADROTATE FIELDS (exported directly to CSV) ===
    -- These map 1:1 to AdRotate CSV columns.
    -- Format: [width]X[height]-[advertiser_id]-[advertiser_name]-[ad_id]-[campaign]
    advert_name         VARCHAR(500)    NOT NULL,
    bannercode          TEXT            NOT NULL    COMMENT 'HTML-encoded for CSV export',
    imagetype           VARCHAR(50)     NOT NULL DEFAULT '',
    image_url           VARCHAR(2000)   NULL,
    width               INT             NOT NULL,
    height              INT             NOT NULL,
    campaign_name       VARCHAR(255)    NULL DEFAULT 'General Promotion',
    enable_stats        CHAR(1)         NOT NULL DEFAULT 'Y',
    show_everyone       CHAR(1)         NOT NULL DEFAULT 'Y',
    show_desktop        CHAR(1)         NOT NULL DEFAULT 'Y',
    show_mobile         CHAR(1)         NOT NULL DEFAULT 'Y',
    show_tablet         CHAR(1)         NOT NULL DEFAULT 'Y',
    show_ios            CHAR(1)         NOT NULL DEFAULT 'Y',
    show_android        CHAR(1)         NOT NULL DEFAULT 'Y',
    autodelete          CHAR(1)         NOT NULL DEFAULT 'Y',
    autodisable         CHAR(1)         NOT NULL DEFAULT 'N',
    budget              INT             NOT NULL DEFAULT 0,
    click_rate          INT             NOT NULL DEFAULT 0,
    impression_rate     INT             NOT NULL DEFAULT 0,
    state_required      CHAR(1)         NOT NULL DEFAULT 'N',
    geo_cities          VARCHAR(500)    NOT NULL DEFAULT 'a:0:{}',
    geo_states          VARCHAR(500)    NOT NULL DEFAULT 'a:0:{}',
    geo_countries       TEXT            NOT NULL    COMMENT 'PHP serialized array of country codes',
    schedule_start      BIGINT          NOT NULL    COMMENT 'Unix timestamp',
    schedule_end        BIGINT          NOT NULL DEFAULT 2650941780  COMMENT 'Unix timestamp, far future = no end',

    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- One row per network + network ad ID
    UNIQUE KEY uq_network_ad (network, network_ad_id),

    -- Foreign key to advertisers
    CONSTRAINT fk_ads_advertiser FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,

    -- Common query patterns
    INDEX idx_advertiser (advertiser_id),
    INDEX idx_dimensions (width, height),
    INDEX idx_approval (approval_status),
    INDEX idx_status (status),
    INDEX idx_network (network),
    INDEX idx_creative_type (creative_type),
    INDEX idx_last_synced_at (last_synced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE: placements
-- Purpose: Ad slots on each website with exact dimensions.
--          Maps to AdRotate groups. Only ads with matching dimensions
--          are eligible for each placement.
-- ============================================================================
CREATE TABLE placements (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    site_id             INT             NOT NULL,
    name                VARCHAR(255)    NOT NULL,
    description         VARCHAR(500)    NULL,
    width               INT             NOT NULL,
    height              INT             NOT NULL,
    is_active           BOOLEAN         NOT NULL DEFAULT TRUE,
    adrotate_group_id   INT             NULL        COMMENT 'Corresponding AdRotate group ID on this site',

    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_placements_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,

    -- One placement per site + dimension combo
    UNIQUE KEY uq_site_dimensions (site_id, width, height),

    INDEX idx_site (site_id),
    INDEX idx_dimensions (width, height)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE: site_advertiser_rules
-- Purpose: Controls which advertisers are allowed or blocked on each site.
--          This is the primary filtering mechanism — Richard's "advertiser grid."
--
-- Key v2 changes:
--   - Removed decided_by, decided_at (Richard said not necessary)
--   - Auto-created from per-site API keys during sync (status = 'default')
--   - Nothing exports until Richard sets rule to 'allowed'
-- ============================================================================
CREATE TABLE site_advertiser_rules (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    site_id             INT             NOT NULL,
    advertiser_id       INT             NOT NULL,
    rule                ENUM('allowed', 'denied', 'default') NOT NULL DEFAULT 'default',
    reason              VARCHAR(500)    NULL        COMMENT 'Why this decision was made',

    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_rules_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    CONSTRAINT fk_rules_advertiser FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,

    -- One rule per site + advertiser combination
    UNIQUE KEY uq_site_advertiser (site_id, advertiser_id),

    INDEX idx_site (site_id),
    INDEX idx_advertiser (advertiser_id),
    INDEX idx_rule (rule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE: sync_logs
-- Purpose: Audit trail of every sync run. Useful for troubleshooting
--          ("Did the sync run last night?") and tracking system health.
-- ============================================================================
CREATE TABLE sync_logs (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    network             ENUM('flexoffers', 'awin', 'cj', 'impact') NOT NULL,
    site_domain         VARCHAR(255)    NULL        COMMENT 'Which site this sync was for (NULL = all sites)',
    started_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at        TIMESTAMP       NULL,
    status              ENUM('running', 'success', 'failed') NOT NULL DEFAULT 'running',
    advertisers_synced  INT             NOT NULL DEFAULT 0,
    ads_synced          INT             NOT NULL DEFAULT 0,
    ads_deleted         INT             NOT NULL DEFAULT 0   COMMENT 'Stale ads hard-deleted this run',
    error_message       TEXT            NULL,

    INDEX idx_network (network),
    INDEX idx_status (status),
    INDEX idx_started (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE: export_logs
-- Purpose: Audit trail of every CSV export. Useful for auditing
--          ("What did we send to RV Travel Life on Tuesday?").
-- ============================================================================
CREATE TABLE export_logs (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    site_id             INT             NOT NULL,
    filename            VARCHAR(500)    NOT NULL    COMMENT 'e.g., rvtravellife-2026-02-16.csv',
    ads_exported        INT             NOT NULL DEFAULT 0,
    exported_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exported_by         VARCHAR(100)    NULL        COMMENT 'Dashboard user who triggered export',

    CONSTRAINT fk_export_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,

    INDEX idx_site (site_id),
    INDEX idx_exported_at (exported_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- VIEW: v_exportable_ads
-- Purpose: The "export query" as a view. For a given site, this returns
--          all ads ready for CSV export with the correct weight calculated.
--
-- Weight logic:
--   1. If ad has weight_override → use that
--   2. Else if advertiser has default_weight → use that
--   3. Else → default to 2 (lowest)
--
-- Usage: SELECT * FROM v_exportable_ads WHERE site_id = 1 AND width = 300 AND height = 250;
-- ============================================================================
CREATE OR REPLACE VIEW v_exportable_ads AS
SELECT
    a.id                AS ad_id,
    a.advertiser_id,
    adv.name            AS advertiser_name,
    a.network,
    sar.site_id,
    s.domain            AS site_domain,

    -- Calculated weight: ad override > advertiser default > fallback 2
    COALESCE(a.weight_override, adv.default_weight, 2) AS final_weight,

    -- AdRotate CSV fields
    a.advert_name,
    a.bannercode,
    a.imagetype,
    a.image_url,
    a.width,
    a.height,
    a.enable_stats,
    a.show_everyone,
    a.show_desktop,
    a.show_mobile,
    a.show_tablet,
    a.show_ios,
    a.show_android,
    a.autodelete,
    a.autodisable,
    a.budget,
    a.click_rate,
    a.impression_rate,
    a.state_required,
    a.geo_cities,
    a.geo_states,
    a.geo_countries,
    a.schedule_start,
    a.schedule_end

FROM ads a
JOIN advertisers adv ON a.advertiser_id = adv.id
JOIN site_advertiser_rules sar ON sar.advertiser_id = adv.id
JOIN sites s ON sar.site_id = s.id
WHERE
    -- Step 1: Advertiser must be explicitly allowed on this site
    sar.rule = 'allowed'
    -- Advertiser must be active (not soft-deleted)
    AND adv.is_active = TRUE
    -- Step 3: Ad must be approved (not denied)
    AND a.approval_status = 'approved'
    -- Ad must be active in the network
    AND a.status = 'active';
    -- Step 2 (placement matching) is applied in the application query
    -- by adding: AND a.width = ? AND a.height = ?


-- ============================================================================
-- TABLE: geo_regions
-- Purpose: Geographic targeting regions for AdRotate.
--          Each region maps a set of advertiser countries to an AdRotate
--          PHP-serialized geo_countries string. Lower priority = more specific.
-- ============================================================================
CREATE TABLE geo_regions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)    NOT NULL UNIQUE,
    priority        INT             NOT NULL        COMMENT 'Lower = more specific (1=Canada, 5=USA catch-all)',
    country_codes   TEXT            NOT NULL        COMMENT 'Comma-separated ISO 2-letter codes belonging to this region',
    adrotate_value  TEXT            NOT NULL        COMMENT 'PHP serialized array for AdRotate geo_countries',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Sites
INSERT INTO sites (name, domain, wordpress_url, is_active) VALUES
    ('RV Travel Life',          'rvtravellife.com',             'https://www.rvtravellife.com',         TRUE),
    ('This Old Campsite',       'thisoldcampsite.com',          'https://thisoldcampsite.com',           TRUE),
    ('Marine Part Shop',        'marinepartshop.com',           'https://marinepartshop.com',            TRUE),
    ('Powersports Part Shop',   'powersportspartshop.com',      'https://powersportspartshop.com',       TRUE),
    ('The Part Shops',          'thepartshops.com',             'https://thepartshops.com',              TRUE);

-- Common placements (standard IAB sizes)
-- adrotate_group_id is NULL for now — Richard will provide these
-- Note: same sizes across all sites, but group IDs may differ per site
INSERT INTO placements (site_id, name, width, height, is_active) VALUES
    -- RV Travel Life (id=1)
    (1, 'Medium Rectangle',     300, 250, TRUE),
    (1, 'Leaderboard',          728, 90,  TRUE),
    (1, 'Wide Skyscraper',      160, 600, TRUE),
    (1, 'Billboard',            970, 250, TRUE),
    -- This Old Campsite (id=2)
    (2, 'Medium Rectangle',     300, 250, TRUE),
    (2, 'Leaderboard',          728, 90,  TRUE),
    (2, 'Wide Skyscraper',      160, 600, TRUE),
    (2, 'Billboard',            970, 250, TRUE),
    -- Marine Part Shop (id=3)
    (3, 'Medium Rectangle',     300, 250, TRUE),
    (3, 'Leaderboard',          728, 90,  TRUE),
    (3, 'Wide Skyscraper',      160, 600, TRUE),
    (3, 'Billboard',            970, 250, TRUE),
    -- Powersports Part Shop (id=4)
    (4, 'Medium Rectangle',     300, 250, TRUE),
    (4, 'Leaderboard',          728, 90,  TRUE),
    (4, 'Wide Skyscraper',      160, 600, TRUE),
    (4, 'Billboard',            970, 250, TRUE),
    -- The Part Shops (id=5)
    (5, 'Medium Rectangle',     300, 250, TRUE),
    (5, 'Leaderboard',          728, 90,  TRUE),
    (5, 'Wide Skyscraper',      160, 600, TRUE),
    (5, 'Billboard',            970, 250, TRUE);

-- Geo regions (Richard's 5 regions from email)
-- Priority: 1=most specific (Canada), 5=catch-all (USA)
INSERT INTO geo_regions (name, priority, country_codes, adrotate_value) VALUES
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


-- ============================================================================
-- VERIFICATION: Show all tables and row counts
-- ============================================================================
SELECT 'sites' AS table_name, COUNT(*) AS rows FROM sites
UNION ALL SELECT 'placements', COUNT(*) FROM placements
UNION ALL SELECT 'advertisers', COUNT(*) FROM advertisers
UNION ALL SELECT 'ads', COUNT(*) FROM ads
UNION ALL SELECT 'site_advertiser_rules', COUNT(*) FROM site_advertiser_rules
UNION ALL SELECT 'sync_logs', COUNT(*) FROM sync_logs
UNION ALL SELECT 'export_logs', COUNT(*) FROM export_logs
UNION ALL SELECT 'geo_regions', COUNT(*) FROM geo_regions;