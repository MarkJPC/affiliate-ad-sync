# Impact API Notes

**Owner**: Rag

## API Documentation

- Base URL: `https://api.impact.com`
- Authentication: HTTP Basic Auth (Account SID as username, Auth Token as password)
- Docs: [Impact API Documentation](https://developer.impact.com/)
- Response format: JSON (must send `Accept: application/json` header — defaults to XML otherwise)

## Rate Limiting

From response headers:

| Header | Value | Description |
|--------|-------|-------------|
| `x-ratelimit-limit-hour` | `1000` | Max requests per hour |
| `x-ratelimit-remaining-hour` | varies | Remaining requests this hour |
| `x-ratelimit-limit` | `1000` | Same as hourly limit |
| `x-ratelimit-remaining` | varies | Same as hourly remaining |
| `x-ratelimit-reset` | `1200` | Seconds until limit resets |

**Budget**: With 64 campaigns and ~33 pages of ads, a full sync uses ~34 requests. Well within the 1,000/hour limit.

## Pagination

All list endpoints use the same pagination structure.

**Query parameters:**
- `Page` — Page number (1-based, default: 1)
- `PageSize` — Results per page (default: 100)

**Response metadata (prefixed with `@`):**
```json
{
    "@page": "1",
    "@numpages": "33",
    "@pagesize": "100",
    "@total": "3253",
    "@start": "0",
    "@end": "99",
    "@uri": "/Mediapartners/{AccountSid}/Ads",
    "@firstpageuri": "...?PageSize=100&Page=1",
    "@previouspageuri": "",
    "@nextpageuri": "...?PageSize=100&Page=2",
    "@lastpageuri": "...?PageSize=100&Page=33"
}
```

**Pagination logic**: Increment `Page` until `@nextpageuri` is empty or `@page` equals `@numpages`.

## Endpoints

### Get Campaigns (Advertisers)

```
GET /Mediapartners/{AccountSid}/Campaigns
```

Returns all campaigns (advertiser partnerships) for the media partner account.

**Current data**: 64 campaigns, fits in 1 page at default PageSize=100.

**Response structure:**
```json
{
    "@page": "1",
    "@numpages": "1",
    "@pagesize": "100",
    "@total": "64",
    "Campaigns": [
        {
            "AdvertiserId": "28763",
            "AdvertiserName": "Adorama",
            "AdvertiserUrl": "http://www.adorama.com",
            "CampaignId": "11630",
            "CampaignName": "SunnySports",
            "CampaignUrl": "http://sunnysports.com",
            "CampaignDescription": "Description text...",
            "ShippingRegions": ["US"],
            "CampaignLogoUri": "/Mediapartners/{AccountSid}/Campaigns/11630/Logo",
            "PublicTermsUri": "/Mediapartners/{AccountSid}/Campaigns/11630/PublicTerms",
            "ContractStatus": "Active",
            "ContractUri": "...",
            "TrackingLink": "https://example.sjv.io/c/{MediaPartnerId}/88200/2181",
            "HasStandDownPolicy": "true",
            "AllowsDeeplinking": "true",
            "DeeplinkDomains": ["example.com", "*.example.com"],
            "Uri": "/Mediapartners/{AccountSid}/Campaigns/11630"
        }
    ]
}
```

**ContractStatus values observed**: `"Active"`, `"Expired"`

### Get Ads (Global — all campaigns)

```
GET /Mediapartners/{AccountSid}/Ads
```

> **IMPORTANT**: The per-campaign endpoint (`/Campaigns/{CampaignId}/Ads`) returns **403 Access Denied**.
> Use the global `/Ads` endpoint instead. Each ad includes its `CampaignId` for association.

**Current data**: 3,253 ads total (2,551 BANNER + 633 TEXT_LINK + 69 other).

**Optional query filters:**
- `Type` — Filter by ad type: `BANNER` or `TEXT_LINK`
- `CampaignId` — Filter by campaign (untested, may work here)

**Response structure (BANNER example):**
```json
{
    "Ads": [
        {
            "Id": "1502989",
            "Name": "Learning Rewards - 728x90",
            "Description": "",
            "CampaignId": "9251",
            "CampaignName": "Coinbase",
            "Type": "BANNER",
            "TrackingLink": "https://coinbase-consumer.sjv.io/c/{MediaPartnerId}/1502989/9251",
            "LandingPageUrl": "https://www.coinbase.com/learning-rewards",
            "AdvertiserId": "1339375",
            "AdvertiserName": "Coinbase",
            "Code": "<a rel=\"sponsored\" href=\"...\"><img src=\"//a.impactradius-go.com/display-ad/9251-1502989\" width=\"728\" height=\"90\"/></a>...",
            "IFrameCode": "<iframe src=\"...\" width=\"728\" height=\"90\"></iframe>",
            "Width": "728",
            "Height": "90",
            "CreativeUrl": "//a.impactradius-go.com/display-ad/9251-1502989",
            "Labels": "Learning Rewards",
            "AllowDeepLinking": "false",
            "MobileReady": "false",
            "Language": "ENGLISH",
            "StartDate": "2022-11-03T16:01:14-04:00",
            "EndDate": "",
            "Season": "",
            "TopSeller": "false",
            "DealId": "",
            "DealName": "",
            "DealState": "",
            "Uri": "/Mediapartners/{AccountSid}/Ads/1502989"
        }
    ]
}
```

**Response structure (TEXT_LINK example):**
```json
{
    "Ads": [
        {
            "Id": "1477107",
            "Name": "RC Products",
            "Description": "10% OFF for all RC Products",
            "CampaignId": "15745",
            "CampaignName": "Harfington",
            "Type": "TEXT_LINK",
            "TrackingLink": "https://harfington.pxf.io/c/{MediaPartnerId}/1477107/15745",
            "LandingPageUrl": "https://www.harfington.com/collections/...",
            "AdvertiserId": "3276669",
            "AdvertiserName": "Creation E-Commerce Ltd.",
            "Code": "<h3 id=\"1477107\"><a rel=\"sponsored\" href=\"...\">10% OFF for all RC Products</a></h3>...",
            "IFrameCode": "<iframe src=\"...\"></iframe>",
            "Width": "",
            "Height": "",
            "CreativeUrl": "",
            "Labels": "",
            "AllowDeepLinking": "true",
            "MobileReady": "true",
            "Language": "ENGLISH",
            "StartDate": "2022-10-06T10:54:30-04:00",
            "EndDate": "",
            "DealId": "130505",
            "DealName": "10% off sitewide coupon",
            "DealState": "ACTIVE",
            "DealDefaultPromoCode": "wativ10",
            "Uri": "/Mediapartners/{AccountSid}/Ads/1477107"
        }
    ]
}
```

## Field Mapping (Corrected from live API)

### Campaign → Advertiser

| Impact Field | Canonical Field | Notes |
|-------------|-----------------|-------|
| `CampaignId` | `network_program_id` | String, used as advertiser identifier |
| `CampaignName` | `network_program_name` | |
| `CampaignUrl` | `website_url` | |
| `ContractStatus` | `status` | `"Active"` → `"active"`, else `"paused"` |
| `CampaignDescription` | (not mapped) | Available if needed |
| `AdvertiserId` | (not mapped) | Impact's parent advertiser, not the same as CampaignId |
| `AdvertiserName` | (not mapped) | Parent company name |

### Ad → Ad

| Impact Field | Canonical Field | Notes |
|-------------|-----------------|-------|
| `Id` | `network_link_id` | String |
| `Name` | `name` | |
| `Type` | `creative_type` | `"BANNER"` → `"banner"`, `"TEXT_LINK"` → `"text"` |
| `Code` | `bannercode` / `html_snippet` | Full HTML with tracking pixel — use as bannercode |
| `CreativeUrl` | `image_url` | Only populated for BANNERs, protocol-relative URL |
| `TrackingLink` | `tracking_url` | |
| `LandingPageUrl` | `destination_url` | |
| `Width` | `width` | **String** — must cast to int, empty for TEXT_LINK → 0 |
| `Height` | `height` | **String** — must cast to int, empty for TEXT_LINK → 0 |
| `StartDate` | `start_date` | ISO 8601 with timezone |
| `EndDate` | `end_date` | Empty string if no end date |
| `CampaignId` | (for association) | Links ad back to its campaign/advertiser |

## Key Differences from Original Assumptions

1. **No per-campaign ads endpoint** — `/Campaigns/{id}/Ads` returns 403. Use global `/Ads` instead.
2. **No `State` field on ads** — Ads don't have active/paused status. All returned ads are active.
3. **`Code` not `AdHtml`** — The HTML snippet field is called `Code`.
4. **`CreativeUrl` not `ImageUrl`** — The banner image URL is `CreativeUrl` (protocol-relative).
5. **Dimensions are strings** — `Width` and `Height` are strings, empty for text links.
6. **Ad types**: Only `BANNER` and `TEXT_LINK` (no `HTML` type).
7. **No EPC in campaign or ad response** — Performance metrics come from separate reporting endpoints.
8. **`ContractStatus`** uses title-case (`"Active"`, `"Expired"`), not uppercase.

## Architecture Impact

Since per-campaign ads endpoint is 403, the sync flow changes:
- **Old plan**: fetch campaigns → for each campaign, fetch its ads
- **New plan**: fetch campaigns → fetch ALL ads globally → group ads by CampaignId in code

This means `ImpactClient.fetch_ads()` should ignore the `advertiser_id` parameter and fetch
all ads at once, OR the sync flow can be overridden. See implementation notes.

## Raw Samples

See `docs/api-notes/samples/`:
- `impact-campaigns.json` — Sample campaigns response (2 campaigns)
- `impact-ads.json` — Sample ads response (1 BANNER + 1 TEXT_LINK)
