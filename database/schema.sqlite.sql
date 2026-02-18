-- ============================================================================
-- Affiliate Ad Sync System — Database Schema v2.0 (SQLite)
-- February 2026
-- Author: Mark Cena
--
-- SQLite-compatible version of the v2 MySQL schema for local development.
-- Run via: python setup-dev-db.py --reset
-- ============================================================================

-- ============================================================================
-- TABLE: sites
-- Purpose: Your WordPress websites.
-- ============================================================================

CREATE TABLE sites (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    name            TEXT    NOT NULL,
    domain          TEXT    NOT NULL UNIQUE,
    wordpress_url   TEXT    NULL,
    is_active       INTEGER NOT NULL DEFAULT 1,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TRIGGER trg_sites_updated_at
    AFTER UPDATE ON sites
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE sites SET updated_at = datetime('now') WHERE id = NEW.id;
END;


-- ============================================================================
-- TABLE: advertisers
-- Purpose: Companies/brands whose products we promote through affiliate programs.
--
-- Key v2 changes:
--   - Added default_weight (advertiser-level weight, inheritable by ads)
--   - Added is_active for soft-delete when advertiser disappears from network
--   - Added commission_rate for network comparison
-- ============================================================================

CREATE TABLE advertisers (
    id                      INTEGER PRIMARY KEY AUTOINCREMENT,
    network                 TEXT    NOT NULL CHECK (network IN ('flexoffers', 'awin', 'cj', 'impact')),
    network_advertiser_id   TEXT    NOT NULL,
    name                    TEXT    NOT NULL,
    website_url             TEXT    NULL,
    category                TEXT    NULL,

    -- Performance metrics
    total_clicks            INTEGER NOT NULL DEFAULT 0,
    total_revenue           REAL    NOT NULL DEFAULT 0.00,
    epc                     REAL    NOT NULL DEFAULT 0.0000,
    commission_rate         TEXT    NULL,

    -- Weight: Richard assigns this in the advertiser grid.
    -- All ads inherit this weight unless overridden. NULL = not yet assigned.
    default_weight          INTEGER NULL,

    -- Soft-delete: when advertiser disappears from network sync
    is_active               INTEGER NOT NULL DEFAULT 1,

    -- Sync tracking
    last_synced_at          TEXT    NULL,
    raw_hash                TEXT    NULL,

    created_at              TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at              TEXT    NOT NULL DEFAULT (datetime('now')),

    CONSTRAINT uq_network_advertiser UNIQUE (network, network_advertiser_id)
);

CREATE INDEX idx_advertisers_name ON advertisers(name);
CREATE INDEX idx_advertisers_network ON advertisers(network);
CREATE INDEX idx_advertisers_is_active ON advertisers(is_active);
CREATE INDEX idx_advertisers_epc ON advertisers(epc);

CREATE TRIGGER trg_advertisers_updated_at
    AFTER UPDATE ON advertisers
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE advertisers SET updated_at = datetime('now') WHERE id = NEW.id;
END;


-- ============================================================================
-- TABLE: ads
-- Purpose: Individual ad creatives pulled from affiliate networks.
--
-- Key v2 changes:
--   - approval_status + approval_reason on the ad (global, not per-site)
--   - weight_override replaces weight (nullable, inherits from advertiser)
--   - Stale ads are HARD DELETED during sync
-- ============================================================================

CREATE TABLE ads (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,

    -- Internal fields
    network             TEXT    NOT NULL CHECK (network IN ('flexoffers', 'awin', 'cj', 'impact')),
    network_ad_id       TEXT    NOT NULL,
    advertiser_id       INTEGER NOT NULL,
    creative_type       TEXT    NOT NULL DEFAULT 'banner' CHECK (creative_type IN ('banner', 'text', 'html')),
    tracking_url        TEXT    NOT NULL,
    destination_url     TEXT    NULL,
    html_snippet        TEXT    NULL,
    status              TEXT    NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'paused', 'expired')),

    -- Ad-level performance
    clicks              INTEGER NOT NULL DEFAULT 0,
    revenue             REAL    NOT NULL DEFAULT 0.00,
    epc                 REAL    NOT NULL DEFAULT 0.0000,

    -- Approval: GLOBAL (not per-site). Ads default to approved.
    approval_status     TEXT    NOT NULL DEFAULT 'approved' CHECK (approval_status IN ('approved', 'denied')),
    approval_reason     TEXT    NULL,

    -- Weight override: if set, takes priority over advertiser default_weight.
    weight_override     INTEGER NULL,

    -- Sync tracking
    last_synced_at      TEXT    NULL,
    raw_hash            TEXT    NULL,

    -- AdRotate fields (exported directly to CSV)
    advert_name         TEXT    NOT NULL,
    bannercode          TEXT    NOT NULL,
    imagetype           TEXT    NOT NULL DEFAULT '',
    image_url           TEXT    NULL,
    width               INTEGER NOT NULL,
    height              INTEGER NOT NULL,
    campaign_name       TEXT    NULL DEFAULT 'General Promotion',
    enable_stats        TEXT    NOT NULL DEFAULT 'Y' CHECK (enable_stats IN ('Y', 'N')),
    show_everyone       TEXT    NOT NULL DEFAULT 'Y' CHECK (show_everyone IN ('Y', 'N')),
    show_desktop        TEXT    NOT NULL DEFAULT 'Y' CHECK (show_desktop IN ('Y', 'N')),
    show_mobile         TEXT    NOT NULL DEFAULT 'Y' CHECK (show_mobile IN ('Y', 'N')),
    show_tablet         TEXT    NOT NULL DEFAULT 'Y' CHECK (show_tablet IN ('Y', 'N')),
    show_ios            TEXT    NOT NULL DEFAULT 'Y' CHECK (show_ios IN ('Y', 'N')),
    show_android        TEXT    NOT NULL DEFAULT 'Y' CHECK (show_android IN ('Y', 'N')),
    autodelete          TEXT    NOT NULL DEFAULT 'Y' CHECK (autodelete IN ('Y', 'N')),
    autodisable         TEXT    NOT NULL DEFAULT 'N' CHECK (autodisable IN ('Y', 'N')),
    budget              INTEGER NOT NULL DEFAULT 0,
    click_rate          INTEGER NOT NULL DEFAULT 0,
    impression_rate     INTEGER NOT NULL DEFAULT 0,
    state_required      TEXT    NOT NULL DEFAULT 'N' CHECK (state_required IN ('Y', 'N')),
    geo_cities          TEXT    NOT NULL DEFAULT 'a:0:{}',
    geo_states          TEXT    NOT NULL DEFAULT 'a:0:{}',
    geo_countries       TEXT    NOT NULL DEFAULT 'a:0:{}',
    schedule_start      INTEGER NOT NULL DEFAULT 0,
    schedule_end        INTEGER NOT NULL DEFAULT 2650941780,

    created_at          TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at          TEXT    NOT NULL DEFAULT (datetime('now')),

    CONSTRAINT uq_network_ad UNIQUE (network, network_ad_id),
    CONSTRAINT fk_ads_advertiser FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE
);

