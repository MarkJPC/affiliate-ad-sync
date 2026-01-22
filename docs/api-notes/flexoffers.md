# FlexOffers API Notes

**Owner**: Mark

## API Documentation

- Base URL: `https://api.flexoffers.com`
- Authentication: Bearer token
- Docs: [FlexOffers API Documentation](https://www.flexoffers.com/affiliate-programs/api-tools/)

## Endpoints to Implement

### Get Advertisers/Programs
```
GET /advertisers
```
TODO: Document actual endpoint and response structure

### Get Creatives/Banners
```
GET /advertisers/{id}/creatives
```
TODO: Document actual endpoint and response structure

## Field Mapping

| FlexOffers Field | Canonical Field |
|-----------------|-----------------|
| `id` | `network_program_id` / `network_link_id` |
| `name` | `network_program_name` / `name` |
| `imageUrl` | `image_url` |
| `trackingUrl` | `tracking_url` |
| `width` | `width` |
| `height` | `height` |

## Notes

- TODO: Add rate limiting info
- TODO: Add pagination handling
- TODO: Document any quirks or edge cases
