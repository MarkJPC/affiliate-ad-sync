# Affiliate Ad Sync System

Centralized system to sync affiliate ads from 4 networks (FlexOffers, Awin, CJ, Impact) to WordPress sites via AdRotate.

## Message from Mark (Tech Lead)

Hey team! Welcome to the project. Here's what I need from you right now:

**Immediate priorities:**
1. **Get familiar with the codebase** - Clone the repo, explore the folder structure, and read through `docs/architecture.md`
2. **Review the architecture** - Let me know what you think! I'm open to feedback before we lock things in
3. **Send me your info** - I need your GitHub usernames and emails so I can add you to the repo and Supabase

**What to wait on:**
- Don't start writing integration code yet - I'm finalizing the Standard Ad schema right after our kickoff meeting
- Once the schema is locked and we have a solid foundation, we'll create git branches for each API service (e.g., `feature/cj-integration`, `feature/awin-integration`)

For now, focus on **research and documentation** - understand your network's APIs, note the authentication flows, and start mapping fields to what you see in `database/schema.sql` (draft).

Questions? Reach out anytime.

— Mark

---

## Quick Start

### Prerequisites

- **Python 3.12** with [uv](https://docs.astral.sh/uv/) package manager
- **Node.js 20+** with npm
- **Supabase** account (or local Postgres)

### 1. Clone and Set Up Environment

```bash
git clone <repo-url>
cd affiliate-ad-sync
cp .env.example .env  # Edit with your credentials
```

### 2. Sync Service (Python)

```bash
cd sync-service
uv sync                    # Install dependencies
uv run python -m src.main # Run sync manually
```

### 3. Admin Dashboard (Next.js)

```bash
cd admin-dashboard
npm install                # Install dependencies
npm run dev                # Start dev server (http://localhost:3000)
npm run build              # Build for production
```

## Project Structure

```
affiliate-ad-sync/
├── sync-service/              # Python sync job (GitHub Actions cron)
│   └── src/
│       ├── main.py            # Entry point
│       ├── config.py          # Environment config
│       ├── db.py              # Database connection
│       ├── networks/          # API clients (1 per network)
│       │   ├── base.py        # Abstract NetworkClient
│       │   ├── flexoffers.py  # Mark
│       │   ├── awin.py        # Nadia
│       │   ├── cj.py          # Rag
│       │   └── impact.py      # Rag
│       └── mappers/           # Response → Standard Ad transformers
│           ├── base.py        # Abstract Mapper
│           └── [network].py   # One per network
│
├── admin-dashboard/           # Next.js admin UI (Vercel)
│   └── src/
│       ├── app/               # Pages and API routes
│       └── lib/               # Controllers and queries
│
├── database/
│   └── schema.sql             # Database schema (run this in Supabase)
│
└── docs/
    ├── architecture.md        # System diagram
    └── api-notes/             # Network API documentation
```

## How to Contribute

### For Network Integrations (Rag & Nadia)

1. **Implement your network client** in `sync-service/src/networks/[network].py`
   - Inherit from `NetworkClient` base class
   - Implement `fetch_advertisers()` and `fetch_ads(advertiser_id)`

2. **Implement your mapper** in `sync-service/src/mappers/[network].py`
   - Inherit from `Mapper` base class
   - Transform API responses to match the Standard Ad schema

3. **Document your API research** in `docs/api-notes/[network].md`
   - Authentication flow
   - Endpoints used
   - Field mappings
   - Assumptions and edge cases

### Workflow

- Work on `main` branch for now (we'll discuss branching at kickoff)
- Test your code locally with `uv run pytest`
- Commit with clear messages describing what you added

## Standard Ad Schema (Canonical Format)

**DRAFT** - This schema will be finalized after the kickoff meeting. Wait for Mark's confirmation before coding against it.

All networks map to this schema. See `database/schema.sql` for full definition.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `network` | VARCHAR(20) | Yes | flexoffers, awin, cj, impact |
| `network_link_id` | VARCHAR(100) | Yes | Unique ID from network |
| `creative_type` | VARCHAR(20) | Yes | banner, text, html |
| `width` | INTEGER | Yes | Ad width in pixels |
| `height` | INTEGER | Yes | Ad height in pixels |
| `tracking_url` | TEXT | Yes | Affiliate tracking URL |
| `destination_url` | TEXT | No | Landing page URL |
| `image_url` | TEXT | No | Banner image URL |
| `html_snippet` | TEXT | No | Raw HTML for HTML ads |
| `status` | VARCHAR(20) | Yes | active, paused, expired |
| `approval_status` | VARCHAR(20) | Yes | pending, approved, denied |

## Environment Variables

Create `.env` in each service directory. Required variables:

```
DATABASE_URL=postgresql://user:pass@host:5432/db

# Network APIs (add yours as you implement)
FLEXOFFERS_API_KEY=
AWIN_API_TOKEN=
AWIN_PUBLISHER_ID=
CJ_API_TOKEN=
CJ_WEBSITE_ID=
IMPACT_ACCOUNT_SID=
IMPACT_AUTH_TOKEN=
```

## Team Assignments

| Person | Networks | Files to Modify |
|--------|----------|-----------------|
| Mark | FlexOffers + Core System | `networks/flexoffers.py`, schema, export |
| Rag | CJ + Impact | `networks/cj.py`, `impact.py`, mappers |
| Nadia | Awin + FlexOffers | `networks/awin.py`, `mappers/awin.py` |
