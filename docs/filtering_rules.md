**Filtering Rules**

Affiliate Ad Sync System

Version 2.0  |  February 18, 2026  |  Author: Mark Cena

*Updated based on Richard’s feedback from February 16, 2026 meeting*

This document describes how the system decides which ads/advertisers appear on which WordPress sites. Every ad goes through a four-step pipeline before it can be exported.

# **The Four-Step Pipeline**

When it’s time to export ads for a site, the system runs through these steps in order:

| **Step** | **What Happens** | **Example** | **Who Does It** |
| --- | --- | --- | --- |
| **1** | Advertiser Approval: Is this brand allowed on this site? | Yamaha Marine blocked from RV sites, allowed on Marine Part Shop | Richard, via the advertiser grid |
| **2** | Placement Grouping: Group the ads by size to match the site’s ad slots | 300×250 ads grouped for sidebar slots, 728×90 for leaderboard slots | Automatic |
| **3** | Ad Review (optional): Scan for bad images or defective creatives | Blurry banner denied, clean banner stays approved | Richard, via the ad review page |
| **4** | Export: Generate the CSV with weights applied | rvtravellife-2026-02-16.csv with 125 ads, weights 2–10 | Richard clicks export; system builds the file |

An ad must pass all steps to be included in the export. If it fails at any step, it’s excluded.

# **Step 1: Advertiser Approval**

## **What It Does**

Each WordPress site can allow or block specific advertisers. This is the primary control. It keeps ads relevant to each site’s niche. For example, marine advertisers should appear on marinepartshop.com, not on rvtravellife.com.

This is where most of the daily decision-making happens.

## **The Three States**

| **State** | **What It Means** |
| --- | --- |
| **Allowed** | This advertiser’s ads CAN appear on this site |
| **Denied** | This advertiser’s ads are BLOCKED from this site |
| **Default (Pending)** | No decision made yet. Advertiser needs review before its ads can export |

## **How Advertisers Initially Appear in the Grid**

When the sync service pulls advertisers from the affiliate networks, the system creates initial site-advertiser associations based on what the network tells us. Different networks work differently:

**Networks with per-site API keys** (FlexOffers, CJ):

Each API key is tied to a specific website. When we pull an advertiser using the RV Travel Life API key, we know that advertiser is approved for RV Travel Life on that network. The system automatically creates a site-advertiser rule linking that advertiser to that specific site with a status of default (pending). No rule is created for any other site as Richard decides those manually.

**Networks with a single token** (Awin):

One API token covers all sites. The network doesn’t tell us which advertisers belong on which site. These advertisers show up in the grid with no site associations at all. Richard sets everything from scratch.

**Important:** Regardless of how an advertiser initially appears, nothing exports until Richard explicitly sets the status to “allowed.” The auto-association just pre-fills the grid to save time,it never makes export decisions on Richard’s behalf.

### **Example: How the Grid Looks After a Sync**

| **Advertiser** | **RV Travel** | **Campsite** | **Marine** | **Powersports** | **Source** | **How It Got Here** | **EPC** |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **Camping World** | ⏳ pending | ⏳ pending | — | — | FlexOffers | Auto — FlexOffers has per-site keys for RV + Campsite | $2.45 |
| **Bass Pro Shops** | ⏳ pending | — | ⏳ pending | — | CJ | Auto — CJ has keys for RV + Marine | $3.10 |
| **Yamaha Marine** | — | — | — | — | Awin | No auto — Awin uses single token, no site info | $1.80 |

Richard filters for “pending” and works through the list: confirm the auto-associations, deny ones that don’t fit, and manually assign Awin advertisers to the right sites.

## **Network-Site Eligibility**

Some networks are not approved for all sites. For example, FlexOffers is only approved for RV Travel Life and This Old Campsite, not Marine Part Shop or Powersports. This is a business relationship managed by the network, not something we control.

Because we use per-site API keys for those networks, this is enforced automatically: we simply don’t have an API key for Marine Part Shop on FlexOffers, so we can’t pull ads for it. The current network-site approval status:

