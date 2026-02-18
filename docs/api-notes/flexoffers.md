# FlexOffers API Notes

**Owner**: Mark

## API Documentation

- Base URL: `https://api.flexoffers.com`
- Authentication: Bearer token (API key per domain)
- Docs: [FlexOffers API Documentation](https://www.flexoffers.com/affiliate-programs/api-tools/)

## Endpoints

### Get Advertisers/Programs
```
GET /advertisers
```

**Response Structure:**
```json
{
  "id": 157929,
  "name": "Sam's Club",
  "domainUrl": "https://samsclub.com",
  "programStatus": "Approved",
  "applicationStatus": "Approved",
  "categoryNames": "Department Stores",
  "sevenDayEpc": "0.14"
}
```

### Get Creatives/Promotions
```
GET /advertisers/{id}/promotions
```

**Response Structure:**
```json
{
  "linkId": "4611686018427577245",
  "linkName": "Summer Sale Banner",
  "linkType": "Banner",
  "linkUrl": "https://track.flexoffers.com/...",
  "imageUrl": "https://media.flexoffers.com/...",
  "bannerWidth": 300,
  "bannerHeight": 250,
  "htmlCode": "<a href=\"...\"><img src=\"...\" /></a>",
  "epc7D": "0.08"
}
```

## Field Mapping

### Advertiser Fields

| FlexOffers Field | Canonical Field | Notes |
|------------------|-----------------|-------|
| `id` | `network_program_id` | Cast to string |
| `name` | `network_program_name` | |
| `programStatus` + `applicationStatus` | `status` | "active" if both "Approved" |
| `domainUrl` | `website_url` | |
| `categoryNames` | `category` | |
| `sevenDayEpc` | `epc` | Parse as float, default 0 |

### Ad/Promotion Fields

| FlexOffers Field | Canonical Field | Notes |
|------------------|-----------------|-------|
| `linkId` | `network_link_id` | String (may contain dots) |
| `linkUrl` | `tracking_url` | |
| `imageUrl` | `image_url` | |
| `bannerWidth` | `width` | |
| `bannerHeight` | `height` | |
| `htmlCode` | `bannercode` | Use if available, else construct |
| `linkName` | Part of `advert_name` | |
| `epc7D` | `epc` | Parse as float |

## advert_name Format

Format: `{width}X{height}-{advertiser_id}-{sanitized_name}-{link_id_suffix}-General`

Example: `300X250-157929-SamsClub-4611686018427577245-General`

- `sanitized_name`: Alphanumeric only, no spaces or special chars
- `link_id_suffix`: Last segment of linkId (split on `.`) for brevity

## Notes

- **Rate Limiting**: Standard API rate limits apply
- **Pagination**: Use `page` and `pageSize` query parameters
- **linkId Format**: Always treat as string; may contain dots (e.g., `123.456.789`)
- **EPC Values**: May be strings or floats; parse safely with `float(raw.get("epc7D") or 0)`
- **htmlCode**: May contain HTML entities; provided for most banner ads
- **Null Dimensions**: Text links have null `bannerWidth`/`bannerHeight`; default to 0
- **Status Logic**: Advertiser is "active" only when both `programStatus` AND `applicationStatus` are "Approved"
