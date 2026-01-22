# Impact API Notes

**Owner**: Rag

## API Documentation

- Base URL: `https://api.impact.com`
- Authentication: Basic auth (Account SID + Auth Token)
- Docs: [Impact API Documentation](https://developer.impact.com/)

## Endpoints to Implement

### Get Campaigns (Advertisers)
```
GET /Mediapartners/{AccountSid}/Campaigns
```
TODO: Document actual response structure

### Get Ads
```
GET /Mediapartners/{AccountSid}/Campaigns/{CampaignId}/Ads
```
TODO: Document actual response structure

## Field Mapping

| Impact Field | Canonical Field |
|-------------|-----------------|
| `CampaignId` | `network_program_id` |
| `CampaignName` | `network_program_name` |
| `ContractStatus` | `status` (Active → active) |
| `Id` | `network_link_id` |
| `Name` | `name` |
| `Type` | `creative_type` |
| `AdHtml` | `html_snippet` |
| `ImageUrl` | `image_url` |
| `TrackingLink` | `tracking_url` |
| `LandingPageUrl` | `destination_url` |
| `Width` | `width` |
| `Height` | `height` |
| `State` | `status` (ACTIVE → active) |
| `StartDate` | `start_date` |
| `EndDate` | `end_date` |

## Notes

- Impact uses PascalCase for field names
- State field: ACTIVE, PAUSED, etc.
- TODO: Add rate limiting info
- TODO: Add pagination handling
