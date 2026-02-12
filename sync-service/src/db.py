"""Database connection and utilities for MySQL and SQLite.

Supports dual-database configuration:
- If DB_PATH env var is set → SQLite (local development)
- Otherwise uses MYSQL_* env vars → MySQL (production)
"""

import logging
import os
import sqlite3
import sys
import uuid
from contextlib import contextmanager
from pathlib import Path

from dotenv import load_dotenv

load_dotenv()

logger = logging.getLogger(__name__)

# Detect database type from environment
_db_path = os.getenv("DB_PATH")
_use_sqlite = _db_path is not None

if _use_sqlite:
    logger.info(f"Using SQLite database: {_db_path}")
else:
    import pymysql

    logger.info("Using MySQL database")

    # MySQL connection configuration from environment variables
    _mysql_config = {
        "host": os.getenv("MYSQL_HOST", "localhost"),
        "port": int(os.getenv("MYSQL_PORT", "3306")),
        "user": os.getenv("MYSQL_USER"),
        "password": os.getenv("MYSQL_PASSWORD"),
        "database": os.getenv("MYSQL_DATABASE"),
        "charset": "utf8mb4",
        "cursorclass": pymysql.cursors.DictCursor,
        "autocommit": False,
    }

    # Optional SSL configuration for remote connections
    _ssl_ca = os.getenv("MYSQL_SSL_CA")
    if _ssl_ca:
        _mysql_config["ssl"] = {"ca": _ssl_ca}


def generate_uuid() -> str:
    """Generate a UUID string for primary keys."""
    return str(uuid.uuid4())


def _create_connection():
    """Create a new database connection."""
    if _use_sqlite:
        # Resolve path relative to project root if needed
        db_path = Path(_db_path)
        if not db_path.is_absolute():
            # Relative to sync-service directory
            db_path = Path(__file__).parent.parent / _db_path

        conn = sqlite3.connect(str(db_path))
        conn.row_factory = sqlite3.Row
        # Enable foreign keys
        conn.execute("PRAGMA foreign_keys = ON")
        return conn
    else:
        return pymysql.connect(**_mysql_config)


def test_connection() -> None:
    """Test database connection on startup."""
    try:
        conn = _create_connection()
        if _use_sqlite:
            cursor = conn.execute("SELECT datetime('now') as now")
            result = cursor.fetchone()
            logger.info(f"SQLite connected at: {result['now']}")
        else:
            with conn.cursor() as cur:
                cur.execute("SELECT NOW()")
                result = cur.fetchone()
                logger.info(f"MySQL connected at: {result['NOW()']}")
        conn.close()
    except Exception as err:
        logger.error(f"Database connection failed: {err}")
        sys.exit(1)


@contextmanager
def get_connection():
    """Get a database connection (context manager).

    Usage:
        with get_connection() as conn:
            # For SQLite: use conn.execute() directly
            # For MySQL: use conn.cursor()
            ...
        # Auto-commits on success, rolls back on exception
    """
    conn = _create_connection()
    try:
        yield conn
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        conn.close()


def health_check() -> bool:
    """Verify database connection works."""
    try:
        conn = _create_connection()
        if _use_sqlite:
            cursor = conn.execute("SELECT 1 AS result")
            result = cursor.fetchone()
            conn.close()
            return result is not None and result["result"] == 1
        else:
            with conn.cursor() as cur:
                cur.execute("SELECT 1 AS result")
                result = cur.fetchone()
                conn.close()
                return result is not None and result["result"] == 1
    except Exception:
        return False


# =============================================================================
# HELPER FUNCTIONS FOR DUAL-DATABASE SUPPORT
# =============================================================================


def _execute_query(conn, sql: str, params: tuple = ()) -> list[dict]:
    """Execute a SELECT query and return results as list of dicts."""
    if _use_sqlite:
        cursor = conn.execute(sql, params)
        rows = cursor.fetchall()
        return [dict(row) for row in rows]
    else:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            return cur.fetchall()


def _execute_one(conn, sql: str, params: tuple = ()) -> dict | None:
    """Execute a SELECT query and return first result as dict."""
    if _use_sqlite:
        cursor = conn.execute(sql, params)
        row = cursor.fetchone()
        return dict(row) if row else None
    else:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            return cur.fetchone()


def _execute_write(conn, sql: str, params: tuple = ()) -> int:
    """Execute an INSERT/UPDATE query and return lastrowid."""
    if _use_sqlite:
        cursor = conn.execute(sql, params)
        return cursor.lastrowid
    else:
        with conn.cursor() as cur:
            cur.execute(sql, params)
            return cur.lastrowid


def _placeholder() -> str:
    """Return the correct placeholder for the database type."""
    return "?" if _use_sqlite else "%s"


