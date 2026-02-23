# Data Normalization Pipeline

This document explains how the sync service converts raw data from affiliate networks into a standardized format for our system.

## The Challenge

Each affiliate network (FlexOffers, Awin, CJ, Impact) sends data in its own unique format. For example:

| Concept | FlexOffers calls it | CJ calls it | What we call it |
|---------|---------------------|-------------|-----------------|
| Advertiser ID | `id` | `advertiser-id` | `network_program_id` |
| Program name | `name` | `advertiser-name` | `network_program_name` |
| Active status | `"Approved"` | `"joined"` | `"active"` |
| Banner link | `linkUrl` | `clickUrl` | `tracking_url` |

Without normalization, we'd need completely different code paths for each network. Instead, we use a **"translator"** pattern that converts each network's format into our single, standard model.

## How It Works

```
┌─────────────┐     ┌──────────────┐     ┌────────────────┐     ┌──────────┐
│ Network API │ ──► │ Mapper       │ ──► │ Standard Model │ ──► │ Database │
│ (raw data)  │     │ (translator) │     │ (canonical)    │     │          │
└─────────────┘     └──────────────┘     └────────────────┘     └──────────┘
```

Each network has its own Mapper (translator):
- **FlexOffersMapper** - Handles FlexOffers' unique field names and status values
- **AwinMapper** - Handles Awin's format
- **CJMapper** - Handles CJ's format
- **ImpactMapper** - Handles Impact's format

The sync process uses the appropriate mapper for each network, ensuring all data ends up in the same standard format regardless of where it came from.

## The Five Pipeline Stages

### Stage 1: Fetch Raw Data

The system calls the network's API to retrieve advertisers and their ads.

**What happens:** We request data from FlexOffers, Awin, CJ, or Impact using their specific API endpoints and authentication.

**Result:** Raw data in the network's native format (JSON).

### Stage 2: Extract Key Fields

The mapper pulls out the important fields from the raw data.

**What happens:** We identify which fields contain the advertiser ID, name, status, URLs, and other essential information.

**Example (FlexOffers):**
- Extract `id` for the advertiser identifier
- Extract `name` for the program name
- Extract `linkUrl` for the tracking link
- Extract `imageUrl` for banner images

### Stage 3: Normalize Values

The mapper converts network-specific values to our standard values.

**What happens:** Different networks use different words for the same things. We translate them to a consistent vocabulary.

**Status normalization example:**

| Network | Their "active" value | Our standard value |
|---------|----------------------|-------------------|
| FlexOffers | `"Approved"` (both programStatus and applicationStatus) | `"active"` |
| CJ | `"joined"` | `"active"` |
| Impact | `"Active"` | `"active"` |
| Awin | `"accepted"` | `"active"` |

**Creative type normalization example:**

| Network value | Our standard value |
|---------------|-------------------|
| `"Banner Link"` | `"banner"` |
| `"Text Link"` | `"text"` |
| (anything else) | `"html"` |

### Stage 4: Build AdRotate Fields

The mapper constructs fields specifically needed by the AdRotate WordPress plugin.

**What happens:** AdRotate expects certain fields in specific formats. We build these from the normalized data.

**Fields we construct:**

| AdRotate Field | What it contains | Example |
|----------------|------------------|---------|
| `advert_name` | Standardized name with dimensions | `300X250-42-SummerSale-12345-General` |
| `bannercode` | HTML snippet for display | `<a href="..."><img src="..." /></a>` |
| `width` / `height` | Banner dimensions in pixels | `300`, `250` |
| `campaign_name` | Default campaign grouping | `"General Promotion"` |

**How `advert_name` is built:**
```
{width}X{height}-{advertiser_id}-{sanitized_name}-{link_id}-General

Example: 300X250-42-SummerSale-12345-General
         ───┬─── ─┬─ ────┬──── ──┬── ───┬───
            │     │      │       │      │
            │     │      │       │      └── Campaign category
            │     │      │       └── Network's link identifier
            │     │      └── Name with special characters removed
            │     └── Our database advertiser ID
            └── Banner dimensions
```

### Stage 5: Compute Fingerprint

The mapper generates a unique "fingerprint" (hash) of the raw data for change detection.

**What happens:** We create a SHA-256 hash of the complete raw API response. This fingerprint is stored with each record.

**Why this matters:**
- On the next sync, we compare fingerprints
- If the fingerprint hasn't changed, the data is identical—no database update needed
- This makes syncs faster and creates a clear audit trail of when data actually changed

## What Gets Normalized

### Advertiser Fields

| Network Field (varies) | Standard Field | Description |
|------------------------|----------------|-------------|
| `id`, `advertiser-id`, etc. | `network_program_id` | Unique ID from the network |
| `name`, `advertiser-name`, etc. | `network_program_name` | Program/advertiser name |
| `"Approved"`, `"joined"`, etc. | `status` | Always `"active"` or `"paused"` |
| `domainUrl`, `website`, etc. | `website_url` | Advertiser's website |
| `categoryNames`, `category`, etc. | `category` | Product category |
| `sevenDayEpc`, `epc`, etc. | `epc` | Earnings per click metric |

### Ad (Creative) Fields

| Network Field (varies) | Standard Field | Description |
|------------------------|----------------|-------------|
| `linkId`, `ad-id`, etc. | `network_ad_id` | Unique ad ID from network |
| `linkUrl`, `clickUrl`, etc. | `tracking_url` | Affiliate tracking link |
| `imageUrl`, `banner-url`, etc. | `image_url` | Banner image URL |
| `bannerWidth`, `width`, etc. | `width` | Banner width in pixels |
| `bannerHeight`, `height`, etc. | `height` | Banner height in pixels |
| `htmlCode`, `html`, etc. | `bannercode` | HTML code for display |
| `linkType`, `creative-type`, etc. | `creative_type` | `"banner"`, `"text"`, or `"html"` |

## Adding a New Network

If we need to integrate a new affiliate network, here's the process:

1. **Create a new Mapper class** (e.g., `ShareASaleMapper`)
   - Define how to extract fields from their API response format
   - Define how to normalize their status values to ours
   - Build the AdRotate fields using our standard patterns

2. **Create a new NetworkClient class**
   - Implement the API connection and authentication
   - Implement methods to fetch advertisers and ads

3. **Register the new mapper** in the mapper registry

The pipeline stages remain the same—only the "translation rules" change for each network.

## Error Handling

The pipeline is designed to be resilient:

| Situation | What Happens |
|-----------|--------------|
| **Single bad record** | Logged as a warning, skipped, processing continues |
| **Missing field** | Default value used (e.g., `0` for dimensions, `""` for text) |
| **API connection failure** | Error logged, sync marked as failed, can retry later |
| **Invalid status value** | Defaults to `"paused"` (safe default) |

**Key principle:** One bad record shouldn't stop the entire sync. We process what we can and log what we couldn't.

### Sync Statistics

After each sync, we track:
- `advertisers_synced` - How many advertisers were processed
- `ads_synced` - How many ads were processed
- `ads_updated` - How many ads had changes
- `errors` - How many records failed to process

These statistics are recorded in the `sync_logs` table for monitoring and troubleshooting.

## Summary

The normalization pipeline ensures that no matter which network an ad comes from, it ends up in our database in exactly the same format. This means:

- The admin dashboard shows all ads consistently
- CSV exports for AdRotate work the same way for all networks
- Adding new networks doesn't require changing downstream systems
- We have a clear audit trail through fingerprints and sync logs