| **Site** | **FlexOffers** | **Awin** | **CJ** | **Impact** | **Notes** |
| --- | --- | --- | --- | --- | --- |
| rvtravellife.com | ✅ Approved | ✅ | ✅ | ⏳ Pending |  |
| thisoldcampsite.com | ✅ Approved | ✅ | ✅ | ⏳ Pending |  |
| marinepartshop.com | ❌ Declined | ✅ | ✅ | ⏳ Pending | No FlexOffers ads |
| powersportspartshop.com | ⏳ Pending | ✅ | ✅ | ⏳ Pending |  |

## **The Advertiser Grid (Primary Dashboard Screen)**

The advertiser grid is the main screen Richard uses to manage advertisers. It’s laid out as a table:

- Each row is an advertiser (e.g., Camping World, Bass Pro Shops, Yamaha Marine)
- Columns include each site with allow/deny checkboxes
- Additional columns show EPC, commission rates per network, and current weight
- A weight selector lets you assign a default weight (2, 4, 6, 8, or 10) to each advertiser

## **Filtering & Bulk Actions**

The grid supports filters and bulk changes so Richard can make efficient decisions:

**Filter examples:**

- “Show me all advertisers with EPC greater than $5” → set high weight for top earners
- “Show me all advertisers with weight 10 but EPC less than $0.50” → demote underperformers
- “Show me all pending/default advertisers” → process new arrivals
- Filter by network, name, or status across any column

**Bulk actions:**

- Select multiple advertisers and set the same weight
- Allow or deny a group of advertisers for a specific site at once

## **Duplicate Advertisers Across Networks**

The same brand can appear on more than one affiliate network (e.g., Camping World on both FlexOffers and Awin). The system detects this during sync and links them together so they’re managed as one brand. The grid will show which network(s) each advertiser comes from and the commission rates for each, so Richard can compare which network is more profitable.

*Full technical details on how duplicates are handled are in the **Schema Documentation**.*

## **Where This Is Stored**

**Table:** site_advertiser_rules

Each rule connects one site to one advertiser:

- Which site does this rule apply to?
- Which advertiser does this rule apply to?
- What’s the decision? (allowed, denied, or default)
- Optional: Why was this decision made?

## **Example**

| **Site** | **Advertiser** | **Rule** | **Reason** |
| --- | --- | --- | --- |
| rvtravellife.com | Camping World | Allowed | Core RV/camping brand |
| rvtravellife.com | Yamaha Marine | Denied | Not relevant to RV audience |
| marinepartshop.com | Yamaha Marine | Allowed | Core marine brand |
| marinepartshop.com | Camping World | Denied | Not relevant to boating audience |

## **How It Works During Export**

When generating ads to export for a site:

1. Look up the rule for this site + advertiser combination
2. If the rule is “denied” → skip all ads from this advertiser
3. If the rule is “allowed” → continue to Step 2
4. If the rule is “default” (no decision yet) → skip. Advertiser must be explicitly allowed before their ads export

# **Step 2: Placement Grouping**

## **What It Does**

Each WordPress site has specific ad slots with fixed dimensions. A 300×250 banner can only go in a 300×250 slot. This step groups all the allowed ads by their dimensions to match the site’s available placements.

## **Why Exact Matching?**

If you put a 728×90 banner in a 300×250 slot, it will look stretched, cropped, or broken. The system enforces exact matching to prevent this.

## **Common Placement Sizes**

| **Name** | **Dimensions** | **Typical Location** |
| --- | --- | --- |
| Medium Rectangle | 300×250 | Sidebar, in-content |
| Leaderboard | 728×90 | Header, above content |
| Wide Skyscraper | 160×600 | Sidebar |
| Billboard | 970×250 | Header, premium placement |

## **Where This Is Stored**

**Table:** placements

Each placement defines a slot on a site:

- Which site is this placement on?
- What are the required dimensions? (width × height)
- What’s the corresponding AdRotate group ID?
- Is this placement active?

**Table:** ads (dimension fields)

Each ad stores its width and height in pixels. If an ad has no dimensions, it cannot match any placement and will never be exported.

## **How It Works During Export**

1. Get all active placements for this site (e.g., 300×250 sidebar, 728×90 leaderboard)
2. For each placement, find ads from allowed advertisers whose dimensions match exactly
3. Ads with missing or invalid dimensions are skipped

# **Step 3: Ad Review (Optional)**

## **What It Does**