def _now() -> str:
    """Return the correct NOW() function for the database type."""
    return "datetime('now')" if _use_sqlite else "NOW()"


# =============================================================================
# ADVERTISER UPSERT FUNCTIONS
# =============================================================================


def get_advertiser_hash(conn, network: str, network_advertiser_id: str) -> str | None:
    """Get existing raw_hash for an advertiser, or None if not found.

    Args:
        conn: Database connection.
        network: Network identifier (e.g., 'flexoffers').
        network_advertiser_id: Network-specific advertiser ID.

    Returns:
        The raw_hash if advertiser exists, None otherwise.
    """
    p = _placeholder()
    sql = f"SELECT raw_hash FROM advertisers WHERE network = {p} AND network_advertiser_id = {p}"
    row = _execute_one(conn, sql, (network, network_advertiser_id))
    return row["raw_hash"] if row else None


def upsert_advertiser(conn, data: dict) -> tuple[str, bool]:
    """Insert or update advertiser, skipping if raw_hash unchanged.

    Args:
        conn: Database connection.
        data: Advertiser data dict with keys: network, network_advertiser_id, name,
              website_url, category, epc, raw_hash.

    Returns:
        Tuple of (advertiser_id, was_changed). advertiser_id is the database UUID,
        was_changed is True if the record was inserted or updated.
    """
    existing_hash = get_advertiser_hash(conn, data["network"], data["network_advertiser_id"])

    if existing_hash == data["raw_hash"]:
        # No change - return existing ID
        p = _placeholder()
        sql = f"SELECT id FROM advertisers WHERE network = {p} AND network_advertiser_id = {p}"
        row = _execute_one(conn, sql, (data["network"], data["network_advertiser_id"]))
        return row["id"], False

    # Insert or update
    p = _placeholder()
    now = _now()

    if _use_sqlite:
        # Generate UUID for new records
        new_id = generate_uuid()
        sql = f"""INSERT INTO advertisers
                  (id, network, network_advertiser_id, name, website_url, category, epc, raw_hash, last_synced_at)
                  VALUES ({p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {now})
                  ON CONFLICT(network, network_advertiser_id) DO UPDATE SET
                      name = excluded.name,
                      website_url = excluded.website_url,
                      category = excluded.category,
                      epc = excluded.epc,
                      raw_hash = excluded.raw_hash,
                      last_synced_at = {now}"""
        params = (
            new_id,
            data["network"],
            data["network_advertiser_id"],
            data["name"],
            data.get("website_url"),
            data.get("category"),
            data.get("epc", 0),
            data["raw_hash"],
        )
        _execute_write(conn, sql, params)
    else:
        sql = f"""INSERT INTO advertisers
                  (network, network_advertiser_id, name, website_url, category, epc, raw_hash, last_synced_at)
                  VALUES ({p}, {p}, {p}, {p}, {p}, {p}, {p}, {now})
                  ON DUPLICATE KEY UPDATE
                      name = VALUES(name),
                      website_url = VALUES(website_url),
                      category = VALUES(category),
                      epc = VALUES(epc),
                      raw_hash = VALUES(raw_hash),
                      last_synced_at = {now}"""
        params = (
            data["network"],
            data["network_advertiser_id"],
            data["name"],
            data.get("website_url"),
            data.get("category"),
            data.get("epc", 0),
            data["raw_hash"],
        )
        _execute_write(conn, sql, params)

    # Fetch the ID
    sql = f"SELECT id FROM advertisers WHERE network = {p} AND network_advertiser_id = {p}"
    row = _execute_one(conn, sql, (data["network"], data["network_advertiser_id"]))
    return row["id"], True


# =============================================================================
# AD UPSERT FUNCTIONS
# =============================================================================


def get_ad_hash(conn, network: str, network_ad_id: str) -> str | None:
    """Get existing raw_hash for an ad, or None if not found.

    Args:
        conn: Database connection.
        network: Network identifier (e.g., 'flexoffers').
        network_ad_id: Network-specific ad/link ID.

    Returns:
        The raw_hash if ad exists, None otherwise.
    """
    p = _placeholder()
    sql = f"SELECT raw_hash FROM ads WHERE network = {p} AND network_ad_id = {p}"
    row = _execute_one(conn, sql, (network, network_ad_id))
    return row["raw_hash"] if row else None


