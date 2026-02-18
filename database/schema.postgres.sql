-- ============================================================================
-- AFFILIATE AD SYNC SYSTEM - DATABASE SCHEMA
-- Version: 1.1.0
-- Last Updated: January 28, 2026
-- Database: PostgreSQL (Supabase)
-- 
-- DESIGN PRINCIPLE: The `ads` table maps 1:1 with AdRotate CSV import format.
-- All AdRotate fields are stored directly, plus additional fields for our
-- internal workflows (network tracking, approval, performance metrics).
-- ============================================================================

-- Enable UUID extension for generating unique identifiers
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================================
-- ENUM TYPES
-- ============================================================================

-- The four affiliate networks we integrate with
CREATE TYPE network_type AS ENUM ('flexoffers', 'awin', 'cj', 'impact');

-- Types of ad creatives (banner images only for MVP, text/html deferred)
CREATE TYPE creative_type AS ENUM ('banner', 'text', 'html');

-- Ad status from the network's perspective
CREATE TYPE ad_status AS ENUM ('active', 'paused', 'expired');

-- Approval status for the admin workflow
CREATE TYPE approval_status AS ENUM ('pending', 'approved', 'denied');

-- Site-level advertiser rules
CREATE TYPE advertiser_rule AS ENUM ('allowed', 'denied', 'default');

-- ============================================================================
-- TABLE: advertisers
-- Stores advertiser/merchant information from all networks
-- ============================================================================

