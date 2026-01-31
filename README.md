# Affiliate Ad Sync System

Centralized system to sync affiliate ads from 4 networks to WordPress sites via AdRotate.

## Project Overview

- **Timeline**: Jan 19 - March 13, 2026 (Riipen project)
- **Team**: Mark (lead + FlexOffers), Rag (CJ + Impact), Nadia (Awin + QA)

## Architecture

```
FlexOffers API ─┐
Awin API ───────┼──► sync-service (Python) ──► MySQL (cPanel)
CJ API ─────────┤                                   │
Impact API ─────┘                                   ▼
                                            admin-dashboard (Laravel)
                                                    │
                                                    ▼
                                            CSV Export → AdRotate (WordPress)
```

## Directory Structure

```
affiliate-ad-sync/
├── sync-service/           # Python scheduled job (runs via GitHub Actions)
│   ├── src/
│   │   ├── networks/       # API clients (one per network)
│   │   │   ├── base.py     # Abstract NetworkClient
│   │   │   ├── flexoffers.py
│   │   │   ├── awin.py
│   │   │   ├── cj.py
│   │   │   └── impact.py
│   │   ├── mappers/        # Transform API → canonical schema
│   │   │   ├── base.py     # Abstract Mapper
│   │   │   └── ...
│   │   ├── db.py           # MySQL connection
│   │   └── main.py         # Entry point
│   ├── tests/
│   ├── pyproject.toml
│   └── .env.example
│
├── admin-dashboard/        # Laravel admin interface (deployed via FTP)
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── ...
│   ├── resources/views/    # Blade templates
│   ├── routes/web.php
│   ├── .env.example
│   └── ...
│
├── database/
│   ├── schema.mysql.sql    # MySQL schema (production - import via phpMyAdmin)
│   └── schema.postgres.sql # PostgreSQL schema (alternative for local dev)
│
└── docs/
    ├── architecture.md
    ├── adrotate-csv-contract.md
    └── filtering-rules.md
```

## Tech Stack

| Component | Technology | Hosting |
|-----------|------------|---------|
| Sync Service | Python 3.12, httpx, PyMySQL | GitHub Actions (cron) |
| Database | MySQL | cPanel (phpMyAdmin access) |
| Admin Dashboard | Laravel 11, Blade, Tailwind | cPanel (FTP deploy) |
| WordPress | AdRotate plugin | Client's existing hosting |

---

## How to Run Locally

### Prerequisites

