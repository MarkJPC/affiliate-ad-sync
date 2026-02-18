"""Tests for database functions against v2 SQLite schema."""

import sqlite3
from pathlib import Path

import pytest

from src import db

# Path to the v2 SQLite schema
SCHEMA_FILE = Path(__file__).parent.parent.parent / "database" / "schema.sqlite.sql"


@pytest.fixture
def conn():
    """Create an in-memory SQLite database with the v2 schema."""
    connection = sqlite3.connect(":memory:")
    connection.row_factory = sqlite3.Row
    connection.execute("PRAGMA foreign_keys = ON")

    schema_sql = SCHEMA_FILE.read_text(encoding="utf-8")
    connection.executescript(schema_sql)

    yield connection
    connection.close()


class TestGetAdvertiserHash:
    """Test get_advertiser_hash function."""

    def test_returns_hash_when_advertiser_exists(self, conn):
        """Should return raw_hash for existing advertiser."""
        conn.execute(
            "INSERT INTO advertisers (network, network_advertiser_id, name, raw_hash) "
            "VALUES ('flexoffers', '12345', 'Test', 'abc123hash')"
        )

        result = db.get_advertiser_hash(conn, "flexoffers", "12345")
        assert result == "abc123hash"

    def test_returns_none_when_advertiser_not_found(self, conn):
        """Should return None when advertiser doesn't exist."""
        result = db.get_advertiser_hash(conn, "flexoffers", "99999")
        assert result is None


class TestUpsertAdvertiser:
    """Test upsert_advertiser function."""

    def test_inserts_new_advertiser(self, conn):
        """Should insert when advertiser doesn't exist."""
        data = {
            "network": "flexoffers",
            "network_advertiser_id": "99999",
            "name": "New Advertiser",
            "website_url": "https://example.com",
            "category": "Test",
            "epc": 0.5,
            "raw_hash": "new_hash",
        }

        advertiser_id, was_changed = db.upsert_advertiser(conn, data)

        assert isinstance(advertiser_id, int)
        assert was_changed is True

        # Verify it's in the database
        row = conn.execute(
            "SELECT * FROM advertisers WHERE id = ?", (advertiser_id,)
        ).fetchone()
        assert row["name"] == "New Advertiser"
        assert row["is_active"] == 1

    def test_skips_update_when_hash_unchanged(self, conn):
        """Should return existing ID without update when hash matches."""
        conn.execute(
            "INSERT INTO advertisers (network, network_advertiser_id, name, raw_hash) "
            "VALUES ('flexoffers', '12345', 'Existing', 'existing_hash')"
        )

        data = {
            "network": "flexoffers",
            "network_advertiser_id": "12345",
            "name": "Should Not Change",
            "raw_hash": "existing_hash",
        }

        advertiser_id, was_changed = db.upsert_advertiser(conn, data)

        assert was_changed is False
        # Name should remain unchanged
        row = conn.execute(
            "SELECT name FROM advertisers WHERE id = ?", (advertiser_id,)
        ).fetchone()
        assert row["name"] == "Existing"

    def test_updates_when_hash_changed(self, conn):
        """Should update when hash differs."""
        conn.execute(
            "INSERT INTO advertisers (network, network_advertiser_id, name, raw_hash) "
            "VALUES ('flexoffers', '12345', 'Old Name', 'old_hash')"
        )

        data = {
            "network": "flexoffers",
            "network_advertiser_id": "12345",
            "name": "Updated Name",
            "raw_hash": "new_hash",
        }

        advertiser_id, was_changed = db.upsert_advertiser(conn, data)

        assert was_changed is True
        row = conn.execute(
            "SELECT name FROM advertisers WHERE id = ?", (advertiser_id,)
        ).fetchone()
        assert row["name"] == "Updated Name"

    def test_reactivates_inactive_advertiser(self, conn):
        """Should set is_active=1 when upserting a previously deactivated advertiser."""
        conn.execute(
            "INSERT INTO advertisers (network, network_advertiser_id, name, raw_hash, is_active) "
            "VALUES ('flexoffers', '12345', 'Deactivated', 'old_hash', 0)"
        )

        data = {
            "network": "flexoffers",
            "network_advertiser_id": "12345",
            "name": "Reactivated",
            "raw_hash": "new_hash",
        }

        advertiser_id, _ = db.upsert_advertiser(conn, data)

        row = conn.execute(
            "SELECT is_active FROM advertisers WHERE id = ?", (advertiser_id,)
        ).fetchone()
        assert row["is_active"] == 1


