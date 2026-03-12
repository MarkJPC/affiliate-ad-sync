# Banner Export Verification Pack

This checklist verifies banner CSV behavior for:

- AdRotate column contract/order
- Placement-aware eligibility
- Quality normalization and invalid-row rejection

## Automated checks

Run from `admin-dashboard`:

```bash
php artisan test --filter=BannerExportVerificationTest
```

What this validates:

- Only active placement sizes are exported for a site.
- Banner quality normalization is applied (`Y/N` flags, numeric defaults, schedule fallback, geo defaults).
- Invalid banner rows are excluded (bad dimensions or empty creative payload).

## Manual checks (UI + service behavior)

1. Start app:

```bash
cd "/Users/ragpatel/affiliate-ad-sync/affiliate-ad-sync/admin-dashboard" && php artisan serve
```

2. Open export page:

`http://127.0.0.1:8000/export`

3. Download a banner CSV for a site that has active placements and allowed advertisers.

4. Validate CSV headers exactly match expected AdRotate order:

`id,advert_name,bannercode,imagetype,image_url,enable_stats,show_everyone,show_desktop,show_mobile,show_tablet,show_ios,show_android,weight,autodelete,autodisable,budget,click_rate,impression_rate,state_required,geo_cities,geo_states,geo_countries,schedule_start,schedule_end`

5. Validate row quality:

- `weight` is positive.
- `imagetype` is empty for affiliate ads (only set for uploaded images in AdRotate).
- `image_url` is empty for affiliate ads (AdRotate only uses this for locally-uploaded images; remote URLs are embedded in `bannercode`).
- `enable_stats` and other flags are strict `Y`/`N`.
- `schedule_end` is greater than `schedule_start`.
- `geo_*` fields are present.
- `id` is empty for new ads (AdRotate assigns on import).

6. Placement strictness check:

- Export dimensions should only be from active placements for that site.
- Testing with a non-active dimension filter should yield zero rows.
