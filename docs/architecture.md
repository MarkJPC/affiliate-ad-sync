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
│                         SUPABASE POSTGRES                                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐     │
│  │ advertisers │  │    ads      │  │   sites     │  │   placements    │     │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────────┘     │
│  ┌─────────────────────────┐  ┌─────────────────────────────────────────┐   │
│  │ site_advertiser_rules   │  │       performance_metrics               │   │
│  └─────────────────────────┘  └─────────────────────────────────────────┘   │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                      ADMIN DASHBOARD (Next.js)                              │
│                           Vercel Hosting                                    │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │  Pages: /ads, /advertisers, /sites                                   │   │
│  │  API: /api/ads, /api/advertisers, /api/sites, /api/export            │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│  Features:                                                                  │
│  • View/filter synced ads                                                   │
│  • Approve/deny ads with reasons                                            │
│  • Configure sites and placements                                           │
│  • Export CSV for AdRotate import                                           │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   │
                                   │ CSV Export
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
│                   (Manages ad display)                                      │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Data Flow

1. **Sync Service** fetches advertisers and ads from each network API
2. **Mappers** transform network-specific responses to canonical schema
3. **Database** stores all ads with change detection (raw_hash)
4. **Admin Dashboard** allows review/approval of new ads
5. **Export** generates CSV for AdRotate import

## Approval Workflow

```
New Ad Synced → pending → [Admin Reviews] → approved/denied
                                ↓
                         denied with reason
                         (deny_is_permanent flag)
```

## Key Design Decisions

1. **Canonical Schema**: All networks map to a single ads table structure
2. **Change Detection**: SHA-256 hash of raw API response detects updates
3. **Site Rules**: Allow/deny advertisers per site for targeting
4. **Placement Matching**: Ads matched to placements by width/height