Even if an advertiser is allowed on a site, some of their individual ads might have bad images, missing text, or be visually unappealing. This step gives manual control over specific creatives.

This is a lightweight review. Richard mainly scans for defective images. Most ads pass through without needing attention.

## **How It Works**

**Ads default to approved.** New ads that arrive from a sync are automatically approved and will export unless Richard specifically denies them. This keeps the workload low — Richard only needs to act on bad ads, not approve every single one.

| **State** | **What It Means** | **Exported?** |
| --- | --- | --- |
| **Approved (default)** | Ad is cleared for export — this is the default for new ads | Yes |
| **Denied** | Ad has been rejected (bad image, broken, off-brand) | No — never, unless re-approved |

## **Approval Is Global (Not Per-Site)**

When Richard approves or denies an ad, the decision applies everywhere. If a blurry banner is denied, it’s denied on all sites. There’s no need to review the same ad separately for each site — the advertiser-site rules (Step 1) already control where ads appear.

## **Denials Are Permanent**

If an ad is denied, it stays denied. Even if the same ad appears again in a future sync, the denial is preserved. Richard never has to review the same bad creative twice.

If he changes his mind, he can manually re-approve it in the dashboard.

## **Ad Review Page (Dashboard)**

The ad review page defaults to showing only items that need attention. Features include:

- Default filter: show ads that may need review
- Image preview for each ad so Richard can see exactly what it looks like
- Filter by size, advertiser, or network
- Deny individual ads with one click
- Bulk approve all ads from a trusted advertiser

## **Where This Is Stored**

**Table:** ads

The approval status is stored directly on the ad record:

- approval_status: approved (default) or denied
- approval_reason: optional note explaining why it was denied

Since approval is global (not per-site), there’s no need for a separate site_ads join table. The status lives on the ad itself.

# **Step 4: Export with Weights**

## **What It Does**

This is the final step. The system collects all ads that passed Steps 1–3 and generates a CSV file that can be imported directly into the AdRotate plugin on WordPress.

## **How Weights Work**

Every ad in the export gets a weight (2, 4, 6, 8, or 10) that controls how often AdRotate shows it. Higher weight = shown more frequently.

**Weight inheritance:**

1. **Advertiser default weight:** Richard assigns a weight to each advertiser in the grid (e.g., Camping World = 8). All of that advertiser’s ads inherit this weight.
2. **Ad-level override:** If a specific ad is outperforming or underperforming, Richard can override just that ad’s weight. For example, one Camping World banner performing exceptionally well gets bumped to 10, while the rest stay at 8.
3. **Final weight in CSV:** If the ad has its own override, use that. Otherwise, use the advertiser’s default weight. If neither is set, default to 2 (lowest).

| **Advertiser Weight** | **Ad Override** | **Weight in CSV** | **Why** |
| --- | --- | --- | --- |
| 8 | (none) | 8 | Inherits from advertiser |
| 8 | 10 | 10 | Ad override takes priority |
| 8 | 4 | 4 | Ad demoted individually |
| (none) | (none) | 2 | Fallback to lowest weight |

## **The Export Process**

When Richard clicks “Export” for a specific site, the system:

1. Collects all ads that passed Steps 1–3 (allowed advertiser, matching dimensions, approved)
2. Calculates the final weight for each ad (advertiser default or ad override)
3. Selects only the fields AdRotate needs (advert_name, bannercode, image_url, weight, geo_countries, schedule dates, etc.)
4. Outputs a CSV file named with the site and date (e.g., rvtravellife-2026-02-16.csv)
5. Richard downloads the file and imports it into AdRotate on that site

*Future enhancement: Richard is exploring an AdRotate helper plugin that could automate the import step, removing the need for manual CSV upload.*

# **Stale Data Cleanup**

Ads and advertisers can disappear from affiliate networks (programs end, creatives are retired, etc.). The system handles this automatically during each sync:

**Ads:** If an ad existed in our database but does not appear in the latest sync from that network, it is hard deleted. It’s gone because we only care about live ads. If the network brings it back later, it will be re-imported as a new ad.

**Advertisers:** If an advertiser disappears from a network, they are soft-deleted (marked inactive and excluded from exports). Their advertiser-site rules are preserved. If the advertiser reappears in a future sync, the existing rules are restored and Richard doesn’t have to redo any decisions.

