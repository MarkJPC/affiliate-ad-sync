"""End-to-end test: FlexOffers API → SQLite database → verification."""

import logging
import subprocess
import sys
from pathlib import Path

from src.networks.flexoffers import FlexOffersClient
from src.config import load_config
from src.db import get_connection, _execute_query, _use_sqlite

# Minimal logging (only warnings and errors)
logging.basicConfig(level=logging.WARNING, format='%(name)s - %(levelname)s - %(message)s')

# Project root (one level up from sync-service)
PROJECT_ROOT = Path(__file__).parent.parent


def reset_database():
    """Reset the SQLite database to a clean state."""
    print("Resetting database...")
    result = subprocess.run(
        [sys.executable, "setup-dev-db.py", "--reset"],
        cwd=PROJECT_ROOT,
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        print(f"Error resetting database: {result.stderr}")
        sys.exit(1)
    print("Database reset complete.\n")


def verify_database(conn):
    """Query database to verify sync results."""
    print("\n" + "=" * 60)
    print("DATABASE VERIFICATION")
    print("=" * 60)

    # Count advertisers
    advertisers = _execute_query(conn, "SELECT COUNT(*) as count FROM advertisers")
    advertiser_count = advertisers[0]["count"]
    print(f"Advertisers in DB: {advertiser_count}")

    # Count ads
    ads = _execute_query(conn, "SELECT COUNT(*) as count FROM ads")
    ad_count = ads[0]["count"]
    print(f"Ads in DB: {ad_count}")

    # Count by creative type
    banners = _execute_query(
        conn, "SELECT COUNT(*) as count FROM ads WHERE width > 0 AND height > 0"
    )
    text_links = _execute_query(
        conn, "SELECT COUNT(*) as count FROM ads WHERE width = 0 AND height = 0"
    )
    print(f"  - Banners (width > 0, height > 0): {banners[0]['count']}")
    print(f"  - Text links (0x0): {text_links[0]['count']}")

    # Verify sync log was created
    sync_logs = _execute_query(
        conn, "SELECT * FROM sync_logs WHERE network = 'flexoffers' ORDER BY started_at DESC LIMIT 1"
    )
    if sync_logs:
        log = sync_logs[0]
        print(f"\nSync log entry:")
        print(f"  - Advertisers synced: {log['advertisers_synced']}")
        print(f"  - Ads synced: {log['ads_synced']}")
        print(f"  - Errors: {log['errors']}")
        if log.get("duration_seconds"):
            print(f"  - Duration: {log['duration_seconds']}s")

    # Sample advertiser
    if advertiser_count > 0:
        sample_adv = _execute_query(conn, "SELECT name, website_url FROM advertisers LIMIT 3")
        print(f"\nSample advertisers:")
        for adv in sample_adv:
            print(f"  - {adv['name']}")

    # Sample ads
    if ad_count > 0:
        sample_ads = _execute_query(
            conn, "SELECT advert_name, width, height FROM ads LIMIT 3"
        )
        print(f"\nSample ads:")
        for ad in sample_ads:
            print(f"  - {ad['advert_name']} ({ad['width']}x{ad['height']})")


def main():
    # Verify SQLite mode
    if not _use_sqlite:
        print("Error: This test requires SQLite mode.")
        print("Set DB_PATH in .env (e.g., DB_PATH=../database/affiliate_ads.sqlite)")
        sys.exit(1)

    # Load config
    config = load_config()
    if not config.flexoffers_domain_keys:
        print("No FlexOffers domain keys configured.")
        print("Set FLEXOFFERS_DOMAIN_KEYS in .env file.")
        print("Format: domain1:key1,domain2:key2")
        sys.exit(1)

    # Reset database
    reset_database()

    # Sync each domain
    print("=" * 60)
    print("SYNCING FLEXOFFERS DATA")
    print("=" * 60)

    total_stats = {
        "advertisers_synced": 0,
        "ads_synced": 0,
        "ads_updated": 0,
        "errors": 0,
    }

    with get_connection() as conn:
        for domain, api_key in config.flexoffers_domain_keys.items():
            print(f"\n[{domain}] Starting sync...")

            client = FlexOffersClient(api_key, domain=domain)
            try:
                stats = client.sync(conn)
                print(f"[{domain}] Done: {stats['advertisers_synced']} advertisers, "
                      f"{stats['ads_synced']} ads, {stats['errors']} errors")

                # Accumulate totals
                for key in total_stats:
                    total_stats[key] += stats.get(key, 0)

            except Exception as e:
                print(f"[{domain}] Sync failed: {e}")
                total_stats["errors"] += 1
            finally:
                client.close()

    # Print summary
    print("\n" + "=" * 60)
    print("SYNC SUMMARY")
    print("=" * 60)
    print(f"Total advertisers synced: {total_stats['advertisers_synced']}")
    print(f"Total ads synced: {total_stats['ads_synced']}")
    print(f"Total errors: {total_stats['errors']}")

    # Verify data in database
    with get_connection() as conn:
        verify_database(conn)

    print("\n" + "=" * 60)
    print("TEST COMPLETE")
    print("=" * 60)
    print(f"Database file: {PROJECT_ROOT / 'database' / 'affiliate_ads.sqlite'}")
    print("Verify with: python setup-dev-db.py --verify")


if __name__ == "__main__":
    main()