- Python 3.12+ with [uv](https://docs.astral.sh/uv/) package manager
- PHP 8.2+ with Composer
- MySQL 8.0+ (or use a Docker container)
- Node.js 18+ (for Tailwind CSS compilation in Laravel)

### 1. Clone the Repository

```bash
git clone https://github.com/MARKJPC/affiliate-ad-sync.git
cd affiliate-ad-sync
```

### 2. Set Up the Database

**Option A: Local MySQL (recommended)**
```bash
mysql -u root -p
CREATE DATABASE affiliate_ads;
USE affiliate_ads;
SOURCE database/schema.mysql.sql;
```

**Option B: Docker MySQL**
```bash
docker run -d \
  --name affiliate-mysql \
  -e MYSQL_ROOT_PASSWORD=rootpass \
  -e MYSQL_DATABASE=affiliate_ads \
  -p 3306:3306 \
  mysql:8.0

# Wait 30 seconds for MySQL to start, then import schema
docker exec -i affiliate-mysql mysql -uroot -prootpass affiliate_ads < database/schema.mysql.sql
```

**Option C: PostgreSQL (alternative)**
```bash
# If you prefer PostgreSQL for local dev, use the Postgres schema instead
psql -U postgres -c "CREATE DATABASE affiliate_ads;"
psql -U postgres -d affiliate_ads -f database/schema.postgres.sql
```

### 3. Run the Sync Service

```bash
cd sync-service

# Copy environment template
cp .env.example .env

# Edit .env with your credentials:
# MYSQL_HOST=localhost
# MYSQL_USER=root
# MYSQL_PASSWORD=rootpass
# MYSQL_DATABASE=affiliate_ads
# FLEXOFFERS_API_KEY=...
# AWIN_API_TOKEN=...

# Install dependencies
uv sync

# Run sync manually
uv run python -m src.main

# Run tests
uv run pytest
```

### 4. Run the Admin Dashboard

```bash
cd admin-dashboard

# Install PHP dependencies
composer install

# Copy environment template
cp .env.example .env

# Generate app key
php artisan key:generate

# Edit .env with database credentials:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=affiliate_ads
# DB_USERNAME=root
# DB_PASSWORD=rootpass

# Run migrations (if using Laravel migrations instead of raw SQL)
php artisan migrate

# Install and compile frontend assets
npm install
npm run dev

# Start development server
php artisan serve
# Visit http://localhost:8000
```

---

## How Code is Organized

### Sync Service (Python)

The sync service follows a **client → mapper → database** pipeline:

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│ NetworkClient   │────►│ Mapper          │────►│ Database        │
│ (fetch raw API) │     │ (normalize)     │     │ (upsert)        │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

**Key files:**
- `src/networks/base.py` - Abstract `NetworkClient` with common methods
- `src/networks/flexoffers.py` - FlexOffers-specific API calls
- `src/mappers/base.py` - Abstract `Mapper` with canonical field extraction
- `src/db.py` - MySQL connection and upsert helpers
- `src/main.py` - Orchestrates the sync process

**Pattern: Each network has its own client + mapper**
```python
# src/networks/flexoffers.py
class FlexOffersClient(NetworkClient):
    def fetch_advertisers(self) -> list[dict]:
        """Fetch raw advertiser data from API"""
        ...
    
    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        """Fetch raw ad/creative data from API"""
        ...

# src/mappers/flexoffers.py
class FlexOffersMapper(Mapper):
    def to_canonical_ad(self, raw: dict) -> CanonicalAd:
        """Transform FlexOffers response to standard schema"""
        ...
```

### Admin Dashboard (Laravel)

Standard Laravel MVC structure:

**Routes** (`routes/web.php`)
```php
Route::resource('ads', AdController::class);
Route::resource('advertisers', AdvertiserController::class);
Route::resource('sites', SiteController::class);
Route::get('export/{site}', [ExportController::class, 'csv']);
```

**Controllers** (`app/Http/Controllers/`)
- `AdController` - View, approve/deny ads
- `AdvertiserController` - Manage advertiser allow/deny lists
- `SiteController` - Configure sites and placements
- `ExportController` - Generate AdRotate CSV

**Models** (`app/Models/`)
- `Ad`, `Advertiser`, `Site`, `Placement`, `SiteAdvertiserRule`

**Views** (`resources/views/`)
- Blade templates with Tailwind CSS

---

## How to Submit Work

### Branching Strategy

We use a simple **feature branch** workflow:

```
main (protected)
  └── feature/flexoffers-client (Mark)
  └── feature/cj-client (Rag)
  └── feature/awin-client (Nadia)
```

### Workflow

1. **Create a feature branch** from `main`:
   ```bash
   git checkout main
   git pull origin main
   git checkout -b feature/your-feature-name
   ```

2. **Work on your feature**, committing regularly:
   ```bash
   git add .
   git commit -m "Add FlexOffers advertiser fetch"
   ```

3. **Push your branch**:
   ```bash
   git push -u origin feature/your-feature-name
   ```

4. **Open a Pull Request** on GitHub:
   - Base: `main`
   - Compare: `feature/your-feature-name`
   - Add a description of what you changed
   - Request review from Mark (or another team member)

5. **After review**, Mark will merge to `main`

### Commit Message Format

Keep it simple and descriptive:
```
Add FlexOffers advertiser API client
Fix MySQL connection retry logic
Update schema with geo_countries field
```

### What to Commit

✅ **Do commit:**
- Source code changes
- Tests
- Documentation updates
- Sample API responses (in `docs/samples/`)

❌ **Don't commit:**
- `.env` files (use `.env.example` as template)
- API keys or passwords
- `node_modules/`, `vendor/`, `__pycache__/`
- IDE-specific files (`.idea/`, `.vscode/`)

---

## Environment Variables

### Sync Service (`.env`)

```bash
# Database (Richard's cPanel MySQL)
MYSQL_HOST=mysql.thepartshops.com      # Or IP address
MYSQL_USER=affiliate_user
MYSQL_PASSWORD=********
MYSQL_DATABASE=affiliate_ads

# FlexOffers (Mark)
FLEXOFFERS_API_KEY_RVTRAVEL=a0fb95df-e707-4e71-877a-f5bb213ab26e
FLEXOFFERS_API_KEY_CAMPING=604892e9-596a-43dc-9aff-5c4de8b805a1

# Awin (Nadia)
AWIN_API_TOKEN=a0858024-b0b0-4cec-b2e0-43706e161709

# CJ (Rag) - PENDING
CJ_API_KEY=

# Impact (Rag) - PENDING
IMPACT_ACCOUNT_SID=
IMPACT_AUTH_TOKEN=
```

### Admin Dashboard (`.env`)

```bash
APP_NAME="Affiliate Ad Sync"
APP_ENV=local
APP_KEY=  # Run: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1  # Or mysql.thepartshops.com for production
DB_PORT=3306
DB_DATABASE=affiliate_ads
DB_USERNAME=affiliate_user
DB_PASSWORD=********
```

---

## Deployment

### Sync Service (GitHub Actions)

The sync service runs automatically every 6 hours via GitHub Actions.

**File:** `.github/workflows/sync.yml`
```yaml
name: Sync Ads

on:
  schedule:
    - cron: '0 */6 * * *'  # Every 6 hours
  workflow_dispatch:        # Manual trigger

jobs:
  sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: astral-sh/setup-uv@v4
      - name: Run sync
        run: |
          cd sync-service
          uv sync
          uv run python -m src.main
        env:
          MYSQL_HOST: ${{ secrets.MYSQL_HOST }}
          MYSQL_USER: ${{ secrets.MYSQL_USER }}
          MYSQL_PASSWORD: ${{ secrets.MYSQL_PASSWORD }}
          MYSQL_DATABASE: ${{ secrets.MYSQL_DATABASE }}
          # ... other secrets
```

**Required GitHub Secrets:**
- `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`
- `FLEXOFFERS_API_KEY_RVTRAVEL`, `FLEXOFFERS_API_KEY_CAMPING`
- `AWIN_API_TOKEN`
- `CJ_API_KEY` (when available)
- `IMPACT_ACCOUNT_SID`, `IMPACT_AUTH_TOKEN` (when available)

### Admin Dashboard (FTP to cPanel)

1. **Build for production:**
   ```bash
   cd admin-dashboard
   composer install --optimize-autoloader --no-dev
   npm run build
   php artisan config:cache
   php artisan route:cache
   ```

2. **Upload via FTP:**
   - Host: `ftp.thepartshops.com`
   - Port: `21`
   - Username: `mark@thepartshops.com`
   - Upload to: root folder (visible as `cgi-bin` initially)

3. **Configure `.env` on server** with production database credentials

4. **Site accessible at:** `https://ads.thepartshops.com`

---

## Contact

- **Mark Cena** (Lead) - markjpcena@gmail.com
- **Rag Patel** (CJ + Impact) - ragspatel2006@gmail.com
- **Nadia Parhizgar** (Awin + QA) - nadiaparhizgar@gmail.com
- **Richard Gastmeier** (Client) - info@thepartshops.com