-- ============================================================================
-- AFFILIATE AD SYNC SYSTEM - DATABASE SCHEMA (SQLite)
-- Version: 1.1.0
-- Last Updated: February 2026
-- Database: SQLite 3.x (local development)
--
-- DESIGN PRINCIPLE: The `ads` table maps 1:1 with AdRotate CSV import format.
-- All AdRotate fields are stored directly, plus additional fields for our
-- internal workflows (network tracking, approval, performance metrics).
--
-- NOTE: This schema is SQLite-compatible for local development.
-- UUIDs are generated in application code before INSERT (not by the DB).
-- ============================================================================

-- ============================================================================
-- TABLE: advertisers
-- Stores advertiser/merchant information from all networks
-- ============================================================================

CREATE TABLE advertisers (
    -- Primary identifier (our internal ID) - UUID generated in Python
    id TEXT PRIMARY KEY NOT NULL,

    -- Network identification
    network TEXT NOT NULL CHECK (network IN ('flexoffers', 'awin', 'cj', 'impact')),
    network_advertiser_id TEXT NOT NULL,

    -- Advertiser details
    name TEXT NOT NULL,
    website_url TEXT,
    category TEXT,

    -- Performance metrics (denormalized, updated each sync)
    total_clicks INTEGER DEFAULT 0,
    total_revenue REAL DEFAULT 0,
    epc REAL DEFAULT 0,  -- Earnings per click (advertiser average)

    -- Sync tracking
    last_synced_at TEXT,
    raw_hash TEXT,  -- SHA-256 of API response for change detection

    -- Timestamps
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),

    -- Ensure unique advertiser per network
    CONSTRAINT uq_advertiser_network UNIQUE (network, network_advertiser_id)
);

-- Indexes for common queries
CREATE INDEX idx_advertisers_network ON advertisers(network);
CREATE INDEX idx_advertisers_name ON advertisers(name);

