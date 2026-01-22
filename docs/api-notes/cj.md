# CJ (Commission Junction) API Notes

**Owner**: Rag

## API Documentation

- Base URL: TBD
- Authentication: Bearer token
- Docs: [CJ Affiliate API](https://developers.cj.com/)

## Endpoints to Implement

### Get Advertisers
```
TBD - CJ uses a different API structure
```
TODO: Document actual endpoint and response structure

### Get Links (Creatives)
```
TBD - CJ calls these "links" rather than creatives
```
TODO: Document actual endpoint and response structure

## Field Mapping

| CJ Field | Canonical Field |
|----------|-----------------|
| `advertiserId` | `network_program_id` |
| `advertiserName` | `network_program_name` |
| `linkId` | `network_link_id` |
| `linkName` | `name` |
| `linkType` | `creative_type` |
| `linkCodeHtml` | `html_snippet` |
| `imageUrl` | `image_url` |
| `clickUrl` | `tracking_url` |
| `destination` | `destination_url` |
| `width` | `width` |
| `height` | `height` |
| `promotionStartDate` | `start_date` |
| `promotionEndDate` | `end_date` |

## Notes

- CJ uses "links" terminology instead of "creatives"
- `relationshipStatus: "joined"` indicates active relationship
- TODO: Add rate limiting info
- TODO: Add pagination handling
