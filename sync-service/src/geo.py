"""Geo-targeting resolution for AdRotate ads.

Maps advertiser country codes to AdRotate PHP-serialized geo_countries strings
using the geo_regions table in the database.
"""

import logging
from urllib.parse import urlparse

logger = logging.getLogger(__name__)

# Module-level cache: loaded once per sync run
_region_cache: list[dict] | None = None


def get_geo_regions(conn) -> list[dict]:
    """Load geo_regions from DB, cached after first call.

    Returns:
        List of region dicts sorted by priority (most specific first).
    """
    global _region_cache
    if _region_cache is not None:
        return _region_cache

    from . import db

    rows = db._execute_query(
        conn, "SELECT name, priority, country_codes, adrotate_value FROM geo_regions ORDER BY priority"
    )
    _region_cache = rows
    logger.info(f"[geo] Loaded {len(rows)} geo regions")
    return _region_cache


def resolve_geo_countries(conn, country_code: str | None) -> str:
    """Resolve an advertiser's country code to an AdRotate geo_countries string.

    Looks up the geo_regions table (priority order) and returns the adrotate_value
    for the first region whose country_codes list contains this country.

    Args:
        conn: Database connection.
        country_code: ISO 2-letter country code (e.g. "US", "CA", "GB").

    Returns:
        PHP-serialized string for AdRotate's geo_countries field.
        Falls back to 'a:0:{}' (no restriction) if no match.
    """
    if not country_code:
        return "a:0:{}"

    country_code = country_code.upper().strip()
    regions = get_geo_regions(conn)

    for region in regions:
        codes = [c.strip().upper() for c in region["country_codes"].split(",")]
        if country_code in codes:
            logger.debug(f'[geo] "{country_code}" -> \'{region["name"]}\'')
            return region["adrotate_value"]

    # No match — fall back to no geo restriction
    logger.debug(f'[geo] "{country_code}" -> no matching region, using empty')
    return "a:0:{}"


# TLD-to-country mapping for CJ's infer_country_from_url
_TLD_COUNTRY_MAP = {
    ".ca": "CA",
    ".co.uk": "GB",
    ".uk": "GB",
    ".au": "AU",
    ".com.au": "AU",
    ".nz": "NZ",
    ".co.nz": "NZ",
    ".de": "DE",
    ".fr": "FR",
    ".it": "IT",
    ".es": "ES",
    ".nl": "NL",
    ".be": "BE",
    ".at": "AT",
    ".ch": "CH",
    ".se": "SE",
    ".no": "NO",
    ".dk": "DK",
    ".fi": "FI",
    ".ie": "IE",
    ".pt": "PT",
    ".pl": "PL",
}


def infer_country_from_url(url: str) -> str:
    """Infer country code from a URL's TLD.

    Used by CJ mapper which doesn't provide a country field.
    Checks multi-part TLDs first (e.g. .co.uk before .uk).

    Args:
        url: Website URL (e.g. "https://www.canadiantire.ca").

    Returns:
        ISO 2-letter country code, defaults to "US".
    """
    if not url:
        return "US"

    try:
        hostname = urlparse(url).hostname or ""
    except Exception:
        return "US"

    hostname = hostname.lower().rstrip(".")

    # Check longer TLDs first (e.g. .co.uk before .uk)
    for tld, code in sorted(_TLD_COUNTRY_MAP.items(), key=lambda x: -len(x[0])):
        if hostname.endswith(tld):
            return code

    return "US"


def clear_cache() -> None:
    """Clear the region cache (useful for testing)."""
    global _region_cache
    _region_cache = None