-- Trigger for updated_at
CREATE TRIGGER trg_advertisers_updated_at
    AFTER UPDATE ON advertisers
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE advertisers SET updated_at = datetime('now') WHERE id = NEW.id;
END;

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

    -- Primary identifier (our internal ID) - UUID generated in Python
    id TEXT PRIMARY KEY NOT NULL,

    -- Network identification (from canonical schema)
    network TEXT NOT NULL CHECK (network IN ('flexoffers', 'awin', 'cj', 'impact')),
    network_ad_id TEXT NOT NULL,           -- a.k.a. network_link_id

    -- Link to advertiser
    advertiser_id TEXT NOT NULL,

    -- Creative type (banner only for MVP)
    creative_type TEXT NOT NULL DEFAULT 'banner' CHECK (creative_type IN ('banner', 'text', 'html')),

    -- The actual tracking URL (used to construct bannercode)
    tracking_url TEXT NOT NULL,

    -- Destination URL if provided by network (where user lands after click)
    destination_url TEXT,

    -- Full HTML snippet if provided by network (alternative to constructing bannercode)
    html_snippet TEXT,

    -- Ad status from network's perspective
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'paused', 'expired')),

    -- Performance metrics (denormalized, updated each sync)
    clicks INTEGER DEFAULT 0,
    revenue REAL DEFAULT 0,
    epc REAL DEFAULT 0,  -- Earnings per click for this ad

    -- Sync tracking
    last_synced_at TEXT,
    raw_hash TEXT,  -- SHA-256 of API response for change detection

    -- Timestamps
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),

    -- =========================================================================
    -- ADROTATE FIELDS (exported directly to CSV)
    -- These map 1:1 with AdRotate import format
    -- =========================================================================

    -- advert_name: Constructed as [width]X[height]-[advertiser_id]-[advertiser_name]-[ad_id]-[campaign]
    advert_name TEXT NOT NULL,

    -- bannercode: HTML snippet for the ad
    -- Format: <a href="[tracking_url]" rel="sponsored"><img src="[image_url]" /></a>
    bannercode TEXT NOT NULL,

    -- imagetype: Always blank for hosted images (we don't self-host)
    imagetype TEXT DEFAULT '',

    -- image_url: Network-hosted banner image URL
    image_url TEXT NOT NULL,

    -- Dimensions (critical for placement matching)
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,

    -- Campaign name (used in advert_name construction)
    campaign_name TEXT DEFAULT 'General Promotion',

    -- Display settings (all default to Y for "show everywhere")
    enable_stats TEXT NOT NULL DEFAULT 'Y' CHECK (enable_stats IN ('Y', 'N')),
    show_everyone TEXT NOT NULL DEFAULT 'Y' CHECK (show_everyone IN ('Y', 'N')),
    show_desktop TEXT NOT NULL DEFAULT 'Y' CHECK (show_desktop IN ('Y', 'N')),
    show_mobile TEXT NOT NULL DEFAULT 'Y' CHECK (show_mobile IN ('Y', 'N')),
    show_tablet TEXT NOT NULL DEFAULT 'Y' CHECK (show_tablet IN ('Y', 'N')),
    show_ios TEXT NOT NULL DEFAULT 'Y' CHECK (show_ios IN ('Y', 'N')),
    show_android TEXT NOT NULL DEFAULT 'Y' CHECK (show_android IN ('Y', 'N')),

    -- Weight: How often to show this ad (2=rarely, 10=frequently)
    weight INTEGER NOT NULL DEFAULT 2 CHECK (weight IN (2, 4, 6, 8, 10)),

    -- Auto-management settings
    autodelete TEXT NOT NULL DEFAULT 'Y' CHECK (autodelete IN ('Y', 'N')),
    autodisable TEXT NOT NULL DEFAULT 'N' CHECK (autodisable IN ('Y', 'N')),

    -- Budget/rate settings (always 0, not using these features)
    budget INTEGER NOT NULL DEFAULT 0,
    click_rate INTEGER NOT NULL DEFAULT 0,
    impression_rate INTEGER NOT NULL DEFAULT 0,

    -- Geo-targeting
    state_required TEXT NOT NULL DEFAULT 'N' CHECK (state_required IN ('Y', 'N')),
    geo_cities TEXT NOT NULL DEFAULT 'a:0:{}',      -- PHP serialized empty array
    geo_states TEXT NOT NULL DEFAULT 'a:0:{}',      -- PHP serialized empty array
    geo_countries TEXT NOT NULL DEFAULT 'a:0:{}',   -- PHP serialized array of country codes

    -- Schedule (Unix timestamps)
    schedule_start INTEGER NOT NULL DEFAULT 0,       -- Start date (seconds since Jan 1, 1970)
    schedule_end INTEGER NOT NULL DEFAULT 2650941780, -- End date (far future = no end)

    -- =========================================================================
    -- CONSTRAINTS
    -- =========================================================================

    -- Ensure unique ad per network
    CONSTRAINT uq_ad_network UNIQUE (network, network_ad_id),

    -- Foreign key to advertisers
    CONSTRAINT fk_ads_advertiser FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE
);

-- Indexes for common query patterns
CREATE INDEX idx_ads_advertiser ON ads(advertiser_id);
CREATE INDEX idx_ads_network ON ads(network);
CREATE INDEX idx_ads_dimensions ON ads(width, height);
CREATE INDEX idx_ads_status ON ads(status);
CREATE INDEX idx_ads_weight ON ads(weight DESC);

-- Trigger for updated_at
CREATE TRIGGER trg_ads_updated_at
    AFTER UPDATE ON ads
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE ads SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- ============================================================================
-- TABLE: sites
-- Richard's WordPress sites where ads will be displayed
-- ============================================================================