CREATE INDEX idx_ads_advertiser ON ads(advertiser_id);
CREATE INDEX idx_ads_network ON ads(network);
CREATE INDEX idx_ads_dimensions ON ads(width, height);
CREATE INDEX idx_ads_approval ON ads(approval_status);
CREATE INDEX idx_ads_status ON ads(status);

CREATE TRIGGER trg_ads_updated_at
    AFTER UPDATE ON ads
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE ads SET updated_at = datetime('now') WHERE id = NEW.id;
END;


-- ============================================================================
-- TABLE: placements
-- Purpose: Ad slots on each website with exact dimensions.
-- ============================================================================

CREATE TABLE placements (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id             INTEGER NOT NULL,
    name                TEXT    NOT NULL,
    description         TEXT    NULL,
    width               INTEGER NOT NULL,
    height              INTEGER NOT NULL,
    is_active           INTEGER NOT NULL DEFAULT 1,
    adrotate_group_id   INTEGER NULL,

    created_at          TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at          TEXT    NOT NULL DEFAULT (datetime('now')),

    CONSTRAINT fk_placements_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    CONSTRAINT uq_site_dimensions UNIQUE (site_id, width, height)
);

CREATE INDEX idx_placements_site ON placements(site_id);
CREATE INDEX idx_placements_dimensions ON placements(width, height);

CREATE TRIGGER trg_placements_updated_at
    AFTER UPDATE ON placements
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE placements SET updated_at = datetime('now') WHERE id = NEW.id;
END;


-- ============================================================================
-- TABLE: site_advertiser_rules
-- Purpose: Controls which advertisers are allowed or blocked on each site.
-- ============================================================================

CREATE TABLE site_advertiser_rules (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id             INTEGER NOT NULL,
    advertiser_id       INTEGER NOT NULL,
    rule                TEXT    NOT NULL DEFAULT 'default' CHECK (rule IN ('allowed', 'denied', 'default')),
    reason              TEXT    NULL,

    created_at          TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at          TEXT    NOT NULL DEFAULT (datetime('now')),

    CONSTRAINT fk_rules_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    CONSTRAINT fk_rules_advertiser FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
    CONSTRAINT uq_site_advertiser UNIQUE (site_id, advertiser_id)
);

CREATE INDEX idx_site_advertiser_rules_site ON site_advertiser_rules(site_id);
CREATE INDEX idx_site_advertiser_rules_advertiser ON site_advertiser_rules(advertiser_id);
CREATE INDEX idx_site_advertiser_rules_rule ON site_advertiser_rules(rule);