def upsert_ad(conn, data: dict) -> tuple[str, bool]:
    """Insert or update ad, skipping if raw_hash unchanged.

    Args:
        conn: Database connection.
        data: Ad data dict with keys matching the ads table schema.
              Required: network, network_ad_id, advertiser_id, tracking_url,
              advert_name, bannercode, image_url, width, height, raw_hash.

    Returns:
        Tuple of (ad_id, was_changed). ad_id is the database UUID,
        was_changed is True if the record was inserted or updated.
    """
    existing_hash = get_ad_hash(conn, data["network"], data["network_ad_id"])

    if existing_hash == data["raw_hash"]:
        # No change - return existing ID
        p = _placeholder()
        sql = f"SELECT id FROM ads WHERE network = {p} AND network_ad_id = {p}"
        row = _execute_one(conn, sql, (data["network"], data["network_ad_id"]))
        return row["id"], False

    # Insert or update
    p = _placeholder()
    now = _now()

    # Common parameter values
    params_values = (
        data["network"],
        data["network_ad_id"],
        data["advertiser_id"],
        data.get("creative_type", "banner"),
        data["tracking_url"],
        data.get("destination_url"),
        data.get("status", "active"),
        data.get("epc", 0),
        data["raw_hash"],
        data["advert_name"],
        data["bannercode"],
        data.get("imagetype", ""),
        data["image_url"],
        data["width"],
        data["height"],
        data.get("campaign_name", "General Promotion"),
        data.get("enable_stats", "Y"),
        data.get("show_everyone", "Y"),
        data.get("show_desktop", "Y"),
        data.get("show_mobile", "Y"),
        data.get("show_tablet", "Y"),
        data.get("show_ios", "Y"),
        data.get("show_android", "Y"),
        data.get("weight", 2),
        data.get("autodelete", "Y"),
        data.get("autodisable", "N"),
        data.get("budget", 0),
        data.get("click_rate", 0),
        data.get("impression_rate", 0),
        data.get("state_required", "N"),
        data.get("geo_cities", "a:0:{}"),
        data.get("geo_states", "a:0:{}"),
        data.get("geo_countries", "a:0:{}"),
        data.get("schedule_start", 0),
        data.get("schedule_end", 2650941780),
    )

    if _use_sqlite:
        new_id = generate_uuid()
        sql = f"""INSERT INTO ads
                  (id, network, network_ad_id, advertiser_id, creative_type, tracking_url,
                   destination_url, status, epc, raw_hash, last_synced_at,
                   advert_name, bannercode, imagetype, image_url, width, height,
                   campaign_name, enable_stats, show_everyone, show_desktop, show_mobile,
                   show_tablet, show_ios, show_android, weight, autodelete, autodisable,
                   budget, click_rate, impression_rate, state_required,
                   geo_cities, geo_states, geo_countries, schedule_start, schedule_end)
                  VALUES ({p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {now},
                          {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p},
                          {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p},
                          {p}, {p}, {p}, {p}, {p})
                  ON CONFLICT(network, network_ad_id) DO UPDATE SET
                      advertiser_id = excluded.advertiser_id,
                      creative_type = excluded.creative_type,
                      tracking_url = excluded.tracking_url,
                      destination_url = excluded.destination_url,
                      status = excluded.status,
                      epc = excluded.epc,
                      raw_hash = excluded.raw_hash,
                      last_synced_at = {now},
                      advert_name = excluded.advert_name,
                      bannercode = excluded.bannercode,
                      imagetype = excluded.imagetype,
                      image_url = excluded.image_url,
                      width = excluded.width,
                      height = excluded.height,
                      campaign_name = excluded.campaign_name,
                      enable_stats = excluded.enable_stats,
                      show_everyone = excluded.show_everyone,
                      show_desktop = excluded.show_desktop,
                      show_mobile = excluded.show_mobile,
                      show_tablet = excluded.show_tablet,
                      show_ios = excluded.show_ios,
                      show_android = excluded.show_android,
                      weight = excluded.weight,
                      autodelete = excluded.autodelete,
                      autodisable = excluded.autodisable,
                      budget = excluded.budget,
                      click_rate = excluded.click_rate,
                      impression_rate = excluded.impression_rate,
                      state_required = excluded.state_required,
                      geo_cities = excluded.geo_cities,
                      geo_states = excluded.geo_states,
                      geo_countries = excluded.geo_countries,
                      schedule_start = excluded.schedule_start,
                      schedule_end = excluded.schedule_end"""
        params = (new_id,) + params_values
        _execute_write(conn, sql, params)
    else:
        sql = f"""INSERT INTO ads
                  (network, network_ad_id, advertiser_id, creative_type, tracking_url,
                   destination_url, status, epc, raw_hash, last_synced_at,
                   advert_name, bannercode, imagetype, image_url, width, height,
                   campaign_name, enable_stats, show_everyone, show_desktop, show_mobile,
                   show_tablet, show_ios, show_android, weight, autodelete, autodisable,
                   budget, click_rate, impression_rate, state_required,
                   geo_cities, geo_states, geo_countries, schedule_start, schedule_end)
                  VALUES ({p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {now},
                          {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p},
                          {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p}, {p},
                          {p}, {p}, {p}, {p}, {p})
                  ON DUPLICATE KEY UPDATE
                      advertiser_id = VALUES(advertiser_id),
                      creative_type = VALUES(creative_type),
                      tracking_url = VALUES(tracking_url),
                      destination_url = VALUES(destination_url),
                      status = VALUES(status),
                      epc = VALUES(epc),
                      raw_hash = VALUES(raw_hash),
                      last_synced_at = {now},
                      advert_name = VALUES(advert_name),
                      bannercode = VALUES(bannercode),
                      imagetype = VALUES(imagetype),
                      image_url = VALUES(image_url),
                      width = VALUES(width),
                      height = VALUES(height),
                      campaign_name = VALUES(campaign_name),
                      enable_stats = VALUES(enable_stats),
                      show_everyone = VALUES(show_everyone),
                      show_desktop = VALUES(show_desktop),
                      show_mobile = VALUES(show_mobile),
                      show_tablet = VALUES(show_tablet),
                      show_ios = VALUES(show_ios),
                      show_android = VALUES(show_android),
                      weight = VALUES(weight),
                      autodelete = VALUES(autodelete),
                      autodisable = VALUES(autodisable),
                      budget = VALUES(budget),
                      click_rate = VALUES(click_rate),
                      impression_rate = VALUES(impression_rate),
                      state_required = VALUES(state_required),
                      geo_cities = VALUES(geo_cities),
                      geo_states = VALUES(geo_states),
                      geo_countries = VALUES(geo_countries),
                      schedule_start = VALUES(schedule_start),
                      schedule_end = VALUES(schedule_end)"""
        _execute_write(conn, sql, params_values)

    # Fetch the ID
    sql = f"SELECT id FROM ads WHERE network = {p} AND network_ad_id = {p}"
    row = _execute_one(conn, sql, (data["network"], data["network_ad_id"]))
    return row["id"], True