CREATE TABLE sites (
    id TEXT PRIMARY KEY NOT NULL,

    -- Site identification
    name TEXT NOT NULL,
    domain TEXT NOT NULL UNIQUE,

    -- Site configuration
    is_active INTEGER NOT NULL DEFAULT 1,

    -- AdRotate connection (for future automated imports)
    wordpress_url TEXT,
    adrotate_api_key TEXT,

    -- Timestamps
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Trigger for updated_at
CREATE TRIGGER trg_sites_updated_at
    AFTER UPDATE ON sites
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE sites SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- ============================================================================
-- TABLE: placements
-- Ad slots on each site with specific dimensions
-- ============================================================================

CREATE TABLE placements (
    id TEXT PRIMARY KEY NOT NULL,

    -- Link to site
    site_id TEXT NOT NULL,

    -- Placement details
    name TEXT NOT NULL,  -- e.g., "Sidebar 300x250", "Leaderboard"
    description TEXT,

    -- Required dimensions (ads must match exactly)
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,

    -- Whether this placement is active
    is_active INTEGER NOT NULL DEFAULT 1,

    -- AdRotate group ID (for mapping during export)
    adrotate_group_id INTEGER,

    -- Timestamps
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),

    -- Ensure unique placement name per site
    CONSTRAINT uq_placement_site_name UNIQUE (site_id, name),

    -- Foreign key to sites
    CONSTRAINT fk_placements_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- Index for dimension matching
CREATE INDEX idx_placements_dimensions ON placements(width, height);
CREATE INDEX idx_placements_site ON placements(site_id);

-- Trigger for updated_at
CREATE TRIGGER trg_placements_updated_at
    AFTER UPDATE ON placements
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE placements SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- ============================================================================
-- TABLE: site_advertiser_rules
-- Per-site allow/deny rules for advertisers
-- ============================================================================

CREATE TABLE site_advertiser_rules (
    id TEXT PRIMARY KEY NOT NULL,

    -- Links
    site_id TEXT NOT NULL,
    advertiser_id TEXT NOT NULL,

    -- The rule (allowed, denied, or default)
    rule TEXT NOT NULL DEFAULT 'default' CHECK (rule IN ('allowed', 'denied', 'default')),

    -- Admin notes
    reason TEXT,

    -- Who made this decision and when
    decided_by TEXT,
    decided_at TEXT,

    -- Timestamps
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),

    -- One rule per site-advertiser pair
    CONSTRAINT uq_site_advertiser UNIQUE (site_id, advertiser_id),

    -- Foreign keys
    CONSTRAINT fk_site_advertiser_rules_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    CONSTRAINT fk_site_advertiser_rules_advertiser FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE
);

-- Indexes for filtering
CREATE INDEX idx_site_advertiser_rules_site ON site_advertiser_rules(site_id);
CREATE INDEX idx_site_advertiser_rules_advertiser ON site_advertiser_rules(advertiser_id);
CREATE INDEX idx_site_advertiser_rules_rule ON site_advertiser_rules(rule);

-- Trigger for updated_at
CREATE TRIGGER trg_site_advertiser_rules_updated_at
    AFTER UPDATE ON site_advertiser_rules
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE site_advertiser_rules SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- ============================================================================
-- TABLE: site_ads
-- Per-site approval status for each ad (many-to-many with approval workflow)
-- ============================================================================

CREATE TABLE site_ads (
    id TEXT PRIMARY KEY NOT NULL,

    -- Links
    site_id TEXT NOT NULL,
    ad_id TEXT NOT NULL,

    -- Approval workflow
    approval_status TEXT NOT NULL DEFAULT 'pending' CHECK (approval_status IN ('pending', 'approved', 'denied')),
    approval_reason TEXT,
    deny_is_permanent INTEGER NOT NULL DEFAULT 1,

    -- Who made this decision and when
    approved_by TEXT,
    approved_at TEXT,

    -- Export tracking
    last_exported_at TEXT,
    adrotate_ad_id INTEGER,  -- ID assigned by AdRotate after import

    -- Timestamps
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),

    -- One record per site-ad pair
    CONSTRAINT uq_site_ad UNIQUE (site_id, ad_id),

    -- Foreign keys
    CONSTRAINT fk_site_ads_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    CONSTRAINT fk_site_ads_ad FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
);

-- Indexes for export queries
CREATE INDEX idx_site_ads_site ON site_ads(site_id);
CREATE INDEX idx_site_ads_ad ON site_ads(ad_id);
CREATE INDEX idx_site_ads_approval ON site_ads(approval_status);
CREATE INDEX idx_site_ads_export ON site_ads(site_id, approval_status);

-- Trigger for updated_at
CREATE TRIGGER trg_site_ads_updated_at
    AFTER UPDATE ON site_ads
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE site_ads SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- ============================================================================
-- TABLE: sync_logs
-- Audit trail for sync operations
-- ============================================================================

