-- ============================================================================
-- AFFILIATE AD SYNC SYSTEM - DATABASE SCHEMA (MySQL)
-- Version: 1.1.0
-- Last Updated: January 31, 2026
-- Database: MySQL 8.0+
--
-- DESIGN PRINCIPLE: The `ads` table maps 1:1 with AdRotate CSV import format.
-- All AdRotate fields are stored directly, plus additional fields for our
-- internal workflows (network tracking, approval, performance metrics).
-- ============================================================================

-- ============================================================================
-- TABLE: advertisers
-- Stores advertiser/merchant information from all networks
-- ============================================================================

CREATE TABLE advertisers (
    -- Primary identifier (our internal ID)
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),

    -- Network identification
    network ENUM('flexoffers', 'awin', 'cj', 'impact') NOT NULL,
    network_advertiser_id VARCHAR(255) NOT NULL,

    -- Advertiser details
    name VARCHAR(255) NOT NULL,
    website_url TEXT,
    category VARCHAR(255),

    -- Performance metrics (denormalized, updated each sync)
    total_clicks INT DEFAULT 0,
    total_revenue DECIMAL(12, 2) DEFAULT 0,
    epc DECIMAL(10, 4) DEFAULT 0,  -- Earnings per click (advertiser average)

    -- Sync tracking
    last_synced_at TIMESTAMP NULL,
    raw_hash VARCHAR(64),  -- SHA-256 of API response for change detection

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Ensure unique advertiser per network
    CONSTRAINT uq_advertiser_network UNIQUE (network, network_advertiser_id)
) COMMENT = 'Companies/merchants whose products we advertise. Synced from affiliate networks.';

-- Indexes for common queries
CREATE INDEX idx_advertisers_network ON advertisers(network);
CREATE INDEX idx_advertisers_name ON advertisers(name);

-- ============================================================================
-- TABLE: ads
-- The canonical ad schema - ALL AdRotate fields + internal workflow fields
--
-- This table is designed to map 1:1 with AdRotate CSV import format.
-- Export = SELECT AdRotate fields, drop internal fields, done.
-- ============================================================================

