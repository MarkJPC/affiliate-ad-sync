**Schema Documentation**

Affiliate Ad Sync System — Database Documentation

Version 2.0  |  February 17, 2026  |  Author: Mark Cena

*Updated based on Richard’s feedback from February 16, 2026 meeting*

# **What This Document Explains**

This document describes how we store and organize all the advertising data in our system.

Our system pulls ads from four affiliate networks (FlexOffers, Awin, Commission Junction, and Impact), stores them in one central database, lets you approve which ads appear on which websites, and then exports the approved ads to WordPress so they can start showing to visitors.

For how the filtering and approval workflow operates, see the companion Filtering Rules document.

# **Overview: The Five Main Tables**

| **Table** | **What It Stores** | **Real-World Analogy** |
| --- | --- | --- |
| **advertisers** | Companies whose products we promote | A rolodex of all the brands we work with |
| **ads** | Individual banner images from those companies | The actual ad creatives / artwork |
| **sites** | Your WordPress websites | Your portfolio of properties |
| **placements** | Specific ad slots on each website | The spaces on each page where ads appear |
| **site_advertiser_rules** | Which brands can advertise on which sites | A bouncer list — who’s allowed where |

**Note (v2 change):** The site_ads table from v1 has been removed. Ad approval is now global — stored directly on the ads table. See the Filtering Rules document for details.

# **Table: advertisers**

**Purpose:** Stores information about each company/brand whose products we’re promoting through affiliate programs.

## **Fields**

| **Field** | **What It Means** | **Required?** | **Example / Default** |
| --- | --- | --- | --- |
| **id** | Auto-generated unique identifier | Auto | 1, 2, 3... |
| **network** | Which affiliate network this advertiser comes from | Yes | flexoffers, awin, cj, impact |
| **network_advertiser_id** | The ID the network uses for this advertiser | Yes | 158856 |
| **name** | The company/brand name | Yes | Bass Pro Shops |
| **website_url** | The advertiser’s website | No | https://basspro.com |
| **category** | What type of products they sell | No | Outdoor Recreation |
| **total_clicks** | How many times people clicked their ads | No (default 0) | 1,523 |
| **total_revenue** | How much money we’ve earned from them | No (default 0) | $2,450.00 |
| **epc** | Earnings Per Click — how valuable each click is | No (default 0) | $1.61 |
| **commission_rate** | Commission info from the network | No | 8% or $5 flat |
| **default_weight** | AdRotate weight assigned by Richard (2/4/6/8/10). All ads from this advertiser inherit this weight unless overridden at the ad level. | No (NULL) | 8 |
| **is_active** | Is this advertiser still active on the network? Set to FALSE when advertiser disappears from sync (soft-delete). | Yes (default TRUE) | TRUE |
| **last_synced_at** | When we last pulled fresh data from the network | No | Jan 27, 2026 3:00 PM |
| **raw_hash** | SHA-256 fingerprint to detect if anything changed since last sync | No | (technical use only) |

## **Key Design Decisions (v2)**

- **default_weight:** Richard assigns this in the advertiser grid. All of an advertiser’s ads inherit this weight. If an individual ad needs a different weight, it can be overridden on the ad record.
- **Soft-delete via is_active:** When an advertiser disappears from a network sync, we don’t delete the record — we set is_active = FALSE. This preserves any site rules Richard already configured. If the advertiser reappears, rules are intact.
- **commission_rate:** The same brand can appear on multiple networks (e.g., Camping World on FlexOffers and Awin). Each gets its own row. Richard can compare commission rates in the grid to see which network pays more.
- **Duplicates across networks:** Handled at the application layer. The dashboard groups advertisers with the same name visually. Site rules apply to all rows sharing the brand.

# **Table: ads**

**Purpose:** The heart of the system. Stores every ad creative pulled from the affiliate networks.

**Key design principle:** The AdRotate fields in this table map 1:1 to the AdRotate CSV import format. When we export to WordPress, we simply SELECT the AdRotate columns and output them as CSV.

**Stale data:** Ads that disappear from a network sync are hard deleted. We only care about live ads. If the network brings an ad back later, it gets re-imported as a new record.

## **Internal Fields (Not Exported to AdRotate)**