CREATE TABLE advertisers (
    -- Primary identifier (our internal ID)
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- Network identification
    network network_type NOT NULL,
    network_advertiser_id TEXT NOT NULL,
    
    -- Advertiser details
    name TEXT NOT NULL,
    website_url TEXT,
    category TEXT,
    
    -- Performance metrics (denormalized, updated each sync)
    total_clicks INTEGER DEFAULT 0,
    total_revenue NUMERIC(12, 2) DEFAULT 0,
    epc NUMERIC(10, 4) DEFAULT 0,  -- Earnings per click (advertiser average)
    
    -- Sync tracking
    last_synced_at TIMESTAMPTZ,
    raw_hash TEXT,  -- SHA-256 of API response for change detection
    
    -- Timestamps
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    -- Ensure unique advertiser per network
    CONSTRAINT uq_advertiser_network UNIQUE (network, network_advertiser_id)
);

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
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- Network identification (from canonical schema)
    network network_type NOT NULL,
    network_ad_id TEXT NOT NULL,           -- a.k.a. network_link_id
    
    -- Link to advertiser
    advertiser_id UUID NOT NULL REFERENCES advertisers(id) ON DELETE CASCADE,
    
    -- Creative type (banner only for MVP)
    creative_type creative_type NOT NULL DEFAULT 'banner',
    
    -- The actual tracking URL (used to construct bannercode)
    tracking_url TEXT NOT NULL,
    
    -- Destination URL if provided by network (where user lands after click)
    destination_url TEXT,
    
    -- Full HTML snippet if provided by network (alternative to constructing bannercode)
    html_snippet TEXT,
    
    -- Ad status from network's perspective
    status ad_status NOT NULL DEFAULT 'active',
    
    -- Performance metrics (denormalized, updated each sync)
    clicks INTEGER DEFAULT 0,
    revenue NUMERIC(12, 2) DEFAULT 0,
    epc NUMERIC(10, 4) DEFAULT 0,  -- Earnings per click for this ad
    
    -- Sync tracking
    last_synced_at TIMESTAMPTZ,
    raw_hash TEXT,  -- SHA-256 of API response for change detection
    
    -- Timestamps
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    -- =========================================================================
    -- ADROTATE FIELDS (exported directly to CSV)
    -- These map 1:1 with AdRotate import format
    -- =========================================================================
    
    -- advert_name: Constructed as [width]X[height]-[advertiser_id]-[advertiser_name]-[ad_id]-[campaign]
    -- We store the components separately and construct at export time, OR store pre-built:
    advert_name TEXT NOT NULL,
    
    -- bannercode: HTML snippet for the ad
    -- Format: <a href="[tracking_url]" rel="sponsored"><img src="[image_url]" /></a>
    -- Stored HTML-encoded for CSV export
    bannercode TEXT NOT NULL,
    
    -- imagetype: Always blank for hosted images (we don't self-host)
    imagetype TEXT DEFAULT '',
    
    -- image_url: Network-hosted banner image URL (NULL for text links or HTML-only ads)
    image_url TEXT,
    
    -- Dimensions (critical for placement matching)
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,
    
    -- Campaign name (used in advert_name construction)
    campaign_name TEXT DEFAULT 'General Promotion',
    
    -- Display settings (all default to Y for "show everywhere")
    enable_stats CHAR(1) NOT NULL DEFAULT 'Y' CHECK (enable_stats IN ('Y', 'N')),
    show_everyone CHAR(1) NOT NULL DEFAULT 'Y' CHECK (show_everyone IN ('Y', 'N')),
    show_desktop CHAR(1) NOT NULL DEFAULT 'Y' CHECK (show_desktop IN ('Y', 'N')),
    show_mobile CHAR(1) NOT NULL DEFAULT 'Y' CHECK (show_mobile IN ('Y', 'N')),
    show_tablet CHAR(1) NOT NULL DEFAULT 'Y' CHECK (show_tablet IN ('Y', 'N')),
    show_ios CHAR(1) NOT NULL DEFAULT 'Y' CHECK (show_ios IN ('Y', 'N')),
    show_android CHAR(1) NOT NULL DEFAULT 'Y' CHECK (show_android IN ('Y', 'N')),
    
    -- Weight: How often to show this ad (2=rarely, 10=frequently)
    -- Valid values: 2, 4, 6, 8, 10
    weight INTEGER NOT NULL DEFAULT 2 CHECK (weight IN (2, 4, 6, 8, 10)),
    
    -- Auto-management settings
    autodelete CHAR(1) NOT NULL DEFAULT 'Y' CHECK (autodelete IN ('Y', 'N')),
    autodisable CHAR(1) NOT NULL DEFAULT 'N' CHECK (autodisable IN ('Y', 'N')),
    
    -- Budget/rate settings (always 0, not using these features)
    budget INTEGER NOT NULL DEFAULT 0,
    click_rate INTEGER NOT NULL DEFAULT 0,
    impression_rate INTEGER NOT NULL DEFAULT 0,
    
    -- Geo-targeting
    state_required CHAR(1) NOT NULL DEFAULT 'N' CHECK (state_required IN ('Y', 'N')),
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
    CONSTRAINT uq_ad_network UNIQUE (network, network_ad_id)
);

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
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- Site identification
    name TEXT NOT NULL,
    domain TEXT NOT NULL UNIQUE,
    
    -- Site configuration
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    
    -- AdRotate connection (for future automated imports)
    wordpress_url TEXT,
    adrotate_api_key TEXT,
    
    -- Timestamps
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ============================================================================
-- TABLE: placements
-- Ad slots on each site with specific dimensions
-- ============================================================================

CREATE TABLE placements (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- Link to site
    site_id UUID NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    
    -- Placement details
    name TEXT NOT NULL,  -- e.g., "Sidebar 300x250", "Leaderboard"
    description TEXT,
    
    -- Required dimensions (ads must match exactly)
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,
    
    -- Whether this placement is active
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    
    -- AdRotate group ID (for mapping during export)
    adrotate_group_id INTEGER,
    
    -- Timestamps
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    -- Ensure unique placement name per site
    CONSTRAINT uq_placement_site_name UNIQUE (site_id, name)
);

-- Index for dimension matching
CREATE INDEX idx_placements_dimensions ON placements(width, height);
CREATE INDEX idx_placements_site ON placements(site_id);