CREATE TABLE ads (
    -- =========================================================================
    -- INTERNAL FIELDS (not exported to AdRotate)
    -- =========================================================================

    -- Primary identifier (our internal ID)
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),

    -- Network identification (from canonical schema)
    network ENUM('flexoffers', 'awin', 'cj', 'impact') NOT NULL,
    network_ad_id VARCHAR(255) NOT NULL,           -- a.k.a. network_link_id

    -- Link to advertiser
    advertiser_id CHAR(36) NOT NULL,

    -- Creative type (banner only for MVP)
    creative_type ENUM('banner', 'text', 'html') NOT NULL DEFAULT 'banner',

    -- The actual tracking URL (used to construct bannercode)
    tracking_url TEXT NOT NULL,

    -- Destination URL if provided by network (where user lands after click)
    destination_url TEXT,

    -- Full HTML snippet if provided by network (alternative to constructing bannercode)
    html_snippet TEXT,

    -- Ad status from network's perspective
    status ENUM('active', 'paused', 'expired') NOT NULL DEFAULT 'active',

    -- Performance metrics (denormalized, updated each sync)
    clicks INT DEFAULT 0,
    revenue DECIMAL(12, 2) DEFAULT 0,
    epc DECIMAL(10, 4) DEFAULT 0,  -- Earnings per click for this ad

    -- Sync tracking
    last_synced_at TIMESTAMP NULL,
    raw_hash VARCHAR(64),  -- SHA-256 of API response for change detection

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- =========================================================================
    -- ADROTATE FIELDS (exported directly to CSV)
    -- These map 1:1 with AdRotate import format
    -- =========================================================================

    -- advert_name: Constructed as [width]X[height]-[advertiser_id]-[advertiser_name]-[ad_id]-[campaign]
    -- We store the components separately and construct at export time, OR store pre-built:
    advert_name VARCHAR(255) NOT NULL,

    -- bannercode: HTML snippet for the ad
    -- Format: <a href="[tracking_url]" rel="sponsored"><img src="[image_url]" /></a>
    -- Stored HTML-encoded for CSV export
    bannercode TEXT NOT NULL,

    -- imagetype: Always blank for hosted images (we don't self-host)
    imagetype VARCHAR(50) DEFAULT '',

    -- image_url: Network-hosted banner image URL
    image_url TEXT NOT NULL,

    -- Dimensions (critical for placement matching)
    width INT NOT NULL,
    height INT NOT NULL,

    -- Campaign name (used in advert_name construction)
    campaign_name VARCHAR(255) DEFAULT 'General Promotion',

    -- Display settings (all default to Y for "show everywhere")
    enable_stats CHAR(1) NOT NULL DEFAULT 'Y',
    show_everyone CHAR(1) NOT NULL DEFAULT 'Y',
    show_desktop CHAR(1) NOT NULL DEFAULT 'Y',
    show_mobile CHAR(1) NOT NULL DEFAULT 'Y',
    show_tablet CHAR(1) NOT NULL DEFAULT 'Y',
    show_ios CHAR(1) NOT NULL DEFAULT 'Y',
    show_android CHAR(1) NOT NULL DEFAULT 'Y',

    -- Weight: How often to show this ad (2=rarely, 10=frequently)
    -- Valid values: 2, 4, 6, 8, 10
    weight INT NOT NULL DEFAULT 2,

    -- Auto-management settings
    autodelete CHAR(1) NOT NULL DEFAULT 'Y',
    autodisable CHAR(1) NOT NULL DEFAULT 'N',

    -- Budget/rate settings (always 0, not using these features)
    budget INT NOT NULL DEFAULT 0,
    click_rate INT NOT NULL DEFAULT 0,
    impression_rate INT NOT NULL DEFAULT 0,

    -- Geo-targeting
    state_required CHAR(1) NOT NULL DEFAULT 'N',
    geo_cities TEXT NOT NULL DEFAULT 'a:0:{}',      -- PHP serialized empty array
    geo_states TEXT NOT NULL DEFAULT 'a:0:{}',      -- PHP serialized empty array
    geo_countries TEXT NOT NULL DEFAULT 'a:0:{}',   -- PHP serialized array of country codes

    -- Schedule (Unix timestamps)
    schedule_start BIGINT NOT NULL DEFAULT 0,       -- Start date (seconds since Jan 1, 1970)
    schedule_end BIGINT NOT NULL DEFAULT 2650941780, -- End date (far future = no end)

    -- =========================================================================
    -- CONSTRAINTS
    -- =========================================================================

    -- Ensure unique ad per network
    CONSTRAINT uq_ad_network UNIQUE (network, network_ad_id),

    -- Foreign key to advertisers
    CONSTRAINT fk_ads_advertiser FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,

    -- Check constraints for Y/N fields
    CONSTRAINT chk_enable_stats CHECK (enable_stats IN ('Y', 'N')),
    CONSTRAINT chk_show_everyone CHECK (show_everyone IN ('Y', 'N')),
    CONSTRAINT chk_show_desktop CHECK (show_desktop IN ('Y', 'N')),
    CONSTRAINT chk_show_mobile CHECK (show_mobile IN ('Y', 'N')),
    CONSTRAINT chk_show_tablet CHECK (show_tablet IN ('Y', 'N')),
    CONSTRAINT chk_show_ios CHECK (show_ios IN ('Y', 'N')),
    CONSTRAINT chk_show_android CHECK (show_android IN ('Y', 'N')),
    CONSTRAINT chk_weight CHECK (weight IN (2, 4, 6, 8, 10)),
    CONSTRAINT chk_autodelete CHECK (autodelete IN ('Y', 'N')),
    CONSTRAINT chk_autodisable CHECK (autodisable IN ('Y', 'N')),
    CONSTRAINT chk_state_required CHECK (state_required IN ('Y', 'N'))
) COMMENT = 'Individual ad creatives. Maps 1:1 with AdRotate CSV import format, plus internal workflow fields.';

-- Indexes for common query patterns
CREATE INDEX idx_ads_advertiser ON ads(advertiser_id);
CREATE INDEX idx_ads_network ON ads(network);
CREATE INDEX idx_ads_dimensions ON ads(width, height);
CREATE INDEX idx_ads_status ON ads(status);
CREATE INDEX idx_ads_weight ON ads(weight DESC);

-- ============================================================================
-- TABLE: sites
-- Richard's WordPress sites where ads will be displayed
-- ============================================================================

CREATE TABLE sites (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),

    -- Site identification
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL UNIQUE,

    -- Site configuration
    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    -- AdRotate connection (for future automated imports)
    wordpress_url TEXT,
    adrotate_api_key VARCHAR(255),

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT = 'Richard''s WordPress websites where ads are displayed.';

-- ============================================================================
-- TABLE: placements
-- Ad slots on each site with specific dimensions
-- ============================================================================