| **Field** | **What It Means** | **Required?** | **Example / Default** |
| --- | --- | --- | --- |
| **id** | Auto-generated unique identifier | Auto | 1, 2, 3... |
| **network** | Which network this ad comes from | Yes | flexoffers, awin, cj, impact |
| **network_ad_id** | The ID the network uses for this ad | Yes | 471943 |
| **advertiser_id** | Links to the advertiser who owns this ad | Yes | (links to advertisers) |
| **creative_type** | What kind of ad this is | Yes (default banner) | banner, text, html |
| **tracking_url** | The special link that tracks clicks | Yes | https://track.flexlinkspro.com/... |
| **destination_url** | Where the visitor lands after clicking | No | https://basspro.com/sale |
| **html_snippet** | Full HTML code if the network provides it | No | <a href="..."><img.../></a> |
| **status** | Is this ad currently running? | Yes (default active) | active, paused, expired |
| **clicks** | How many clicks this ad has gotten | No (default 0) | 234 |
| **revenue** | How much money this ad has earned | No (default 0) | $567.89 |
| **epc** | Earnings per click for this specific ad | No (default 0) | $2.43 |
| **approval_status** | Global approval: approved or denied. Defaults to approved (MVP approach). | Yes (default approved) | approved, denied |
| **approval_reason** | Why this ad was denied | No | Blurry image, no text |
| **weight_override** | Override the advertiser’s default weight for this specific ad (2/4/6/8/10). NULL = use advertiser default. | No (NULL) | 10 |
| **last_synced_at** | When we last updated this from the network | No | Jan 27, 2026 |
| **raw_hash** | SHA-256 fingerprint to detect changes | No | (technical use) |

## **AdRotate Fields (Exported Directly to CSV)**

These fields map 1:1 to AdRotate’s CSV columns. During export, we SELECT these columns and output them as-is.

| **Field** | **What It Means** | **Required?** | **Default** | **Example** |
| --- | --- | --- | --- | --- |
| **advert_name** | Descriptive name for the ad | Yes | — | 300X250-158856-Bass Pro-1328453-General |
| **bannercode** | HTML code that displays the ad | Yes | — | <a href... rel="sponsored"><img.../></a> |
| **imagetype** | Image hosting type | Yes | (blank) | Always blank — we use hosted images |
| **image_url** | Where the banner image is hosted | Yes | — | https://cdn.example.com/banner.gif |
| **width** | Banner width in pixels | Yes | — | 300 |
| **height** | Banner height in pixels | Yes | — | 250 |
| **campaign_name** | Name of the marketing campaign | No | General Promotion | Summer Clearance 2026 |
| **enable_stats...show_android** | Device/visibility flags | Yes | Y | Always Y (show on all devices) |
| **weight** | NOT stored — calculated at export time from advertiser default_weight or ad weight_override | — | — | 2, 4, 6, 8, or 10 |
| **autodelete** | Auto-remove when expired? | Yes | Y | Y |
| **autodisable** | Auto-disable when budget reached? | Yes | N | N |
| **budget, click_rate, impression_rate** | Budget/rate fields (not used) | Yes | 0 | Always 0 |
| **geo_cities, geo_states** | City/state targeting (not used) | Yes | a:0:{} | Always empty |
| **geo_countries** | Country targeting | Yes | a:0:{} | PHP serialized array of country codes |
| **schedule_start** | When ad should start showing | Yes | — | 1704067200 (Unix timestamp) |
| **schedule_end** | When ad should stop showing | Yes | 2650941780 | Far future = no end date |

**Important — Weight in the CSV:** The weight column is not stored on the ads table itself. At export time, the system calculates it: if the ad has a weight_override, use that. Otherwise use the advertiser’s default_weight. If neither is set, default to 2. This is handled by the v_exportable_ads database view.

# **Table: sites**

**Purpose:** Stores information about each of your WordPress websites.

| **Field** | **What It Means** | **Required?** | **Example / Default** |
| --- | --- | --- | --- |
| **id** | Auto-generated unique identifier | Auto | 1, 2, 3... |
| **name** | Friendly name for the site | Yes | RV Travel Life |
| **domain** | The website address | Yes (unique) | rvtravellife.com |
| **wordpress_url** | Full URL to the WordPress site | No | https://rvtravellife.com |
| **is_active** | Is this site currently using the system? | Yes (default TRUE) | TRUE |

**Current sites:**

1. RV Travel Life (rvtravellife.com)
2. This Old Campsite (thisoldcampsite.com)
3. Marine Part Shop (marinepartshop.com)
4. Powersports Part Shop (powersportspartshop.com)
5. The Part Shops (thepartshops.com)

# **Table: placements**

**Purpose:** Defines the specific ad slots on each website and their exact dimensions. Maps to AdRotate groups.

| **Field** | **What It Means** | **Required?** | **Example / Default** |
| --- | --- | --- | --- |
| **id** | Auto-generated unique identifier | Auto | 1, 2, 3... |
| **site_id** | Which website this placement belongs to | Yes | (links to sites) |
| **name** | Friendly name for this ad slot | Yes | Sidebar Medium Rectangle |
| **description** | Additional notes about where this appears | No | Right sidebar, below nav |
| **width** | Required width in pixels | Yes | 300 |
| **height** | Required height in pixels | Yes | 250 |
| **is_active** | Is this placement currently in use? | Yes (default TRUE) | TRUE |
| **adrotate_group_id** | The corresponding group ID in AdRotate for this site | No (NULL) | 5 |

