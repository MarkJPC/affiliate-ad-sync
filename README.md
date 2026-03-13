# Affiliate Ad Sync

Automated system that pulls affiliate ads from multiple networks, lets you review and approve them, and exports ready-to-use CSV files for the AdRotate WordPress plugin.

## Architecture

```
FlexOffers API ─┐
Awin API ───────┼──► Sync Service (Python) ──► MySQL Database
CJ API ─────────┤    runs daily via               │
Impact API ─────┘    GitHub Actions                │
                                                   ▼
                                         Admin Dashboard (Laravel)
                                         review, approve, configure
                                                   │
                                                   ▼
                                         CSV Export → AdRotate (WordPress)
```

## How It Works

The sync service runs daily and pulls advertiser and ad data from four affiliate networks: **FlexOffers**, **Awin**, **CJ**, and **Impact**. All ads are normalized into a common format and stored in a central MySQL database. The admin dashboard provides a web interface for reviewing ads, approving or denying advertisers per site, and configuring geo-targeting. When ready, ads are exported as AdRotate-compatible CSV files and imported into WordPress across five managed sites.

## Live URLs

| Site | URL |
|------|-----|
| **Admin Dashboard** | https://ads.thepartshops.com |
| RV Travel Life | https://www.rvtravellife.com |
| This Old Campsite | https://thisoldcampsite.com |
| Marine Part Shop | https://marinepartshop.com |
| Powersports Part Shop | https://powersportspartshop.com |
| The Part Shops | https://thepartshops.com |

## Features

### Sync Service
- Daily automated sync from 4 affiliate networks (FlexOffers, Awin, CJ, Impact)
- Smart change detection — only updates records when data has changed
- Auto-cleanup of stale ads and advertisers
- Geo-targeting resolution for AdRotate compatibility
- Retry logic with rate-limit handling

### Admin Dashboard
- Dashboard with live stats and sync status
- Advertiser grid with filtering, bulk approve/deny per site, weight and geo assignment
- Ad review grid with visual previews, approve/deny, and "needs attention" indicators
- CSV export (banner + text) in AdRotate-compatible format with preview and diagnostics
- Sites and placements management with visual grid
- Geo region configuration
- Sync log viewer with manual trigger button
- Dark mode

## Tech Stack

| Component | Technology | Hosting |
|-----------|------------|---------|
| Sync Service | Python 3.12, httpx, PyMySQL | GitHub Actions (daily cron) |
| Admin Dashboard | Laravel 11, Livewire, Tailwind CSS | cPanel (FTP deploy) |
| Database | MySQL 8.0 | cPanel |
| WordPress Sites | AdRotate plugin | Client hosting |

## Getting Started (Local Development)

**Prerequisites:** Python 3.12+ with [uv](https://docs.astral.sh/uv/), PHP 8.2+ with Composer, Node.js 18+

```bash
# 1. Set up SQLite database (no MySQL needed locally)
python setup-dev-db.py

# 2. Run the sync service
cd sync-service
cp .env.example .env        # Add your API keys to .env
uv sync
uv run python -m src.main

# 3. Run the admin dashboard (in a separate terminal)
cd admin-dashboard
cp .env.example .env
composer install
php artisan key:generate
npm install && npm run dev
php artisan serve            # http://localhost:8000
```

Both services share the same SQLite file at `database/affiliate_ads.sqlite`. See each `.env.example` for configuration options.

## Project Structure

```
affiliate-ad-sync/
├── sync-service/          # Python sync job (GitHub Actions)
├── admin-dashboard/       # Laravel admin interface (cPanel)
├── database/              # SQL schemas, migrations, seed data
├── scripts/               # Utility and validation scripts
└── docs/                  # API notes and architecture docs
```