CREATE TABLE placements (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),

    -- Link to site
    site_id CHAR(36) NOT NULL,

    -- Placement details
    name VARCHAR(255) NOT NULL,  -- e.g., "Sidebar 300x250", "Leaderboard"
    description TEXT,

    -- Required dimensions (ads must match exactly)
    width INT NOT NULL,
    height INT NOT NULL,

    -- Whether this placement is active
    is_active BOOLEAN NOT NULL DEFAULT TRUE,

    -- AdRotate group ID (for mapping during export)
    adrotate_group_id INT,

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Ensure unique placement name per site
    CONSTRAINT uq_placement_site_name UNIQUE (site_id, name),

    -- Foreign key to sites
    CONSTRAINT fk_placements_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) COMMENT = 'Specific ad slots on each site with fixed dimensions (e.g., 300x250 sidebar).';

-- Index for dimension matching
CREATE INDEX idx_placements_dimensions ON placements(width, height);
CREATE INDEX idx_placements_site ON placements(site_id);

-- ============================================================================
-- TABLE: site_advertiser_rules
-- Per-site allow/deny rules for advertisers
-- ============================================================================

CREATE TABLE site_advertiser_rules (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),

    -- Links
    site_id CHAR(36) NOT NULL,
    advertiser_id CHAR(36) NOT NULL,

    -- The rule (allowed, denied, or default)
    rule ENUM('allowed', 'denied', 'default') NOT NULL DEFAULT 'default',

    -- Admin notes
    reason TEXT,

    -- Who made this decision and when
    decided_by VARCHAR(255),
    decided_at TIMESTAMP NULL,

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- One rule per site-advertiser pair
    CONSTRAINT uq_site_advertiser UNIQUE (site_id, advertiser_id),

    -- Foreign keys
    CONSTRAINT fk_site_advertiser_rules_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    CONSTRAINT fk_site_advertiser_rules_advertiser FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE
) COMMENT = 'Per-site rules for allowing or denying specific advertisers.';

-- Indexes for filtering
CREATE INDEX idx_site_advertiser_rules_site ON site_advertiser_rules(site_id);
CREATE INDEX idx_site_advertiser_rules_advertiser ON site_advertiser_rules(advertiser_id);
CREATE INDEX idx_site_advertiser_rules_rule ON site_advertiser_rules(rule);

-- ============================================================================
-- TABLE: site_ads
-- Per-site approval status for each ad (many-to-many with approval workflow)
-- ============================================================================

CREATE TABLE site_ads (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),

    -- Links
    site_id CHAR(36) NOT NULL,
    ad_id CHAR(36) NOT NULL,

    -- Approval workflow
    approval_status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending',
    approval_reason TEXT,
    deny_is_permanent BOOLEAN NOT NULL DEFAULT TRUE,

    -- Who made this decision and when
    approved_by VARCHAR(255),
    approved_at TIMESTAMP NULL,

    -- Export tracking
    last_exported_at TIMESTAMP NULL,
    adrotate_ad_id INT,  -- ID assigned by AdRotate after import

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- One record per site-ad pair
    CONSTRAINT uq_site_ad UNIQUE (site_id, ad_id),

    -- Foreign keys
    CONSTRAINT fk_site_ads_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    CONSTRAINT fk_site_ads_ad FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
) COMMENT = 'Links ads to sites with approval status. Controls which ads export to which sites.';

-- Indexes for export queries
CREATE INDEX idx_site_ads_site ON site_ads(site_id);
CREATE INDEX idx_site_ads_ad ON site_ads(ad_id);
CREATE INDEX idx_site_ads_approval ON site_ads(approval_status);
CREATE INDEX idx_site_ads_export ON site_ads(site_id, approval_status);

-- ============================================================================
-- TABLE: sync_logs
-- Audit trail for sync operations
-- ============================================================================

CREATE TABLE sync_logs (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),

    -- What was synced
    network ENUM('flexoffers', 'awin', 'cj', 'impact') NOT NULL,

    -- Results
    advertisers_synced INT DEFAULT 0,
    ads_synced INT DEFAULT 0,
    ads_created INT DEFAULT 0,
    ads_updated INT DEFAULT 0,
    errors INT DEFAULT 0,

    -- Timing
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    duration_seconds INT,

    -- Error details if any
    error_message TEXT,

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) COMMENT = 'Audit trail of sync operations from affiliate networks.';

-- Index for recent logs
CREATE INDEX idx_sync_logs_network ON sync_logs(network);
CREATE INDEX idx_sync_logs_started ON sync_logs(started_at DESC);

