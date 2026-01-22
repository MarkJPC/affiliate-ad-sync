# Affiliate Ad Sync System

Centralized system to sync affiliate ads from 4 networks to WordPress sites via AdRotate.

## Project Overview

- **Timeline**: Jan 19 - March 13, 2026 (Riipen project)
- **Team**: Mark (lead + FlexOffers), Rag (CJ + Impact), Nadia (Awin + QA)

## Architecture

```
FlexOffers API ─┐
Awin API ───────┼──► sync-service (Python) ──► Supabase Postgres
CJ API ─────────┤                                    │
Impact API ─────┘                                    ▼
                                              admin-dashboard (Next.js)
                                                     │
                                                     ▼
                                              CSV Export → AdRotate (WordPress)
```

## Directory Structure

- `sync-service/` - Python scheduled job (runs via GitHub Actions cron)
- `admin-dashboard/` - Next.js admin interface (deployed on Vercel)
- `database/` - SQL schema and seed files
- `docs/` - Architecture and API documentation

## Tech Stack

- **Sync service**: Python 3.12 + uv, httpx, psycopg
- **Admin dashboard**: Next.js 16 + TypeScript, Tailwind, shadcn/ui
- **Database**: Supabase Postgres
- **Hosting**: GitHub Actions (sync cron), Vercel (admin)

## Common Commands

### Sync Service
```bash
cd sync-service
uv sync                    # Install dependencies
uv run python src/main.py  # Run sync manually
uv run pytest              # Run tests
```

### Admin Dashboard
```bash
cd admin-dashboard
npm install       # Install dependencies
npm run dev       # Start dev server (http://localhost:3000)
npm run build     # Build for production
npm run lint      # Run linter
```

## Code Patterns

### sync-service
- `src/networks/` - API clients inherit from `NetworkClient` base class
- `src/mappers/` - Transform API responses to canonical schema
- Each network client implements `fetch_advertisers()` and `fetch_ads()`

### admin-dashboard
- `src/app/api/` - API routes (Next.js App Router)
- `src/lib/controllers/` - Business logic layer
- `src/lib/queries/` - Database queries (raw SQL with pg)
- Pattern: routes → controllers → queries

## Environment Variables

See `.env.example` for required variables. Key ones:
- `DATABASE_URL` - Supabase Postgres connection string
- `FLEXOFFERS_API_KEY`, `AWIN_API_TOKEN`, etc. - Network API credentials

## Database Schema

See `database/schema.sql`. Core tables:
- `advertisers` - Programs from all networks
- `ads` - Creative assets with approval workflow
- `sites` - WordPress sites
- `placements` - Ad slots on each site
- `site_advertiser_rules` - Allow/deny rules per site
