-- Schema for Affiliate Advertising Management System
-- 1. ADVERTISERS (from all networks)
CREATE TABLE advertisers (
  id SERIAL PRIMARY KEY,
  network VARCHAR(20) NOT NULL, -- 'flexoffers' | 'awin' | 'cj' | 'impact'
  network_program_id VARCHAR(100) UNIQUE NOT NULL,
  network_program_name VARCHAR(255) NOT NULL,
  status VARCHAR(20) DEFAULT 'active', -- 'active' | 'paused'
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- 2. ADS (canonical schema - this is YOUR schema, Mark!)
CREATE TABLE ads (
  id SERIAL PRIMARY KEY,
  advertiser_id INTEGER REFERENCES advertisers(id),
  
  -- Network metadata
  network VARCHAR(20) NOT NULL,
  network_link_id VARCHAR(100) UNIQUE NOT NULL,
  
  -- Creative details
  creative_type VARCHAR(20) NOT NULL, -- 'banner' | 'text' | 'html'
  name VARCHAR(255),
  html_snippet TEXT,
  image_url TEXT,
  tracking_url TEXT NOT NULL,
  destination_url TEXT,
  
  -- Size (CRITICAL for placement matching)
  width INTEGER NOT NULL,
  height INTEGER NOT NULL,
  
  -- Status
  status VARCHAR(20) DEFAULT 'active', -- 'active' | 'paused' | 'expired'
  start_date TIMESTAMP,
  end_date TIMESTAMP,
  
  -- Approval workflow
  approval_status VARCHAR(20) DEFAULT 'pending', -- 'pending' | 'approved' | 'denied'
  approval_reason TEXT,
  deny_is_permanent BOOLEAN DEFAULT TRUE,
  
  -- Change detection
  raw_hash VARCHAR(64), -- SHA-256 of raw API response
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- 3. SITES (WordPress sites)
CREATE TABLE sites (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  url VARCHAR(255) NOT NULL,
  niche VARCHAR(50), -- 'boating' | 'rv' | 'camping' | 'powersports'
  created_at TIMESTAMP DEFAULT NOW()
);

-- 4. SITE_ADVERTISER_RULES (allow/deny per site)
CREATE TABLE site_advertiser_rules (
  id SERIAL PRIMARY KEY,
  site_id INTEGER REFERENCES sites(id),
  advertiser_id INTEGER REFERENCES advertisers(id),
  rule_type VARCHAR(20) NOT NULL, -- 'allowed' | 'denied'
  created_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(site_id, advertiser_id)
);

-- 5. PLACEMENTS (ad slots on each site)
CREATE TABLE placements (
  id SERIAL PRIMARY KEY,
  site_id INTEGER REFERENCES sites(id),
  name VARCHAR(100), -- 'sidebar_main' | 'header_leaderboard'
  width INTEGER NOT NULL,
  height INTEGER NOT NULL,
  adrotate_group_id INTEGER, -- Maps to AdRotate group
  created_at TIMESTAMP DEFAULT NOW()
);

-- 6. PERFORMANCE_METRICS (from network reporting APIs)
CREATE TABLE performance_metrics (
  id SERIAL PRIMARY KEY,
  ad_id INTEGER REFERENCES ads(id),
  date DATE NOT NULL,
  clicks INTEGER DEFAULT 0,
  revenue DECIMAL(10,2) DEFAULT 0,
  conversions INTEGER DEFAULT 0,
  epc DECIMAL(10,4), -- Earnings per click
  created_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(ad_id, date)
);

-- Indexes for common queries
CREATE INDEX idx_ads_size ON ads(width, height);
CREATE INDEX idx_ads_advertiser ON ads(advertiser_id);
CREATE INDEX idx_ads_approval ON ads(approval_status);
CREATE INDEX idx_metrics_ad_date ON performance_metrics(ad_id, date);