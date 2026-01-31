# System Architecture

## Overview

The Affiliate Ad Sync system centralizes ad management across 4 affiliate networks and distributes approved ads to WordPress sites via AdRotate.

## System Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           AFFILIATE NETWORKS                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │  FlexOffers  │  │     Awin     │  │      CJ      │  │    Impact    │    │
│  │    (Mark)    │  │   (Nadia)    │  │    (Rag)     │  │    (Rag)     │    │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘    │
└─────────┼─────────────────┼─────────────────┼─────────────────┼────────────┘
          │                 │                 │                 │
          │    REST APIs    │                 │                 │
          ▼                 ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         SYNC SERVICE (Python)                               │
│                      GitHub Actions - Every 6 hours                         │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  NetworkClient (Abstract)                                            │   │
│  │  ├── FlexOffersClient    ├── AwinClient                              │   │
│  │  ├── CJClient            └── ImpactClient                            │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Mapper (Abstract) - Transforms API responses → Canonical Schema     │   │
│  │  ├── FlexOffersMapper    ├── AwinMapper                              │   │
│  │  ├── CJMapper            └── ImpactMapper                            │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                          MySQL (cPanel)                                     │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐     │
│  │ advertisers │  │    ads      │  │   sites     │  │   placements    │     │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────────┘     │
│  ┌─────────────────────────┐  ┌───────────────┐  ┌───────────────────┐      │
│  │ site_advertiser_rules   │  │   site_ads    │  │    sync_logs      │      │
│  └─────────────────────────┘  └───────────────┘  └───────────────────┘      │
│  ┌───────────────┐  ┌─────────────────────────────────────────────────┐     │
│  │  export_logs  │  │         v_exportable_ads (VIEW)                 │     │
│  └───────────────┘  └─────────────────────────────────────────────────┘     │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                      ADMIN DASHBOARD (Laravel)                              │
│                          cPanel Hosting                                     │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Routes: /ads, /advertisers, /sites                                  │   │
│  │  Controllers: AdController, AdvertiserController, SiteController     │   │
│  │  Views: Blade templates with Tailwind CSS                            │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│  Features:                                                                  │
│  • View/filter synced ads                                                   │
│  • Approve/deny ads with reasons                                            │
│  • Configure sites and placements                                           │
│  • Export CSV for AdRotate import                                           │
│  • Authentication via Laravel Breeze                                        │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   │
                                   │  CSV Export
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         WORDPRESS SITES                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Boating    │  │     RV       │  │   Camping    │  │  Powersports │     │
│  │    Site      │  │    Site      │  │    Site      │  │    Site      │     │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘     │
│         │                 │                 │                 │             │
│         └─────────────────┼─────────────────┼─────────────────┘             │
│                           ▼                                                 │
│                    AdRotate Plugin                                          │
│                   (Manages ad display)                                     │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Tech Stack

| Component | Technology | Hosting |
|-----------|------------|---------|
| Sync Service | Python 3.12, httpx, pymysql | GitHub Actions |
| Database | MySQL | cPanel (phpMyAdmin) |
| Admin Dashboard | Laravel, Blade, Tailwind | cPanel (FTP deploy) |
| WordPress | AdRotate plugin | Client hosting |

## Data Flow

1. **Sync Service** fetches advertisers and ads from each network API
2. **Mappers** transform network-specific responses to canonical schema
3. **MySQL Database** stores all ads with change detection (raw_hash)
4. **Admin Dashboard** allows review/approval of ads per-site (via `site_ads` table)
5. **Export** generates CSV for AdRotate import using `v_exportable_ads` view
6. **Audit Logs** track sync operations (`sync_logs`) and exports (`export_logs`)

## Approval Workflow

Each ad can be approved/denied per-site via the `site_ads` table:

```
New Ad Synced → site_ads record created (pending)
                         ↓
              [Admin Reviews in Dashboard]
                         ↓
              approved → included in v_exportable_ads
                 OR
              denied → excluded (with reason, deny_is_permanent flag)
```

## Key Design Decisions

1. **Canonical Schema**: All networks map to a single ads table structure
2. **Change Detection**: SHA-256 hash of raw API response detects updates
3. **Site Rules**: Allow/deny advertisers per site for targeting
4. **Placement Matching**: Ads matched to placements by width/height
5. **MySQL + cPanel**: Simplified hosting on existing infrastructure
6. **Laravel**: PHP framework with built-in auth, ORM, and templating

## Deployment

### Sync Service (GitHub Actions)
```yaml
# .github/workflows/sync.yml
on:
  schedule:
    - cron: '0 */6 * * *'  # Every 6 hours
jobs:
  sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: astral-sh/setup-uv@v4
      - run: cd sync-service && uv sync && uv run python -m src.main
    env:
      MYSQL_HOST: ${{ secrets.MYSQL_HOST }}
      MYSQL_USER: ${{ secrets.MYSQL_USER }}
      MYSQL_PASSWORD: ${{ secrets.MYSQL_PASSWORD }}
      MYSQL_DATABASE: ${{ secrets.MYSQL_DATABASE }}
```

### Admin Dashboard (cPanel)
1. FTP files to subdomain folder (e.g., `admin.thepartshops.com`)
2. Set document root to `public/` folder
3. Configure `.env` with database credentials
4. Run `php artisan migrate` via SSH or web console
