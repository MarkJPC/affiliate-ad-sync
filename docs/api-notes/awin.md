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

- TODO: Add rate limiting info
- TODO: Add pagination handling
- TODO: Document any quirks or edge cases
