# Text Export Verification Pack

This checklist verifies text CSV behavior for:

- strict text export eligibility
- text row quality normalization
- final output contract and stability

## Automated checks

Run from `admin-dashboard`:

```bash
php artisan test --filter=TextExportVerificationTest
```

What this validates:

- only eligible rows are exported (`creative_type='text'`, active + approved ads, active advertisers, allowed site rules)
- invalid text rows are excluded (missing affiliate link)
- text values are normalized (fallback advertiser/anchor, normalized network, positive weight fallback)
- `approved_sites` is deduplicated and consistently formatted

## Manual checks (UI + payload)

1. Start app:

```bash
cd "/Users/ragpatel/affiliate-ad-sync/affiliate-ad-sync/admin-dashboard" && php artisan serve
```

2. Open export page:

`http://127.0.0.1:8000/export`

3. Select a site and choose `text` export, then download CSV.

4. Validate header order:

`advertiser_name,anchor_text,affiliate_link,approved_sites,network,weight`

5. Validate row quality:

- `affiliate_link` is always populated.
- `anchor_text` is human-readable (fallback to advertiser name when empty).
- `approved_sites` is a comma-separated list of allowed domains.
- `network` appears lowercase.
- `weight` is a positive number.

6. Filter behavior sanity:

- Apply `network` filter and confirm rows update.
- Apply `advertiser` filter and confirm rows update.
- Apply search text and confirm matching rows only.
