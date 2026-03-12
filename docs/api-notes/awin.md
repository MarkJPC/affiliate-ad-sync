# Awin API Notes

**Owner**: Nadia

## API Documentation

- Base URL: `https://api.awin.com`
- Authentication: Bearer token
- Docs: [Awin Publisher API](https://wiki.awin.com/index.php/Publisher_API)

## Endpoints to Implement

### Get Programmes (Advertisers)
```
GET /publishers/{publisherId}/programmes
```
TODO: Document actual response structure

### Get Creatives
```
GET /publishers/{publisherId}/advertisers/{advertiserId}/creatives
```
TODO: Document actual response structure

## Field Mapping

| Awin Field | Canonical Field |
|-----------|-----------------|
| `id` | `network_program_id` / `network_link_id` |
| `name` | `network_program_name` / `name` |
| `code` | `html_snippet` |
| `imageUrl` | `image_url` |
| `clickThroughUrl` | `tracking_url` |
| `width` | `width` |
| `height` | `height` |

## Notes

- **Creatives endpoint returns 404** (as of 2026-03-12): The `GET /publishers/{publisherId}/advertisers/{advertiserId}/creatives` endpoint returns 404 for all joined advertisers. Awin's Publisher API does not appear to offer a programmatic endpoint for banner creatives — banners are only available through the Awin UI dashboard. All Awin ads currently come from the promotions endpoint as text/voucher types.
- Rate limiting: 403 responses with exponential backoff retry (up to 3 attempts)
- Pagination: promotions use POST with `pagination.page`/`pagination.pageSize` (max 200)