# =============================================================================
# SYNC LOG FUNCTIONS
# =============================================================================


def create_sync_log(conn, network: str) -> str:
    """Create a new sync log entry when starting a sync.

    Args:
        conn: Database connection.
        network: Network identifier (e.g., 'flexoffers').

    Returns:
        The ID of the created sync log entry.
    """
    p = _placeholder()
    now = _now()

    if _use_sqlite:
        new_id = generate_uuid()
        sql = f"INSERT INTO sync_logs (id, network, started_at) VALUES ({p}, {p}, {now})"
        _execute_write(conn, sql, (new_id, network))
        return new_id
    else:
        sql = f"INSERT INTO sync_logs (network, started_at) VALUES ({p}, {now})"
        _execute_write(conn, sql, (network,))
        # Get the auto-generated ID
        row = _execute_one(conn, "SELECT LAST_INSERT_ID() AS id", ())
        return row["id"]


def update_sync_log(
    conn,
    log_id: str,
    advertisers_synced: int = 0,
    ads_synced: int = 0,
    ads_created: int = 0,
    ads_updated: int = 0,
    errors: int = 0,
    error_message: str | None = None,
) -> None:
    """Update a sync log entry with results.

    Args:
        conn: Database connection.
        log_id: The sync log ID to update.
        advertisers_synced: Number of advertisers processed.
        ads_synced: Total number of ads processed.
        ads_created: Number of new ads created.
        ads_updated: Number of existing ads updated.
        errors: Number of errors encountered.
        error_message: Error details if any.
    """
    p = _placeholder()
    now = _now()

    if _use_sqlite:
        sql = f"""UPDATE sync_logs
                  SET advertisers_synced = {p},
                      ads_synced = {p},
                      ads_created = {p},
                      ads_updated = {p},
                      errors = {p},
                      error_message = {p},
                      completed_at = {now},
                      duration_seconds = CAST((julianday({now}) - julianday(started_at)) * 86400 AS INTEGER)
                  WHERE id = {p}"""
    else:
        sql = f"""UPDATE sync_logs
                  SET advertisers_synced = {p},
                      ads_synced = {p},
                      ads_created = {p},
                      ads_updated = {p},
                      errors = {p},
                      error_message = {p},
                      completed_at = {now},
                      duration_seconds = TIMESTAMPDIFF(SECOND, started_at, {now})
                  WHERE id = {p}"""

    _execute_write(
        conn,
        sql,
        (
            advertisers_synced,
            ads_synced,
            ads_created,
            ads_updated,
            errors,
            error_message,
            log_id,
        ),
    )
