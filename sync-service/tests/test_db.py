"""Tests for database upsert functions with hash-based change detection."""

from unittest.mock import MagicMock, patch

import pytest

from src import db


class TestGetAdvertiserHash:
    """Test get_advertiser_hash function."""

    def test_returns_hash_when_advertiser_exists(self):
        """Should return raw_hash for existing advertiser."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)
        mock_cursor.fetchone.return_value = {"raw_hash": "abc123hash"}

        result = db.get_advertiser_hash(mock_conn, "flexoffers", "12345")

        assert result == "abc123hash"
        mock_cursor.execute.assert_called_once()

    def test_returns_none_when_advertiser_not_found(self):
        """Should return None when advertiser doesn't exist."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)
        mock_cursor.fetchone.return_value = None

        result = db.get_advertiser_hash(mock_conn, "flexoffers", "99999")

        assert result is None


class TestUpsertAdvertiser:
    """Test upsert_advertiser function."""

    def test_skips_update_when_hash_unchanged(self):
        """Should return existing ID without update when hash matches."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)

        # First call: get_advertiser_hash returns matching hash
        # Second call: SELECT id returns the existing ID
        mock_cursor.fetchone.side_effect = [
            {"raw_hash": "existing_hash"},  # get_advertiser_hash
            {"id": "uuid-123"},  # SELECT id
        ]

        data = {
            "network": "flexoffers",
            "network_advertiser_id": "12345",
            "name": "Test Advertiser",
            "raw_hash": "existing_hash",
        }

        advertiser_id, was_changed = db.upsert_advertiser(mock_conn, data)

        assert advertiser_id == "uuid-123"
        assert was_changed is False
        # Should only have 2 SELECT queries, no INSERT
        assert mock_cursor.execute.call_count == 2

    def test_inserts_new_advertiser(self):
        """Should insert when advertiser doesn't exist."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)

        # First call: get_advertiser_hash returns None (not found)
        # Second call: INSERT succeeds
        # Third call: SELECT id returns new ID
        mock_cursor.fetchone.side_effect = [
            None,  # get_advertiser_hash - not found
            {"id": "new-uuid-456"},  # SELECT id after insert
        ]

        data = {
            "network": "flexoffers",
            "network_advertiser_id": "99999",
            "name": "New Advertiser",
            "website_url": "https://example.com",
            "category": "Test",
            "epc": 0.5,
            "raw_hash": "new_hash",
        }

        advertiser_id, was_changed = db.upsert_advertiser(mock_conn, data)

        assert advertiser_id == "new-uuid-456"
        assert was_changed is True
        # Should have: SELECT hash, INSERT, SELECT id
        assert mock_cursor.execute.call_count == 3

    def test_updates_when_hash_changed(self):
        """Should update when hash differs."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)

        # Hash differs, so we should INSERT ON DUPLICATE KEY UPDATE
        mock_cursor.fetchone.side_effect = [
            {"raw_hash": "old_hash"},  # get_advertiser_hash - different hash
            {"id": "uuid-789"},  # SELECT id after update
        ]

        data = {
            "network": "flexoffers",
            "network_advertiser_id": "12345",
            "name": "Updated Advertiser",
            "raw_hash": "new_hash",
        }

        advertiser_id, was_changed = db.upsert_advertiser(mock_conn, data)

        assert advertiser_id == "uuid-789"
        assert was_changed is True
        # Should have: SELECT hash, INSERT/UPDATE, SELECT id
        assert mock_cursor.execute.call_count == 3


class TestGetAdHash:
    """Test get_ad_hash function."""

    def test_returns_hash_when_ad_exists(self):
        """Should return raw_hash for existing ad."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)
        mock_cursor.fetchone.return_value = {"raw_hash": "ad_hash_xyz"}

        result = db.get_ad_hash(mock_conn, "flexoffers", "link-123")

        assert result == "ad_hash_xyz"

    def test_returns_none_when_ad_not_found(self):
        """Should return None when ad doesn't exist."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)
        mock_cursor.fetchone.return_value = None

        result = db.get_ad_hash(mock_conn, "flexoffers", "nonexistent")

        assert result is None


class TestUpsertAd:
    """Test upsert_ad function."""

    def test_skips_update_when_hash_unchanged(self):
        """Should return existing ID without update when hash matches."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)

        mock_cursor.fetchone.side_effect = [
            {"raw_hash": "existing_ad_hash"},  # get_ad_hash
            {"id": "ad-uuid-123"},  # SELECT id
        ]

        data = {
            "network": "flexoffers",
            "network_ad_id": "link-123",
            "advertiser_id": "adv-uuid",
            "tracking_url": "https://track.example.com",
            "advert_name": "Test Ad",
            "bannercode": "<a href='#'><img src='test.jpg'/></a>",
            "image_url": "https://example.com/test.jpg",
            "width": 300,
            "height": 250,
            "raw_hash": "existing_ad_hash",
        }

        ad_id, was_changed = db.upsert_ad(mock_conn, data)

        assert ad_id == "ad-uuid-123"
        assert was_changed is False

    def test_inserts_new_ad(self):
        """Should insert when ad doesn't exist."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)

        mock_cursor.fetchone.side_effect = [
            None,  # get_ad_hash - not found
            {"id": "new-ad-uuid"},  # SELECT id after insert
        ]

        data = {
            "network": "flexoffers",
            "network_ad_id": "new-link",
            "advertiser_id": "adv-uuid",
            "tracking_url": "https://track.example.com",
            "advert_name": "New Ad",
            "bannercode": "<a href='#'><img src='new.jpg'/></a>",
            "image_url": "https://example.com/new.jpg",
            "width": 728,
            "height": 90,
            "raw_hash": "new_ad_hash",
        }

        ad_id, was_changed = db.upsert_ad(mock_conn, data)

        assert ad_id == "new-ad-uuid"
        assert was_changed is True

    def test_updates_when_hash_changed(self):
        """Should update when hash differs."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)

        mock_cursor.fetchone.side_effect = [
            {"raw_hash": "old_ad_hash"},  # get_ad_hash - different hash
            {"id": "ad-uuid-updated"},  # SELECT id after update
        ]

        data = {
            "network": "flexoffers",
            "network_ad_id": "link-123",
            "advertiser_id": "adv-uuid",
            "tracking_url": "https://track.example.com/updated",
            "advert_name": "Updated Ad",
            "bannercode": "<a href='#'><img src='updated.jpg'/></a>",
            "image_url": "https://example.com/updated.jpg",
            "width": 300,
            "height": 250,
            "raw_hash": "new_ad_hash",
        }

        ad_id, was_changed = db.upsert_ad(mock_conn, data)

        assert ad_id == "ad-uuid-updated"
        assert was_changed is True


class TestSyncLog:
    """Test sync log functions."""

    def test_create_sync_log(self):
        """Should create sync log entry and return ID."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)
        mock_cursor.fetchone.return_value = {"id": 42}

        log_id = db.create_sync_log(mock_conn, "flexoffers")

        assert log_id == 42
        assert mock_cursor.execute.call_count == 2  # INSERT + SELECT LAST_INSERT_ID

    def test_update_sync_log(self):
        """Should update sync log with stats."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value.__enter__ = MagicMock(return_value=mock_cursor)
        mock_conn.cursor.return_value.__exit__ = MagicMock(return_value=False)

        db.update_sync_log(
            mock_conn,
            log_id=42,
            advertisers_synced=10,
            ads_synced=100,
            ads_created=50,
            ads_updated=30,
            errors=2,
            error_message="Some warnings",
        )

        mock_cursor.execute.assert_called_once()
        call_args = mock_cursor.execute.call_args
        assert 42 in call_args[0][1]  # log_id in params
        assert 10 in call_args[0][1]  # advertisers_synced in params