-- ============================================================================
-- TABLE: site_advertiser_rules
-- Per-site allow/deny rules for advertisers
-- ============================================================================

CREATE TABLE site_advertiser_rules (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- Links
    site_id UUID NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    advertiser_id UUID NOT NULL REFERENCES advertisers(id) ON DELETE CASCADE,
    
    -- The rule (allowed, denied, or default)
    rule advertiser_rule NOT NULL DEFAULT 'default',
    
    -- Admin notes
    reason TEXT,
    
    -- Who made this decision and when
    decided_by TEXT,
    decided_at TIMESTAMPTZ,
    
    -- Timestamps
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    -- One rule per site-advertiser pair
    CONSTRAINT uq_site_advertiser UNIQUE (site_id, advertiser_id)
);

-- Indexes for filtering
CREATE INDEX idx_site_advertiser_rules_site ON site_advertiser_rules(site_id);
CREATE INDEX idx_site_advertiser_rules_advertiser ON site_advertiser_rules(advertiser_id);
CREATE INDEX idx_site_advertiser_rules_rule ON site_advertiser_rules(rule);

-- ============================================================================
-- TABLE: site_ads
-- Per-site approval status for each ad (many-to-many with approval workflow)
-- ============================================================================

CREATE TABLE site_ads (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- Links
    site_id UUID NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    ad_id UUID NOT NULL REFERENCES ads(id) ON DELETE CASCADE,
    
    -- Approval workflow
    approval_status approval_status NOT NULL DEFAULT 'pending',
    approval_reason TEXT,
    deny_is_permanent BOOLEAN NOT NULL DEFAULT TRUE,
    
    -- Who made this decision and when
    approved_by TEXT,
    approved_at TIMESTAMPTZ,
    
    -- Export tracking
    last_exported_at TIMESTAMPTZ,
    adrotate_ad_id INTEGER,  -- ID assigned by AdRotate after import
    
    -- Timestamps
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    -- One record per site-ad pair
    CONSTRAINT uq_site_ad UNIQUE (site_id, ad_id)
);

-- Indexes for export queries
CREATE INDEX idx_site_ads_site ON site_ads(site_id);
CREATE INDEX idx_site_ads_ad ON site_ads(ad_id);
CREATE INDEX idx_site_ads_approval ON site_ads(approval_status);
CREATE INDEX idx_site_ads_export ON site_ads(site_id, approval_status) 
    WHERE approval_status = 'approved';

-- ============================================================================
-- TABLE: sync_logs
-- Audit trail for sync operations
-- ============================================================================

CREATE TABLE sync_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- What was synced
    network network_type NOT NULL,
    
    -- Results
    advertisers_synced INTEGER DEFAULT 0,
    ads_synced INTEGER DEFAULT 0,
    ads_created INTEGER DEFAULT 0,
    ads_updated INTEGER DEFAULT 0,
    errors INTEGER DEFAULT 0,
    
    -- Timing
    started_at TIMESTAMPTZ NOT NULL,
    completed_at TIMESTAMPTZ,
    duration_seconds INTEGER,
    
    -- Error details if any
    error_message TEXT,
    
    -- Timestamps
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Index for recent logs
CREATE INDEX idx_sync_logs_network ON sync_logs(network);
CREATE INDEX idx_sync_logs_started ON sync_logs(started_at DESC);

-- ============================================================================
-- TABLE: export_logs
-- Audit trail for AdRotate CSV exports
-- ============================================================================

CREATE TABLE export_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- What was exported
    site_id UUID NOT NULL REFERENCES sites(id) ON DELETE CASCADE,
    
    -- Results
    ads_exported INTEGER DEFAULT 0,
    
    -- The actual CSV (or path to it)
    csv_filename TEXT,
    
    -- Timing
    exported_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    exported_by TEXT,
    
    -- Timestamps
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
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
    AND s.is_active = TRUE
    AND (sar.rule IS NULL OR sar.rule != 'denied');

-- ============================================================================
-- SEED DATA: Richard's Sites
-- ============================================================================