class TestGetAdHash:
    """Test get_ad_hash function."""

    def test_returns_hash_when_ad_exists(self, conn):
        """Should return raw_hash for existing ad."""
        # Need an advertiser first
        conn.execute(
            "INSERT INTO advertisers (network, network_advertiser_id, name) "
            "VALUES ('flexoffers', '1', 'Test')"
        )
        adv_id = conn.execute("SELECT id FROM advertisers").fetchone()["id"]

        conn.execute(
            "INSERT INTO ads (network, network_ad_id, advertiser_id, tracking_url, "
            "advert_name, bannercode, width, height, raw_hash) "
            "VALUES ('flexoffers', 'link-123', ?, 'https://track.example.com', "
            "'Test Ad', '<a>test</a>', 300, 250, 'ad_hash_xyz')",
            (adv_id,),
        )

        result = db.get_ad_hash(conn, "flexoffers", "link-123")
        assert result == "ad_hash_xyz"

    def test_returns_none_when_ad_not_found(self, conn):
        """Should return None when ad doesn't exist."""
        result = db.get_ad_hash(conn, "flexoffers", "nonexistent")
        assert result is None


class TestUpsertAd:
    """Test upsert_ad function."""

    @pytest.fixture
    def advertiser_id(self, conn):
        """Create a test advertiser and return its ID."""
        conn.execute(
            "INSERT INTO advertisers (network, network_advertiser_id, name) "
            "VALUES ('flexoffers', '1', 'Test Advertiser')"
        )
        return conn.execute("SELECT id FROM advertisers").fetchone()["id"]

    def test_inserts_new_ad(self, conn, advertiser_id):
        """Should insert when ad doesn't exist."""
        data = {
            "network": "flexoffers",
            "network_ad_id": "new-link",
            "advertiser_id": advertiser_id,
            "tracking_url": "https://track.example.com",
            "advert_name": "New Ad",
            "bannercode": "<a href='#'><img src='new.jpg'/></a>",
            "image_url": "https://example.com/new.jpg",
            "width": 728,
            "height": 90,
            "raw_hash": "new_ad_hash",
        }

        ad_id, was_changed = db.upsert_ad(conn, data)

        assert isinstance(ad_id, int)
        assert was_changed is True

    def test_skips_update_when_hash_unchanged(self, conn, advertiser_id):
        """Should return existing ID without update when hash matches."""
        conn.execute(
            "INSERT INTO ads (network, network_ad_id, advertiser_id, tracking_url, "
            "advert_name, bannercode, width, height, raw_hash) "
            "VALUES ('flexoffers', 'link-123', ?, 'https://track.example.com', "
            "'Existing Ad', '<a>test</a>', 300, 250, 'existing_hash')",
            (advertiser_id,),
        )

        data = {
            "network": "flexoffers",
            "network_ad_id": "link-123",
            "advertiser_id": advertiser_id,
            "tracking_url": "https://track.example.com",
            "advert_name": "Should Not Change",
            "bannercode": "<a>test</a>",
            "image_url": "",
            "width": 300,
            "height": 250,
            "raw_hash": "existing_hash",
        }

        ad_id, was_changed = db.upsert_ad(conn, data)
        assert was_changed is False

    def test_updates_when_hash_changed(self, conn, advertiser_id):
        """Should update when hash differs."""
        conn.execute(
            "INSERT INTO ads (network, network_ad_id, advertiser_id, tracking_url, "
            "advert_name, bannercode, width, height, raw_hash) "
            "VALUES ('flexoffers', 'link-123', ?, 'https://track.example.com', "
            "'Old Ad', '<a>old</a>', 300, 250, 'old_hash')",
            (advertiser_id,),
        )

        data = {
            "network": "flexoffers",
            "network_ad_id": "link-123",
            "advertiser_id": advertiser_id,
            "tracking_url": "https://track.example.com/updated",
            "advert_name": "Updated Ad",
            "bannercode": "<a>updated</a>",
            "image_url": "",
            "width": 300,
            "height": 250,
            "raw_hash": "new_hash",
        }

        ad_id, was_changed = db.upsert_ad(conn, data)
        assert was_changed is True

        row = conn.execute(
            "SELECT advert_name FROM ads WHERE id = ?", (ad_id,)
        ).fetchone()
        assert row["advert_name"] == "Updated Ad"

    def test_no_weight_column(self, conn, advertiser_id):
        """Should not include weight column (v2 uses weight_override)."""
        data = {
            "network": "flexoffers",
            "network_ad_id": "link-no-weight",
            "advertiser_id": advertiser_id,
            "tracking_url": "https://track.example.com",
            "advert_name": "No Weight Ad",
            "bannercode": "<a>test</a>",
            "image_url": "",
            "width": 300,
            "height": 250,
            "raw_hash": "hash_no_weight",
        }

        ad_id, _ = db.upsert_ad(conn, data)

        row = conn.execute(
            "SELECT weight_override FROM ads WHERE id = ?", (ad_id,)
        ).fetchone()
        assert row["weight_override"] is None  # Not set by upsert