**adrotate_group_id:** Each site’s AdRotate plugin uses group numbers to organize ads by placement size. The same dimensions might have different group IDs on different sites. This field stores that mapping. Richard will provide the group IDs for each site.

# **Table: site_advertiser_rules**

**Purpose:** Controls which advertisers are allowed or blocked on each specific site. This is the primary filtering mechanism: Richard’s “advertiser grid.”

| **Field** | **What It Means** | **Required?** | **Example / Default** |
| --- | --- | --- | --- |
| **id** | Auto-generated unique identifier | Auto | 1, 2, 3... |
| **site_id** | Which website this rule applies to | Yes | (links to sites) |
| **advertiser_id** | Which advertiser this rule is about | Yes | (links to advertisers) |
| **rule** | The decision | Yes (default: default) | allowed, denied, or default |
| **reason** | Why this decision was made | No | Not relevant to boating audience |

**v2 changes:** Removed decided_by and decided_at fields.

## **How Rules Are Created**

- **Auto-created from per-site API keys:** When syncing from networks with per-site API keys (FlexOffers, CJ), the system automatically creates a rule linking the advertiser to that specific site with status = default (pending). No rule is created for other sites.
- **Manual creation:** For networks with a single token (Awin), no auto-association happens. Richard creates rules manually in the advertiser grid.
- **Nothing exports until “allowed”:** An advertiser must be explicitly set to “allowed” before their ads can appear in any export. Default/pending = blocked.

# **Supporting Tables**

## **sync_logs**

Keeps a history of every time we pulled data from the affiliate networks. Useful for troubleshooting (“Did the sync run last night?”) and tracking system health. Also tracks how many stale ads were hard-deleted each run.

## **export_logs**

Keeps a history of every CSV export generated for AdRotate. Records the filename (which includes site + date), how many ads were exported, and who triggered it. Useful for auditing (“What did we send to RV Travel Life on Tuesday?”).

# **The Export View: v_exportable_ads**

This is a database view (a saved query) that does the heavy lifting for CSV export. It joins all the necessary tables and applies the filtering logic:

1. Only includes ads from allowed advertisers (site_advertiser_rules.rule = ‘allowed’)
2. Only includes active advertisers (is_active = TRUE)
3. Only includes approved ads (approval_status = ‘approved’)
4. Only includes active ads (status = ‘active’)
5. Calculates the final weight: ad override > advertiser default > fallback to 2

To export for a specific site and placement size, the application runs:

*SELECT * FROM v_exportable_ads WHERE site_id = 1 AND width = 300 AND height = 250*

# **How Everything Connects**

**advertisers (1) ←→ (many) ads**

Each advertiser can have many ads. Deleting an advertiser cascades to delete all their ads.

**sites (1) ←→ (many) placements**

Each site has multiple ad slots with specific dimensions.

**sites + advertisers ←→ site_advertiser_rules**

The many-to-many relationship: which brands are allowed on which sites.

**v_exportable_ads joins it all together**

Combines ads + advertisers + site rules to produce the export-ready dataset.

# **Changes from Version 1**

| **Change** | **V1** | **V2** |
| --- | --- | --- |
| Primary keys | UUIDs | Auto-increment integers |
| site_ads table | Existed (per-site ad approval) | Removed — approval is global on ads table |
| Ad approval default | Pending (must approve each ad) | Approved (deny bad ones only) |
| deny_is_permanent | Field on site_ads | Removed — denied means denied |
| decided_by / decided_at | Fields on site_advertiser_rules | Removed — not needed |
| Weight | Stored on ads only | default_weight on advertisers + weight_override on ads |
| commission_rate | Not tracked | New field on advertisers for network comparison |
| Stale ads | Not handled | Hard deleted during sync |
| Stale advertisers | Not handled | Soft-deleted (is_active = FALSE), rules preserved |
| Database view | Not defined | v_exportable_ads with weight calculation logic |
| Database | Supabase Postgres | MySQL on cPanel (phpMyAdmin) |

# **Glossary**

| **Term** | **Definition** |
| --- | --- |
| **EPC** | Earnings Per Click — how much money you earn on average when someone clicks an ad |
| **Placement** | A specific spot on a webpage where an ad can appear, defined by exact dimensions |
| **Tracking URL** | A special link that records when someone clicks and ensures you get credit for the sale |
| **Weight** | In AdRotate, a number (2–10) that controls how often an ad shows compared to others. Assigned at advertiser level, overridable per ad. |
| **Sync** | The process of pulling fresh data from affiliate networks into our database |
| **Export** | The process of generating a CSV file for AdRotate import |
| **Hard Delete** | Permanently removing a record from the database (used for stale ads) |
| **Soft Delete** | Marking a record as inactive without removing it, preserving related data (used for stale advertisers) |
| **Unix Timestamp** | A number representing a date/time as seconds since January 1, 1970 |
| **PHP Serialized Array** | A special format that PHP uses to store lists of data as text (used for geo_countries) |