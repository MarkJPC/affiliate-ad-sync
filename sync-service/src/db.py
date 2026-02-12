"""Database connection and utilities for MySQL."""

import logging
import os
import sys
from contextlib import contextmanager

import pymysql
from dotenv import load_dotenv

load_dotenv()

logger = logging.getLogger(__name__)

# MySQL connection configuration from environment variables
_config = {
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
    _config["ssl"] = {"ca": _ssl_ca}


def _create_connection():
    """Create a new MySQL connection."""
    return pymysql.connect(**_config)


def test_connection() -> None:
    """Test database connection on startup."""
    try:
        conn = _create_connection()
        with conn.cursor() as cur:
            cur.execute("SELECT NOW()")
            result = cur.fetchone()
            logger.info(f"MySQL connected at: {result['NOW()']}")
        conn.close()
    except Exception as err:
        logger.error(f"MySQL connection failed: {err}")
        sys.exit(1)


@contextmanager
def get_connection():
    """Get a MySQL connection (context manager).

    Usage:
        with get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute("SELECT * FROM ads")
                results = cur.fetchall()
            conn.commit()
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
        with conn.cursor() as cur:
            cur.execute("SELECT 1 AS result")
            result = cur.fetchone()
            conn.close()
            return result is not None and result["result"] == 1
    except Exception:
        return False


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
    with conn.cursor() as cur:
        cur.execute(
            "SELECT raw_hash FROM advertisers WHERE network = %s AND network_advertiser_id = %s",
            (network, network_advertiser_id),
        )
        row = cur.fetchone()
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
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id FROM advertisers WHERE network = %s AND network_advertiser_id = %s",
                (data["network"], data["network_advertiser_id"]),
            )
            return cur.fetchone()["id"], False

    # Insert or update
    with conn.cursor() as cur:
        cur.execute(
            """INSERT INTO advertisers
               (network, network_advertiser_id, name, website_url, category, epc, raw_hash, last_synced_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, NOW())
               ON DUPLICATE KEY UPDATE
                   name = VALUES(name),
                   website_url = VALUES(website_url),
                   category = VALUES(category),
                   epc = VALUES(epc),
                   raw_hash = VALUES(raw_hash),
                   last_synced_at = NOW()""",
            (
                data["network"],
                data["network_advertiser_id"],
                data["name"],
                data.get("website_url"),
                data.get("category"),
                data.get("epc", 0),
                data["raw_hash"],
            ),
        )
        cur.execute(
            "SELECT id FROM advertisers WHERE network = %s AND network_advertiser_id = %s",
            (data["network"], data["network_advertiser_id"]),
        )
        return cur.fetchone()["id"], True


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
    with conn.cursor() as cur:
        cur.execute(
            "SELECT raw_hash FROM ads WHERE network = %s AND network_ad_id = %s",
            (network, network_ad_id),
        )
        row = cur.fetchone()
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
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id FROM ads WHERE network = %s AND network_ad_id = %s",
                (data["network"], data["network_ad_id"]),
            )
            return cur.fetchone()["id"], False

    # Insert or update
    with conn.cursor() as cur:
        cur.execute(
            """INSERT INTO ads
               (network, network_ad_id, advertiser_id, creative_type, tracking_url,
                destination_url, status, epc, raw_hash, last_synced_at,
                advert_name, bannercode, imagetype, image_url, width, height,
                campaign_name, enable_stats, show_everyone, show_desktop, show_mobile,
                show_tablet, show_ios, show_android, weight, autodelete, autodisable,
                budget, click_rate, impression_rate, state_required,
                geo_cities, geo_states, geo_countries, schedule_start, schedule_end)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(),
                       %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                       %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                       %s, %s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE
                   advertiser_id = VALUES(advertiser_id),
                   creative_type = VALUES(creative_type),
                   tracking_url = VALUES(tracking_url),
                   destination_url = VALUES(destination_url),
                   status = VALUES(status),
                   epc = VALUES(epc),
                   raw_hash = VALUES(raw_hash),
                   last_synced_at = NOW(),
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
                   schedule_end = VALUES(schedule_end)""",
            (
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
            ),
        )
        cur.execute(
            "SELECT id FROM ads WHERE network = %s AND network_ad_id = %s",
            (data["network"], data["network_ad_id"]),
        )
        return cur.fetchone()["id"], True


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
    with conn.cursor() as cur:
        cur.execute(
            """INSERT INTO sync_logs (network, started_at)
               VALUES (%s, NOW())""",
            (network,),
        )
        cur.execute("SELECT LAST_INSERT_ID() AS id")
        return cur.fetchone()["id"]


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
    with conn.cursor() as cur:
        cur.execute(
            """UPDATE sync_logs
               SET advertisers_synced = %s,
                   ads_synced = %s,
                   ads_created = %s,
                   ads_updated = %s,
                   errors = %s,
                   error_message = %s,
                   completed_at = NOW(),
                   duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW())
               WHERE id = %s""",
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