CREATE TABLE sync_logs (
    id TEXT PRIMARY KEY NOT NULL,

    -- What was synced
    network TEXT NOT NULL CHECK (network IN ('flexoffers', 'awin', 'cj', 'impact')),

    -- Results
    advertisers_synced INTEGER DEFAULT 0,
    ads_synced INTEGER DEFAULT 0,
    ads_created INTEGER DEFAULT 0,
    ads_updated INTEGER DEFAULT 0,
    errors INTEGER DEFAULT 0,

    -- Timing
    started_at TEXT NOT NULL,
    completed_at TEXT,
    duration_seconds INTEGER,

    -- Error details if any
    error_message TEXT,

    -- Timestamps
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Index for recent logs
CREATE INDEX idx_sync_logs_network ON sync_logs(network);
CREATE INDEX idx_sync_logs_started ON sync_logs(started_at DESC);

-- ============================================================================
-- TABLE: export_logs
-- Audit trail for AdRotate CSV exports
-- ============================================================================

CREATE TABLE export_logs (
    id TEXT PRIMARY KEY NOT NULL,

    -- What was exported
    site_id TEXT NOT NULL,

    -- Results
    ads_exported INTEGER DEFAULT 0,

    -- The actual CSV (or path to it)
    csv_filename TEXT,

    -- Timing
    exported_at TEXT NOT NULL DEFAULT (datetime('now')),
    exported_by TEXT,

    -- Timestamps
    created_at TEXT NOT NULL DEFAULT (datetime('now')),

    -- Foreign key
    CONSTRAINT fk_export_logs_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

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
    AND s.is_active = 1
    AND (sar.rule IS NULL OR sar.rule != 'denied');

-- ============================================================================
-- SEED DATA: Richard's Sites
-- UUIDs generated using Python's uuid.uuid4()
-- ============================================================================

INSERT INTO sites (id, name, domain, wordpress_url) VALUES
    ('550e8400-e29b-41d4-a716-446655440001', 'RV Travel Life', 'rvtravellife.com', 'https://rvtravellife.com'),
    ('550e8400-e29b-41d4-a716-446655440002', 'This Old Campsite', 'thisoldcampsite.com', 'https://thisoldcampsite.com'),
    ('550e8400-e29b-41d4-a716-446655440003', 'Marine Part Shop', 'marinepartshop.com', 'https://marinepartshop.com'),
    ('550e8400-e29b-41d4-a716-446655440004', 'Powersports Part Shop', 'powersportspartshop.com', 'https://powersportspartshop.com'),
    ('550e8400-e29b-41d4-a716-446655440005', 'The Part Shops', 'thepartshops.com', 'https://thepartshops.com');

-- ============================================================================
-- SEED DATA: Common Placement Sizes
-- ============================================================================

-- RV Travel Life placements
INSERT INTO placements (id, site_id, name, width, height) VALUES
    ('660e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440001', 'Sidebar Medium Rectangle', 300, 250),
    ('660e8400-e29b-41d4-a716-446655440002', '550e8400-e29b-41d4-a716-446655440001', 'Leaderboard', 728, 90),
    ('660e8400-e29b-41d4-a716-446655440003', '550e8400-e29b-41d4-a716-446655440001', 'Wide Skyscraper', 160, 600),
    ('660e8400-e29b-41d4-a716-446655440004', '550e8400-e29b-41d4-a716-446655440001', 'Billboard', 970, 250);

-- This Old Campsite placements
INSERT INTO placements (id, site_id, name, width, height) VALUES
    ('660e8400-e29b-41d4-a716-446655440005', '550e8400-e29b-41d4-a716-446655440002', 'Sidebar Medium Rectangle', 300, 250),
    ('660e8400-e29b-41d4-a716-446655440006', '550e8400-e29b-41d4-a716-446655440002', 'Leaderboard', 728, 90),
    ('660e8400-e29b-41d4-a716-446655440007', '550e8400-e29b-41d4-a716-446655440002', 'Wide Skyscraper', 160, 600);

-- Marine Part Shop placements
INSERT INTO placements (id, site_id, name, width, height) VALUES
    ('660e8400-e29b-41d4-a716-446655440008', '550e8400-e29b-41d4-a716-446655440003', 'Sidebar Medium Rectangle', 300, 250),
    ('660e8400-e29b-41d4-a716-446655440009', '550e8400-e29b-41d4-a716-446655440003', 'Leaderboard', 728, 90);

-- Powersports Part Shop placements
INSERT INTO placements (id, site_id, name, width, height) VALUES
    ('660e8400-e29b-41d4-a716-446655440010', '550e8400-e29b-41d4-a716-446655440004', 'Sidebar Medium Rectangle', 300, 250),
    ('660e8400-e29b-41d4-a716-446655440011', '550e8400-e29b-41d4-a716-446655440004', 'Leaderboard', 728, 90);
