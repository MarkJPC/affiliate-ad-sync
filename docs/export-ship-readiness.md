# Export Ship Readiness Checklist

Use this checklist before promoting export changes.

## 1) Automated regression checks

From `admin-dashboard`:

```bash
php artisan test --filter=BannerExportVerificationTest
php artisan test --filter=TextExportVerificationTest
php artisan test --filter=ExportControllerRegressionTest
```

Pass criteria:

- all tests pass
- no new failures in export-related assertions

## 2) Endpoint behavior checks

- `GET /api/export/preview` returns:
  - `status=ok`
  - useful message for empty vs non-empty results
  - consistent `summary` + `sample_rows`
- `POST /export/download` returns:
  - valid CSV stream
  - `X-Export-Row-Count` header
  - `X-Export-Empty` header
  - export log row written

## 3) Manual end-to-end checks

- run banner export for a site with known placement sizes and confirm expected rows
- run text export and confirm expected columns and non-empty affiliate links
- run one no-result export and confirm graceful behavior (header-only CSV + row-count metadata)

## 4) Performance sanity checks

- run at least one export on the largest local/staging site
- record row count and elapsed time
- verify no query timeouts or memory warnings

Recommended command examples:

```bash
time php artisan tinker --execute='$f=app(App\Services\ExportFilterService::class); $e=app(App\Services\ExportEngineService::class); $c=$f->normalize(["site_id"=>1,"export_type"=>"banner"]); $d=$e->buildDownloadPayload($c,"site-one.test"); dump($d["meta"]);'
```

```bash
time php artisan tinker --execute='$f=app(App\Services\ExportFilterService::class); $e=app(App\Services\ExportEngineService::class); $c=$f->normalize(["site_id"=>1,"export_type"=>"text"]); $d=$e->buildDownloadPayload($c,"site-one.test"); dump($d["meta"]);'
```

## 5) Release handoff criteria

- latest commit includes verification docs and tests
- CSV contracts for banner and text are unchanged from approved format
- export history records contain expected filename, row count, actor, and timestamp