class TestSyncLog:
    """Test sync log functions."""

    def test_create_sync_log(self, conn):
        """Should create sync log entry and return ID."""
        log_id = db.create_sync_log(conn, "flexoffers")

        assert isinstance(log_id, int)

        row = conn.execute(
            "SELECT * FROM sync_logs WHERE id = ?", (log_id,)
        ).fetchone()
        assert row["network"] == "flexoffers"
        assert row["status"] == "running"
        assert row["site_domain"] is None

    def test_create_sync_log_with_domain(self, conn):
        """Should store site_domain when provided."""
        log_id = db.create_sync_log(conn, "flexoffers", site_domain="rvtravellife.com")

        row = conn.execute(
            "SELECT site_domain FROM sync_logs WHERE id = ?", (log_id,)
        ).fetchone()
        assert row["site_domain"] == "rvtravellife.com"

    def test_update_sync_log(self, conn):
        """Should update sync log with v2 stats."""
        log_id = db.create_sync_log(conn, "flexoffers")

        db.update_sync_log(
            conn,
            log_id=log_id,
            advertisers_synced=10,
            ads_synced=100,
            ads_deleted=5,
            status="success",
            error_message=None,
        )

        row = conn.execute(
            "SELECT * FROM sync_logs WHERE id = ?", (log_id,)
        ).fetchone()
        assert row["advertisers_synced"] == 10
        assert row["ads_synced"] == 100
        assert row["ads_deleted"] == 5
        assert row["status"] == "success"
        assert row["completed_at"] is not None

    def test_update_sync_log_failed(self, conn):
        """Should store failure status and error message."""
        log_id = db.create_sync_log(conn, "impact")

        db.update_sync_log(
            conn,
            log_id=log_id,
            status="failed",
            error_message="Connection timeout",
        )

        row = conn.execute(
            "SELECT status, error_message FROM sync_logs WHERE id = ?", (log_id,)
        ).fetchone()
        assert row["status"] == "failed"
        assert row["error_message"] == "Connection timeout"


class TestSiteLookup:
    """Test site lookup functions."""

    def test_get_active_sites(self, conn):
        """Should return all active sites from seed data."""
        sites = db.get_active_sites(conn)
        assert len(sites) == 5
        domains = {s["domain"] for s in sites}
        assert "rvtravellife.com" in domains

    def test_get_site_by_domain(self, conn):
        """Should find site by domain."""
        site = db.get_site_by_domain(conn, "rvtravellife.com")
        assert site is not None
        assert site["domain"] == "rvtravellife.com"

    def test_get_site_by_domain_not_found(self, conn):
        """Should return None for unknown domain."""
        site = db.get_site_by_domain(conn, "nonexistent.com")
        assert site is None


