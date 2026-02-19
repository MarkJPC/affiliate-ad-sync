"""Shared pytest fixtures for sync-service tests."""

import sqlite3
from pathlib import Path

import pytest

from src.config import load_config

# Path to the SQLite schema
SCHEMA_FILE = Path(__file__).parent.parent.parent / "database" / "schema.sqlite.sql"


@pytest.fixture
def conn():
    """Create an in-memory SQLite database with the schema."""
    connection = sqlite3.connect(":memory:")
    connection.row_factory = sqlite3.Row
    connection.execute("PRAGMA foreign_keys = ON")

    schema_sql = SCHEMA_FILE.read_text(encoding="utf-8")
    connection.executescript(schema_sql)

    yield connection
    connection.close()


@pytest.fixture
def flexoffers_client():
    """Provide FlexOffersClient for first configured domain.

    Skips test if FLEXOFFERS_DOMAIN_KEYS environment variable is not set.
    """
    from src.networks.flexoffers import FlexOffersClient

    config = load_config()
    if not config.flexoffers_domain_keys:
        pytest.skip("FLEXOFFERS_DOMAIN_KEYS not configured")

    domain, api_key = next(iter(config.flexoffers_domain_keys.items()))
    client = FlexOffersClient(api_key, domain=domain)
    yield client
    client.close()


@pytest.fixture
def awin_client():
    """Provide AwinClient with configured credentials.

    Skips test if AWIN_API_TOKEN or AWIN_PUBLISHER_ID is not set.
    """
    from src.networks.awin import AwinClient

    config = load_config()
    if not config.awin_api_token or not config.awin_publisher_id:
        pytest.skip("AWIN_API_TOKEN or AWIN_PUBLISHER_ID not configured")

    client = AwinClient(config.awin_api_token, config.awin_publisher_id)
    yield client
    client.close()


@pytest.fixture
def cj_client():
    """Provide CJClient with configured credentials.

    Skips test if CJ_API_TOKEN, CJ_CID, or CJ_WEBSITE_ID is not set.
    """
    from src.networks.cj import CJClient

    config = load_config()
    if not config.cj_api_token or not config.cj_cid or not config.cj_website_id:
        pytest.skip("CJ_API_TOKEN, CJ_CID, or CJ_WEBSITE_ID not configured")

    client = CJClient(config.cj_api_token, config.cj_cid, config.cj_website_id)
    yield client
    client.close()


@pytest.fixture
def impact_client():
    """Provide ImpactClient with configured credentials.

    Skips test if IMPACT_ACCOUNT_SID or IMPACT_AUTH_TOKEN is not set.
    """
    from src.networks.impact import ImpactClient

    config = load_config()
    if not config.impact_account_sid or not config.impact_auth_token:
        pytest.skip("IMPACT_ACCOUNT_SID or IMPACT_AUTH_TOKEN not configured")

    client = ImpactClient(config.impact_account_sid, config.impact_auth_token)
    yield client
    client.close()
