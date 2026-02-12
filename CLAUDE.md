# Affiliate Ad Sync System

Centralized system to sync affiliate ads from 4 networks to WordPress sites via AdRotate.

## Project Overview

- **Timeline**: Jan 19 - March 13, 2026 (Riipen project)
- **Team**: Mark (lead + FlexOffers), Rag (CJ + Impact), Nadia (Awin + QA)

## Architecture

```
FlexOffers API ─┐
Awin API ───────┼──► sync-service (Python) ──► MySQL (cPanel)
CJ API ─────────┤    [GitHub Actions cron]          │
Impact API ─────┘                                   │
                                                    ▼
                                        admin-dashboard (Laravel)
                                        [cPanel via FTP]
                                                    │
                                                    ▼
                                        CSV Export → AdRotate (WordPress)
```

## Directory Structure

- `sync-service/` - Python scheduled job (runs via GitHub Actions cron)
- `admin-dashboard/` - Laravel admin interface (deployed on cPanel via FTP)
- `database/` - SQL schema and seed files (MySQL)
- `docs/` - Architecture and API documentation

## Tech Stack

- **Sync service**: Python 3.12 + uv, httpx, pymysql
- **Admin dashboard**: Laravel + PHP, Blade templates, Laravel Breeze (auth)
- **Database**: MySQL (cPanel/phpMyAdmin)
- **Hosting**: GitHub Actions (sync cron), cPanel (admin dashboard)

## Common Commands

### Sync Service
```bash
cd sync-service
uv sync                    # Install dependencies
uv run python -m src.main  # Run sync manually
uv run pytest              # Run tests
uv run python test_flexoffers.py  # Test FlexOffers API
```

### Admin Dashboard (Laravel)
```bash
cd admin-dashboard
composer install           # Install PHP dependencies
php artisan serve          # Start dev server (http://localhost:8000)
php artisan migrate        # Run database migrations
npm install && npm run build  # Build frontend assets
```

## Code Patterns

### sync-service
- `src/networks/` - API clients inherit from `NetworkClient` base class
- `src/mappers/` - Transform API responses to canonical schema
- Each network client implements `fetch_advertisers()` and `fetch_ads()`
- Database: pymysql with context manager connections

### admin-dashboard (Laravel)
- `app/Http/Controllers/` - Request handlers
- `app/Models/` - Eloquent models (Ad, Advertiser, Site, etc.)
- `resources/views/` - Blade templates
- `routes/web.php` - Route definitions
- Pattern: routes → controllers → models → views

## Environment Variables

See `.env.example` for required variables. Key ones:

### sync-service
```
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_USER=your_user
MYSQL_PASSWORD=your_password
MYSQL_DATABASE=affiliate_ads
FLEXOFFERS_DOMAIN_KEYS=domain1:key1,domain2:key2
```

See `docs/api-notes/` for network-specific API documentation.

### admin-dashboard (Laravel)
```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=affiliate_ads
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

## Database Schema

Schema files (see `database/`):
- `schema.mysql.sql` - MySQL 8.0+ (production on cPanel)
- `schema.postgres.sql` - PostgreSQL (alternative for local dev)

| Table | Description |
|-------|-------------|
| `advertisers` | Affiliate programs from all networks |
| `ads` | Canonical ad records with AdRotate fields |
| `sites` | WordPress sites we manage |
| `placements` | Ad slots with dimensions per site |
| `site_advertiser_rules` | Allow/deny advertisers per site |
| `site_ads` | Per-site ad approval status (many-to-many) |
| `sync_logs` | Audit trail for sync operations |
| `export_logs` | Audit trail for CSV exports |

**View**: `v_exportable_ads` - Pre-joined query for ads ready to export

## Deployment

### Sync Service
- Runs on GitHub Actions cron schedule
- Connects to cPanel MySQL remotely
- Credentials stored in GitHub Secrets

### Admin Dashboard
- Deploy via FTP to cPanel subdomain (e.g., `admin.thepartshops.com`)
- Document root should point to Laravel's `public/` folder
- Run `php artisan migrate` after first deploy