# **The Complete Picture**

## **Example: Exporting for rvtravellife.com**

Site placements: 300×250 sidebar, 728×90 leaderboard

| **Step** | **What Happens** | **Result** | **Ads Remaining** |
| --- | --- | --- | --- |
| **Start** | All ads in the database |  | 500 ads |
| **1** | Remove ads from denied/pending advertisers | Marine-only and unreviewed brands filtered out | 350 ads |
| **2** | Group by placement dimensions | 300×250: 90 ads, 728×90: 50 ads, other sizes: skipped | 140 ads |
| **3** | Remove denied ads | 15 ads denied for bad images | 125 ads |
| **4** | Apply weights and export CSV | rvtravellife-2026-02-16.csv generated | 125 ads exported |

# **Richard’s Daily Workflow**

Here’s the typical daily routine:

1. **Sync runs automatically** every 24 hours in the background. New ads and advertisers appear in the dashboard. Stale ads are cleaned up.
2. **Open the Advertiser Grid.** Filter for pending/default advertisers. Review new brands, check their EPC and commission rates, allow or deny for each site, and set weights.
3. **Quick ad scan (optional).** Browse the ad review page for any obviously bad images. Deny the bad ones. Most ads are already auto-approved.
4. **Export.** Select a site, download the CSV, and import into AdRotate.

# **Where Everything Is Stored (Summary)**

| **What** | **Database Table** | **Key Fields** |
| --- | --- | --- |
| Advertiser allow/deny per site | site_advertiser_rules | site_id, advertiser_id, rule, reason |
| Ad approval (global) | ads | approval_status, approval_reason |
| Placement dimensions | placements | site_id, width, height, adrotate_group_id |
| Ad dimensions | ads | width, height |
| Advertiser default weight | advertisers | default_weight |
| Ad weight override | ads | weight_override |

All rules are stored in the MySQL database. No external config files needed.

# **Quick Reference**

**Q: Why isn’t this ad showing on my site?**

Check in this order:

1. Is the advertiser denied or still pending for this site? → Check the advertiser grid
2. Is the ad itself denied? → Check the ad review page
3. Does the ad’s size match an available placement? → Check site placements vs ad dimensions
4. Is the ad still active in the network? → Stale ads are auto-deleted during sync

**Q: How do I block an advertiser from all sites?**

In the advertiser grid, uncheck all site columns for that advertiser.

**Q: How do I approve all ads from a trusted advertiser?**

Ads are auto-approved by default. If some were previously denied, use bulk approve in the ad review page filtered by advertiser name.

**Q: What happens when a denied ad reappears in a sync?**

Nothing changes. The denial is preserved. The ad stays denied unless Richard manually re-approves it.

**Q: What if the same advertiser is on two networks?**

The system links them together. Allow/deny decisions and weight apply across all networks for that brand. Commission rates are shown per network so Richard can compare.

**Q: Can FlexOffers ads show on Marine Part Shop?**

No. FlexOffers is only approved for RV Travel Life and This Old Campsite. Since we don’t have an API key for Marine Part Shop, FlexOffers ads are never pulled for that site. This is enforced at the network level, before our system even gets involved.

# **Changes from Version 1**

| **Change** | **V1** | **V2** |
| --- | --- | --- |
| Ad approval scope | Per-site (site_ads table) | Global (on the ads table) |
| Default ad status | Pending (must approve) | Approved (deny bad ones) |
| deny_is_permanent flag | Included | Removed — denied means denied |
| decided_by / decided_at on rules | Included | Removed — not needed |
| Weight assignment | Per-ad only (from EPC formula) | Advertiser default + ad override |
| Advertiser grid in dashboard | Not specified | Primary screen with filtering and bulk actions |
| Stale data handling | Not specified | Hard delete ads, soft-delete advertisers |
| Pipeline structure | Three layers | Four steps (advertiser, placement, ad review, export) |
| Network-site eligibility | Not documented | Documented with auto-association from per-site API keys |
| Initial advertiser-site association | Manual only | Auto-created from per-site network data, still requires confirmation |

Questions? Reach out to Mark Cena (markjpcena@gmail.com).