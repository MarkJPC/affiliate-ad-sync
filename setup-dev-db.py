#!/usr/bin/env python3
"""
Setup script for local SQLite development database.

Usage:
    python setup-dev-db.py          # Create fresh database
    python setup-dev-db.py --reset  # Drop and recreate
    python setup-dev-db.py --verify # Check database validity
"""

import argparse
import sqlite3
import sys
from pathlib import Path

# Paths
PROJECT_ROOT = Path(__file__).parent
DATABASE_DIR = PROJECT_ROOT / "database"
SCHEMA_FILE = DATABASE_DIR / "schema.sqlite.sql"
DATABASE_FILE = DATABASE_DIR / "affiliate_ads.sqlite"


def create_database() -> bool:
    """Create the SQLite database from schema file."""
    if not SCHEMA_FILE.exists():
        print(f"Error: Schema file not found at {SCHEMA_FILE}")
        return False

    print(f"Creating database at {DATABASE_FILE}...")

    try:
        # Read schema
        schema_sql = SCHEMA_FILE.read_text(encoding="utf-8")

        # Create database and execute schema
        conn = sqlite3.connect(DATABASE_FILE)
        conn.executescript(schema_sql)
        conn.commit()
        conn.close()

        print("Database created successfully!")
        return True

    except sqlite3.Error as e:
        print(f"SQLite error: {e}")
        return False
    except Exception as e:
        print(f"Error: {e}")
        return False


def reset_database() -> bool:
    """Drop existing database and recreate it."""
    if DATABASE_FILE.exists():
        print(f"Removing existing database at {DATABASE_FILE}...")
        DATABASE_FILE.unlink()

    return create_database()


def verify_database() -> bool:
    """Verify database structure and seed data."""
    if not DATABASE_FILE.exists():
        print(f"Error: Database not found at {DATABASE_FILE}")
        print("Run 'python setup-dev-db.py' to create it.")
        return False

    print(f"Verifying database at {DATABASE_FILE}...")

    try:
        conn = sqlite3.connect(DATABASE_FILE)
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()

        # Expected tables
        expected_tables = [
            "advertisers",
            "ads",
            "sites",
            "placements",
            "site_advertiser_rules",
            "site_ads",
            "sync_logs",
            "export_logs",
        ]

        # Check tables exist
        cursor.execute(
            "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
        )
        actual_tables = [row["name"] for row in cursor.fetchall()]

        print("\nTables found:")
        missing_tables = []
        for table in expected_tables:
            if table in actual_tables:
                print(f"  [OK] {table}")
            else:
                print(f"  [MISSING] {table}")
                missing_tables.append(table)

        if missing_tables:
            print(f"\nError: Missing tables: {missing_tables}")
            conn.close()
            return False

        # Check view exists
        cursor.execute(
            "SELECT name FROM sqlite_master WHERE type='view' AND name='v_exportable_ads'"
        )
        if cursor.fetchone():
            print("  [OK] v_exportable_ads (view)")
        else:
            print("  [MISSING] v_exportable_ads (view)")
            conn.close()
            return False

        # Check seed data
        print("\nSeed data:")
        cursor.execute("SELECT COUNT(*) as count FROM sites")
        site_count = cursor.fetchone()["count"]
        print(f"  Sites: {site_count}")

        cursor.execute("SELECT COUNT(*) as count FROM placements")
        placement_count = cursor.fetchone()["count"]
        print(f"  Placements: {placement_count}")

        cursor.execute("SELECT COUNT(*) as count FROM advertisers")
        advertiser_count = cursor.fetchone()["count"]
        print(f"  Advertisers: {advertiser_count}")

        cursor.execute("SELECT COUNT(*) as count FROM ads")
        ad_count = cursor.fetchone()["count"]
        print(f"  Ads: {ad_count}")

        # Check triggers
        cursor.execute(
            "SELECT name FROM sqlite_master WHERE type='trigger' ORDER BY name"
        )
        triggers = [row["name"] for row in cursor.fetchall()]
        print(f"\nTriggers found: {len(triggers)}")
        for trigger in triggers:
            print(f"  [OK] {trigger}")

        conn.close()

        if site_count >= 5 and placement_count >= 11:
            print("\nDatabase verification: PASSED")
            return True
        else:
            print("\nWarning: Seed data may be incomplete")
            return True

    except sqlite3.Error as e:
        print(f"SQLite error: {e}")
        return False
    except Exception as e:
        print(f"Error: {e}")
        return False


def main():
    parser = argparse.ArgumentParser(
        description="Setup local SQLite development database"
    )
    parser.add_argument(
        "--reset",
        action="store_true",
        help="Drop existing database and recreate",
    )
    parser.add_argument(
        "--verify",
        action="store_true",
        help="Verify database structure and seed data",
    )

    args = parser.parse_args()

    # Ensure database directory exists
    DATABASE_DIR.mkdir(parents=True, exist_ok=True)

    if args.verify:
        success = verify_database()
    elif args.reset:
        success = reset_database()
    else:
        if DATABASE_FILE.exists():
            print(f"Database already exists at {DATABASE_FILE}")
            print("Use --reset to drop and recreate, or --verify to check it.")
            sys.exit(0)
        success = create_database()

    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