CREATE TRIGGER trg_site_advertiser_rules_updated_at
    AFTER UPDATE ON site_advertiser_rules
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE site_advertiser_rules SET updated_at = datetime('now') WHERE id = NEW.id;
END;


-- ============================================================================
-- TABLE: sync_logs
-- Purpose: Audit trail of every sync run.
-- ============================================================================

CREATE TABLE sync_logs (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    network             TEXT    NOT NULL CHECK (network IN ('flexoffers', 'awin', 'cj', 'impact')),
    site_domain         TEXT    NULL,
    started_at          TEXT    NOT NULL DEFAULT (datetime('now')),
    completed_at        TEXT    NULL,
    status              TEXT    NOT NULL DEFAULT 'running' CHECK (status IN ('running', 'success', 'failed')),
    advertisers_synced  INTEGER NOT NULL DEFAULT 0,
    ads_synced          INTEGER NOT NULL DEFAULT 0,
    ads_deleted         INTEGER NOT NULL DEFAULT 0,
    error_message       TEXT    NULL
);

CREATE INDEX idx_sync_logs_network ON sync_logs(network);
CREATE INDEX idx_sync_logs_status ON sync_logs(status);
CREATE INDEX idx_sync_logs_started ON sync_logs(started_at);


-- ============================================================================
-- TABLE: export_logs
-- Purpose: Audit trail of every CSV export.
-- ============================================================================

CREATE TABLE export_logs (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id             INTEGER NOT NULL,
    filename            TEXT    NOT NULL,
    ads_exported        INTEGER NOT NULL DEFAULT 0,
    exported_at         TEXT    NOT NULL DEFAULT (datetime('now')),
    exported_by         TEXT    NULL,

    CONSTRAINT fk_export_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE INDEX idx_export_logs_site ON export_logs(site_id);
CREATE INDEX idx_export_logs_exported_at ON export_logs(exported_at);


-- ============================================================================
-- VIEW: v_exportable_ads
-- Purpose: For a given site, returns all ads ready for CSV export with
--          the correct weight calculated.
--
-- Weight logic:
--   1. If ad has weight_override -> use that
--   2. Else if advertiser has default_weight -> use that
--   3. Else -> default to 2 (lowest)
--
-- Usage: SELECT * FROM v_exportable_ads WHERE site_id = 1 AND width = 300 AND height = 250;
-- ============================================================================

CREATE VIEW v_exportable_ads AS
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
    sar.rule = 'allowed'
    AND adv.is_active = 1
    AND a.approval_status = 'approved'
    AND a.status = 'active';


-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Sites
INSERT INTO sites (name, domain, wordpress_url, is_active) VALUES
    ('RV Travel Life',          'rvtravellife.com',             'https://www.rvtravellife.com',         1),
    ('This Old Campsite',       'thisoldcampsite.com',          'https://thisoldcampsite.com',           1),
    ('Marine Part Shop',        'marinepartshop.com',           'https://marinepartshop.com',            1),
    ('Powersports Part Shop',   'powersportspartshop.com',      'https://powersportspartshop.com',       1),
    ('The Part Shops',          'thepartshops.com',             'https://thepartshops.com',              1);

-- Common placements (standard IAB sizes)
INSERT INTO placements (site_id, name, width, height, is_active) VALUES
    -- RV Travel Life (id=1)
    (1, 'Medium Rectangle',     300, 250, 1),
    (1, 'Leaderboard',          728, 90,  1),
    (1, 'Wide Skyscraper',      160, 600, 1),
    (1, 'Billboard',            970, 250, 1),
    -- This Old Campsite (id=2)
    (2, 'Medium Rectangle',     300, 250, 1),
    (2, 'Leaderboard',          728, 90,  1),
    (2, 'Wide Skyscraper',      160, 600, 1),
    (2, 'Billboard',            970, 250, 1),
    -- Marine Part Shop (id=3)
    (3, 'Medium Rectangle',     300, 250, 1),
    (3, 'Leaderboard',          728, 90,  1),
    (3, 'Wide Skyscraper',      160, 600, 1),
    (3, 'Billboard',            970, 250, 1),
    -- Powersports Part Shop (id=4)
    (4, 'Medium Rectangle',     300, 250, 1),
    (4, 'Leaderboard',          728, 90,  1),
    (4, 'Wide Skyscraper',      160, 600, 1),
    (4, 'Billboard',            970, 250, 1),
    -- The Part Shops (id=5)
    (5, 'Medium Rectangle',     300, 250, 1),
    (5, 'Leaderboard',          728, 90,  1),
    (5, 'Wide Skyscraper',      160, 600, 1),
    (5, 'Billboard',            970, 250, 1);
