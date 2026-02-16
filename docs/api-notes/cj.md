# CJ (Commission Junction) API Notes

**Owner**: Rag

## API Documentation

- Advertiser Lookup Base URL: `https://advertiser-lookup.api.cj.com/v2/advertiser-lookup`
- Link Search Base URL: `https://link-search.api.cj.com/v2/link-search`
- Authentication: `Authorization: Bearer <personal-access-token>`
- Manage tokens: [Personal Access Tokens](https://developers.cj.com/account/personal-access-tokens)
- Docs: [CJ Developer Portal](https://developers.cj.com/)

## CRITICAL: Response Format is XML (not JSON)

Unlike FlexOffers which returns JSON, CJ returns **XML**. You must parse XML responses.
Use Python's `xml.etree.ElementTree` or `lxml` to parse.

## Rate Limits

- **25 calls per minute** for both endpoints
- Publishers only

## Required Credentials

| Credential | Env Var | Description |
|---|---|---|
| Personal Access Token | `CJ_API_TOKEN` | Bearer token for auth |
| Company ID (CID) | `CJ_CID` | Your publisher CID, required for advertiser lookup (`requestor-cid`) |
| Website/Property ID (PID) | `CJ_WEBSITE_ID` | Your website PID, required for link search (`website-id`) |

**Note**: CID and PID are different values. CID = your company account ID. PID = your registered website/property ID. Both are found in your CJ Account Manager.

## Endpoints

### 1. Advertiser Lookup

```
GET https://advertiser-lookup.api.cj.com/v2/advertiser-lookup
```

**Sample Request:**
```bash
curl -s -XGET "https://advertiser-lookup.api.cj.com/v2/advertiser-lookup?requestor-cid=4567&advertiser-ids=joined" \
  -H "Authorization: Bearer <your-personal-access-token>"
```

**Query Parameters:**

| Parameter | Required | Description |
|---|---|---|
| `requestor-cid` | **YES** | Your publisher CID (Company ID) |
| `advertiser-ids` | No | `joined` = your advertisers, `notjoined`, or comma-separated CIDs |
| `advertiser-name` | No | Filter by advertiser name or program URL |
| `keywords` | No | Keyword search (supports `+` AND, `-` NOT operators) |
| `page-number` | No | Page of results (starts at 1) |
| `records-per-page` | No | Records per page (default: 25, **max: 100**) |
| `mobile-tracking-certified` | No | `true` or `false` |

**Sample Response (XML):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<cj-api>
  <advertisers total-matched="1" records-returned="1" page-number="1">
    <advertiser>
      <advertiser-id>1234567</advertiser-id>
      <account-status>Active</account-status>
      <seven-day-epc>20.00</seven-day-epc>
      <three-month-epc>25.00</three-month-epc>
      <language>en</language>
      <advertiser-name>Sample Advertiser</advertiser-name>
      <program-url>http://www.Advertiser.com</program-url>
      <relationship-status>joined</relationship-status>
      <mobile-supported>true</mobile-supported>
      <network-rank>5</network-rank>
      <primary-category>
        <parent>Home & Garden</parent>
        <child>Utilities</child>
      </primary-category>
      <performance-incentives>false</performance-incentives>
      <actions>
        <action>
          <name>Online Sale</name>
          <type>advanced sale</type>
          <id>1234</id>
          <commission>
            <default>USD 0.50</default>
          </commission>
        </action>
      </actions>
      <link-types>
        <link-type>Content Link</link-type>
        <link-type>Banner</link-type>
        <link-type>Text Link</link-type>
        <link-type>Advanced Link</link-type>
      </link-types>
    </advertiser>
  </advertisers>
</cj-api>
```

**Pagination:** Use `total-matched` attribute on `<advertisers>` to know total count. Compare `page-number * records-per-page` against `total-matched` to know when to stop.

### 2. Link Search (Creatives)

```
GET https://link-search.api.cj.com/v2/link-search
```

**Sample Request:**
```bash
curl -s -XGET "https://link-search.api.cj.com/v2/link-search?website-id=12345&link-type=banner&advertiser-ids=joined" \
  -H "Authorization: Bearer <your-personal-access-token>"
```

**Query Parameters:**

| Parameter | Required | Description |
|---|---|---|
| `website-id` | Recommended | Your Website PID (Property ID) |
| `advertiser-ids` | No | `joined`, `notjoined`, or comma-separated CIDs |
| `link-type` | No | `Banner`, `Text Link`, `Content Link`, `Advanced Link` |
| `category` | No | Advertiser sub-category (not top-level) |
| `keywords` | No | Keyword search (supports boolean operators) |
| `promotion-type` | No | `coupon`, `sweepstakes`, `product`, `sale/discount`, `free shipping`, `seasonal link`, `site to store` |
| `promotion-start-date` | No | `MM/DD/YYYY` format |
| `promotion-end-date` | No | `MM/DD/YYYY` or `ongoing` |
| `page-number` | No | Page of results (starts at 1) |
| `records-per-page` | No | Records per page (default: 100) |
| `link-id` | No | Specific link ID (one at a time) |
| `last-updated` | No | `MM/DD/YYYY` - links updated since this date |
| `language` | No | Language code filter |
| `allow-deep-linking` | No | `true` or `yes` |
| `targeted-country` | No | Two-letter country code |
| `cross-device-only` | No | `true` or `false` |
| `mobile-optimized` | No | `true` or `false` |
| `mobile-app-download` | No | `true` or `false` |

**Sample Response (XML):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<cj-api>
  <links total-matched="2" records-returned="2" page-number="1">
    <link>
      <advertiser-id>15058</advertiser-id>
      <advertiser-name>CJ Affiliate Demo</advertiser-name>
      <category>Home Appliances</category>
      <click-commission>0.0</click-commission>
      <creative-height>0</creative-height>
      <creative-width>0</creative-width>
      <language>English</language>
      <lead-commission>3.00%</lead-commission>
      <link-code-html>
        <a href="https://www.tkqlhce.com/click-...">best diet supplement</a>
        <img src="https://www.ftjcfx.com/image-..." width="1" height="1" border="0"/>
      </link-code-html>
      <destination>http://www.example.com/product</destination>
      <link-id>11470088</link-id>
      <link-name>best diet supplement</link-name>
      <description>best diet supplement</description>
      <link-type>Text Link</link-type>
      <allow-deep-linking>false</allow-deep-linking>
      <performance-incentive>false</performance-incentive>
      <promotion-end-date></promotion-end-date>
      <promotion-start-date></promotion-start-date>
      <promotion-type>N/A</promotion-type>
      <coupon-code></coupon-code>
      <relationship-status>joined</relationship-status>
      <sale-commission></sale-commission>
      <seven-day-epc>N/A</seven-day-epc>
      <three-month-epc>N/A</three-month-epc>
      <clickUrl>https://www.tkqlhce.com/click-3074780-11470088-1700475083000</clickUrl>
    </link>
  </links>
</cj-api>
```

**Pagination:** Use `total-matched` attribute on `<links>` to know total count. Default is 100 records per page. Check `records-returned` < `records-per-page` or `page-number * records-per-page >= total-matched` to stop.

## Field Mapping

### Advertiser Fields (XML element names use hyphens)

| CJ XML Element | Canonical Field | Notes |
|---|---|---|
| `advertiser-id` | `network_program_id` | Cast to string |
| `advertiser-name` | `network_program_name` | |
| `account-status` | `status` | `Active` → "active", `Deactive` → "paused" |
| `program-url` | `website_url` | |
| `primary-category/child` | `category` | Use child (sub-category) |
| `seven-day-epc` | `epc` | Parse as float, default 0 |
| `relationship-status` | (filter only) | Must be "joined" |
| `network-rank` | (metadata) | Advertiser ranking in CJ |

### Link/Ad Fields (XML element names use hyphens)

| CJ XML Element | Canonical Field | Notes |
|---|---|---|
| `link-id` | `network_link_id` | Cast to string |
| `link-name` | `name` | Also used in `advert_name` |
| `link-type` | `creative_type` | `Banner` → "banner", `Text Link` → "text", others → "html" |
| `link-code-html` | `bannercode` / `html_snippet` | Raw HTML from CJ (blank if not joined) |
| `clickUrl` | `tracking_url` | **Note: camelCase, not hyphenated** |
| `destination` | `destination_url` | |
| `creative-width` | `width` | Pixels, 0 for text links |
| `creative-height` | `height` | Pixels, 0 for text links |
| `seven-day-epc` | `epc` | May be "N/A" — default to 0 |
| `promotion-start-date` | `start_date` | UTC, may be empty |
| `promotion-end-date` | `end_date` | UTC, may be empty |
| `category` | (metadata) | Advertiser sub-category |
| `advertiser-id` | (join key) | Links back to advertiser |
| `description` | (metadata) | Link description |
| `coupon-code` | (metadata) | Coupon if present |

## advert_name Format

Same pattern as FlexOffers:
```
{width}X{height}-{advertiser_id}-{sanitized_name}-{link_id}-General
```
Example: `300X250-1234567-SampleAdvertiser-11470088-General`

- `sanitized_name`: Alphanumeric only, no spaces or special chars
- `link_id`: The CJ `link-id` value

## Key Differences from FlexOffers

| Aspect | FlexOffers | CJ |
|---|---|---|
| Response format | **JSON** | **XML** |
| Auth header | `apiKey: <key>` | `Authorization: Bearer <token>` |
| Rate limit | Not documented | 25 calls/min |
| Pagination params | `page`, `pageSize` | `page-number`, `records-per-page` |
| Default page size | 25 (advertisers), 100 (ads) | 25 (advertisers), 100 (links) |
| Max page size | 25 (advertisers), 100 (ads) | 100 (both) |
| Creative dimensions | `bannerWidth`, `bannerHeight` | `creative-width`, `creative-height` |
| Two separate base URLs | No (one base URL) | Yes (advertiser-lookup vs link-search) |
| Needs two IDs | No (just API key) | Yes (CID + PID) |
| EPC field | Always numeric | Can be "N/A" — must handle |

## Important Gotchas

1. **XML parsing required**: Use `xml.etree.ElementTree` (stdlib) to parse responses
2. **Two different base URLs**: Advertiser lookup and link search are on different hosts
3. **`requestor-cid` is required**: Advertiser lookup won't work without your CID
4. **`website-id` needed for link search**: Required to get proper `link-code-html` with tracking
5. **EPC can be "N/A"**: Must safely parse — `float(epc) if epc != "N/A" else 0`
6. **`clickUrl` is camelCase**: Every other field uses hyphens, but `clickUrl` doesn't — watch for this
7. **Text links have 0x0 dimensions**: `creative-width` and `creative-height` are 0 for text links
8. **`link-code-html` is blank for non-joined**: Only returns HTML for joined advertiser links
9. **Empty request returns zero results**: You MUST provide at least one filter parameter
10. **Errors return HTTP 401**: Bad token, missing token, or incorrect URL all return 401