INSERT INTO sites (name, domain, wordpress_url) VALUES
    ('RV Travel Life', 'rvtravellife.com', 'https://rvtravellife.com'),
    ('This Old Campsite', 'thisoldcampsite.com', 'https://thisoldcampsite.com'),
    ('Marine Part Shop', 'marinepartshop.com', 'https://marinepartshop.com'),
    ('Powersports Part Shop', 'powersportspartshop.com', 'https://powersportspartshop.com'),
    ('The Part Shops', 'thepartshops.com', 'https://thepartshops.com');

-- ============================================================================
-- SEED DATA: Common Placement Sizes
-- ============================================================================

DO $$
DECLARE
    rv_site_id UUID;
    campsite_id UUID;
    marine_id UUID;
    powersports_id UUID;
BEGIN
    SELECT id INTO rv_site_id FROM sites WHERE domain = 'rvtravellife.com';
    SELECT id INTO campsite_id FROM sites WHERE domain = 'thisoldcampsite.com';
    SELECT id INTO marine_id FROM sites WHERE domain = 'marinepartshop.com';
    SELECT id INTO powersports_id FROM sites WHERE domain = 'powersportspartshop.com';
    
    -- RV Travel Life placements
    INSERT INTO placements (site_id, name, width, height) VALUES
        (rv_site_id, 'Sidebar Medium Rectangle', 300, 250),
        (rv_site_id, 'Leaderboard', 728, 90),
        (rv_site_id, 'Wide Skyscraper', 160, 600),
        (rv_site_id, 'Billboard', 970, 250);
    
    -- This Old Campsite placements
    INSERT INTO placements (site_id, name, width, height) VALUES
        (campsite_id, 'Sidebar Medium Rectangle', 300, 250),
        (campsite_id, 'Leaderboard', 728, 90),
        (campsite_id, 'Wide Skyscraper', 160, 600);
    
    -- Marine Part Shop placements
    INSERT INTO placements (site_id, name, width, height) VALUES
        (marine_id, 'Sidebar Medium Rectangle', 300, 250),
        (marine_id, 'Leaderboard', 728, 90);
    
    -- Powersports Part Shop placements
    INSERT INTO placements (site_id, name, width, height) VALUES
        (powersports_id, 'Sidebar Medium Rectangle', 300, 250),
        (powersports_id, 'Leaderboard', 728, 90);
END $$;

-- ============================================================================
-- FUNCTION: Update timestamps automatically
-- ============================================================================

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Apply to all tables with updated_at
CREATE TRIGGER update_advertisers_updated_at BEFORE UPDATE ON advertisers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_ads_updated_at BEFORE UPDATE ON ads
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_sites_updated_at BEFORE UPDATE ON sites
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_placements_updated_at BEFORE UPDATE ON placements
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_site_advertiser_rules_updated_at BEFORE UPDATE ON site_advertiser_rules
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_site_ads_updated_at BEFORE UPDATE ON site_ads
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================================
-- COMMENTS
-- ============================================================================

COMMENT ON TABLE advertisers IS 'Companies/merchants whose products we advertise. Synced from affiliate networks.';
COMMENT ON TABLE ads IS 'Individual ad creatives. Maps 1:1 with AdRotate CSV import format, plus internal workflow fields.';
COMMENT ON TABLE sites IS 'Richard''s WordPress websites where ads are displayed.';
COMMENT ON TABLE placements IS 'Specific ad slots on each site with fixed dimensions (e.g., 300x250 sidebar).';
COMMENT ON TABLE site_advertiser_rules IS 'Per-site rules for allowing or denying specific advertisers.';
COMMENT ON TABLE site_ads IS 'Links ads to sites with approval status. Controls which ads export to which sites.';
COMMENT ON TABLE sync_logs IS 'Audit trail of sync operations from affiliate networks.';
COMMENT ON TABLE export_logs IS 'Audit trail of CSV exports to AdRotate.';