-- ============================================================================
-- TABLE: export_logs
-- Audit trail for AdRotate CSV exports
-- ============================================================================

CREATE TABLE export_logs (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),

    -- What was exported
    site_id CHAR(36) NOT NULL,

    -- Results
    ads_exported INT DEFAULT 0,

    -- The actual CSV (or path to it)
    csv_filename VARCHAR(255),

    -- Timing
    exported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exported_by VARCHAR(255),

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Foreign key
    CONSTRAINT fk_export_logs_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) COMMENT = 'Audit trail of CSV exports to AdRotate.';

-- Index for recent exports
CREATE INDEX idx_export_logs_site ON export_logs(site_id);
CREATE INDEX idx_export_logs_date ON export_logs(exported_at DESC);

-- ============================================================================
-- HELPER VIEW: Ads ready for export
-- Usage: SELECT * FROM v_exportable_ads WHERE site_id = '...'
-- ============================================================================

CREATE VIEW v_exportable_ads AS
SELECT
    -- Site info
    sa.site_id,
    s.domain AS site_domain,

    -- AdRotate CSV fields (ready for export)
    a.advert_name,
    a.bannercode,
    a.imagetype,
    a.image_url,
    a.enable_stats,
    a.show_everyone,
    a.show_desktop,
    a.show_mobile,
    a.show_tablet,
    a.show_ios,
    a.show_android,
    a.weight,
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
    a.schedule_end,

    -- Additional context (not exported, but useful for filtering/debugging)
    a.id AS ad_id,
    a.network,
    a.width,
    a.height,
    a.tracking_url,
    a.status,
    a.epc,
    adv.id AS advertiser_id,
    adv.name AS advertiser_name,
    adv.network_advertiser_id,
    a.network_ad_id,
    a.campaign_name,
    sa.approval_status,
    sa.last_exported_at
FROM site_ads sa
JOIN ads a ON sa.ad_id = a.id
JOIN advertisers adv ON a.advertiser_id = adv.id
JOIN sites s ON sa.site_id = s.id
LEFT JOIN site_advertiser_rules sar ON sar.site_id = sa.site_id AND sar.advertiser_id = adv.id
WHERE
    sa.approval_status = 'approved'
    AND a.status = 'active'
    AND s.is_active = TRUE
    AND (sar.rule IS NULL OR sar.rule != 'denied');

-- ============================================================================
-- SEED DATA: Richard's Sites
-- ============================================================================

INSERT INTO sites (id, name, domain, wordpress_url) VALUES
    (UUID(), 'RV Travel Life', 'rvtravellife.com', 'https://rvtravellife.com'),
    (UUID(), 'This Old Campsite', 'thisoldcampsite.com', 'https://thisoldcampsite.com'),
    (UUID(), 'Marine Part Shop', 'marinepartshop.com', 'https://marinepartshop.com'),
    (UUID(), 'Powersports Part Shop', 'powersportspartshop.com', 'https://powersportspartshop.com'),
    (UUID(), 'The Part Shops', 'thepartshops.com', 'https://thepartshops.com');

-- ============================================================================
-- SEED DATA: Common Placement Sizes
-- ============================================================================

-- RV Travel Life placements
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Sidebar Medium Rectangle', 300, 250 FROM sites WHERE domain = 'rvtravellife.com';
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Leaderboard', 728, 90 FROM sites WHERE domain = 'rvtravellife.com';
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Wide Skyscraper', 160, 600 FROM sites WHERE domain = 'rvtravellife.com';
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Billboard', 970, 250 FROM sites WHERE domain = 'rvtravellife.com';

-- This Old Campsite placements
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Sidebar Medium Rectangle', 300, 250 FROM sites WHERE domain = 'thisoldcampsite.com';
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Leaderboard', 728, 90 FROM sites WHERE domain = 'thisoldcampsite.com';
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Wide Skyscraper', 160, 600 FROM sites WHERE domain = 'thisoldcampsite.com';

-- Marine Part Shop placements
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Sidebar Medium Rectangle', 300, 250 FROM sites WHERE domain = 'marinepartshop.com';
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Leaderboard', 728, 90 FROM sites WHERE domain = 'marinepartshop.com';

-- Powersports Part Shop placements
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Sidebar Medium Rectangle', 300, 250 FROM sites WHERE domain = 'powersportspartshop.com';
INSERT INTO placements (id, site_id, name, width, height)
SELECT UUID(), id, 'Leaderboard', 728, 90 FROM sites WHERE domain = 'powersportspartshop.com';