class TestSiteAdvertiserRules:
    """Test site_advertiser_rules functions."""

    @pytest.fixture
    def advertiser_id(self, conn):
        conn.execute(
            "INSERT INTO advertisers (network, network_advertiser_id, name) "
            "VALUES ('flexoffers', '1', 'Test')"
        )
        return conn.execute("SELECT id FROM advertisers").fetchone()["id"]

    def test_ensure_creates_default_rule(self, conn, advertiser_id):
        """Should create a rule with 'default' status."""
        site = db.get_site_by_domain(conn, "rvtravellife.com")
        db.ensure_site_advertiser_rule(conn, site["id"], advertiser_id)

        row = conn.execute(
            "SELECT rule FROM site_advertiser_rules WHERE site_id = ? AND advertiser_id = ?",
            (site["id"], advertiser_id),
        ).fetchone()
        assert row["rule"] == "default"

    def test_ensure_does_not_overwrite_existing(self, conn, advertiser_id):
        """Should not overwrite existing 'allowed' rule."""
        site = db.get_site_by_domain(conn, "rvtravellife.com")

        # Pre-create an 'allowed' rule
        conn.execute(
            "INSERT INTO site_advertiser_rules (site_id, advertiser_id, rule) "
            "VALUES (?, ?, 'allowed')",
            (site["id"], advertiser_id),
        )

        # ensure_site_advertiser_rule should NOT overwrite
        db.ensure_site_advertiser_rule(conn, site["id"], advertiser_id)

        row = conn.execute(
            "SELECT rule FROM site_advertiser_rules WHERE site_id = ? AND advertiser_id = ?",
            (site["id"], advertiser_id),
        ).fetchone()
        assert row["rule"] == "allowed"


class TestStaleCleanup:
    """Test stale data cleanup functions."""

    @pytest.fixture
    def setup_advertiser_with_ads(self, conn):
        """Create an advertiser with 3 ads."""
        conn.execute(
            "INSERT INTO advertisers (network, network_advertiser_id, name) "
            "VALUES ('flexoffers', '1', 'Test')"
        )
        adv_id = conn.execute("SELECT id FROM advertisers").fetchone()["id"]

        for i in range(1, 4):
            conn.execute(
                "INSERT INTO ads (network, network_ad_id, advertiser_id, tracking_url, "
                "advert_name, bannercode, width, height) "
                "VALUES ('flexoffers', ?, ?, 'https://track.example.com', "
                "'Ad', '<a>test</a>', 300, 250)",
                (f"ad-{i}", adv_id),
            )

        return adv_id

    def test_delete_stale_ads(self, conn, setup_advertiser_with_ads):
        """Should delete ads not in the seen set."""
        adv_id = setup_advertiser_with_ads

        # Only ad-1 and ad-2 were seen this sync
        deleted = db.delete_stale_ads(conn, "flexoffers", adv_id, {"ad-1", "ad-2"})

        assert deleted == 1  # ad-3 deleted
        remaining = conn.execute("SELECT COUNT(*) as cnt FROM ads").fetchone()["cnt"]
        assert remaining == 2

    def test_deactivate_stale_advertisers(self, conn):
        """Should deactivate advertisers not in the seen set."""
        for i in range(1, 4):
            conn.execute(
                "INSERT INTO advertisers (network, network_advertiser_id, name, is_active) "
                "VALUES ('flexoffers', ?, ?, 1)",
                (f"adv-{i}", f"Advertiser {i}"),
            )

        # Only adv-1 was seen
        deactivated = db.deactivate_stale_advertisers(conn, "flexoffers", {"adv-1"})

        assert deactivated == 2  # adv-2 and adv-3 deactivated
        active = conn.execute(
            "SELECT COUNT(*) as cnt FROM advertisers WHERE is_active = 1"
        ).fetchone()["cnt"]
        assert active == 1
