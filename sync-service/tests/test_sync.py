"""E2E sync tests - run full pipeline (fetch -> map -> db insert).

Run with -s flag to see verbose output:
    uv run pytest tests/test_sync.py -v -s
"""

import pytest


class TestFlexOffersSync:
    """E2E test for FlexOffers sync pipeline."""

    def test_sync(self, flexoffers_client, conn):
        """Run full sync for FlexOffers with verbose output."""
        print("\n" + "=" * 60)
        print("FLEXOFFERS E2E SYNC TEST")
        print("=" * 60)

        # Step 1: Fetch advertisers
        advertisers = flexoffers_client.fetch_advertisers()
        print(f"Fetched {len(advertisers)} advertisers")
        if not advertisers:
            pytest.skip("API returned empty list (may be temporarily unavailable)")

        # Step 2: Fetch ads for first advertiser
        if advertisers:
            first_adv_id = str(advertisers[0].get("id"))
            ads = flexoffers_client.fetch_ads(first_adv_id)
            print(f"Fetched {len(ads)} ads for first advertiser ({first_adv_id})")

        # Step 3: Run full sync (maps + inserts)
        stats = flexoffers_client.sync(conn)
        print(f"Sync stats: {stats}")

        # Step 4: Verify database
        adv_count = conn.execute(
            "SELECT COUNT(*) FROM advertisers WHERE network='flexoffers'"
        ).fetchone()[0]
        ad_count = conn.execute(
            "SELECT COUNT(*) FROM ads WHERE network='flexoffers'"
        ).fetchone()[0]
        print(f"Database: {adv_count} advertisers, {ad_count} ads")
        print("=" * 60)

        assert adv_count > 0
        assert stats["advertisers_synced"] > 0


class TestAwinSync:
    """E2E test for Awin sync pipeline."""

    def test_sync(self, awin_client, conn):
        """Run full sync for Awin with verbose output.

        NOTE: Awin mapper may be incomplete. This test may fail
        with KeyError if map_ad doesn't return all required fields.
        """
        print("\n" + "=" * 60)
        print("AWIN E2E SYNC TEST")
        print("=" * 60)

        # Step 1: Fetch advertisers
        advertisers = awin_client.fetch_advertisers()
        print(f"Fetched {len(advertisers)} advertisers")
        if not advertisers:
            pytest.skip("API returned empty list (may be temporarily unavailable)")

        # Step 2: Fetch ads for first advertiser
        if advertisers:
            first_adv_id = str(advertisers[0].get("id"))
            ads = awin_client.fetch_ads(first_adv_id)
            print(f"Fetched {len(ads)} ads for first advertiser ({first_adv_id})")

        # Step 3: Run full sync (maps + inserts)
        stats = awin_client.sync(conn)
        print(f"Sync stats: {stats}")

        # Step 4: Verify database
        adv_count = conn.execute(
            "SELECT COUNT(*) FROM advertisers WHERE network='awin'"
        ).fetchone()[0]
        ad_count = conn.execute(
            "SELECT COUNT(*) FROM ads WHERE network='awin'"
        ).fetchone()[0]
        print(f"Database: {adv_count} advertisers, {ad_count} ads")
        print("=" * 60)

        assert adv_count > 0
        assert stats["advertisers_synced"] > 0


class TestCJSync:
    """E2E test for CJ sync pipeline."""

    def test_sync(self, cj_client, conn):
        """Run full sync for CJ with verbose output."""
        print("\n" + "=" * 60)
        print("CJ E2E SYNC TEST")
        print("=" * 60)

        # Step 1: Fetch advertisers
        advertisers = cj_client.fetch_advertisers()
        print(f"Fetched {len(advertisers)} advertisers")
        if not advertisers:
            pytest.skip("API returned empty list (may be temporarily unavailable)")

        # Step 2: Fetch ads for first advertiser
        if advertisers:
            first_adv_id = str(advertisers[0].get("advertiser-id"))
            ads = cj_client.fetch_ads(first_adv_id)
            print(f"Fetched {len(ads)} ads for first advertiser ({first_adv_id})")

        # Step 3: Run full sync (maps + inserts)
        stats = cj_client.sync(conn)
        print(f"Sync stats: {stats}")

        # Step 4: Verify database
        adv_count = conn.execute(
            "SELECT COUNT(*) FROM advertisers WHERE network='cj'"
        ).fetchone()[0]
        ad_count = conn.execute(
            "SELECT COUNT(*) FROM ads WHERE network='cj'"
        ).fetchone()[0]
        print(f"Database: {adv_count} advertisers, {ad_count} ads")
        print("=" * 60)

        assert adv_count > 0
        assert stats["advertisers_synced"] > 0


class TestImpactSync:
    """E2E test for Impact sync pipeline."""

    def test_sync(self, impact_client, conn):
        """Run full sync for Impact with verbose output."""
        print("\n" + "=" * 60)
        print("IMPACT E2E SYNC TEST")
        print("=" * 60)

        # Step 1: Fetch advertisers (campaigns)
        advertisers = impact_client.fetch_advertisers()
        print(f"Fetched {len(advertisers)} campaigns")
        if not advertisers:
            pytest.skip("API returned empty list (may be temporarily unavailable)")

        # Step 2: Fetch ads (Impact uses global endpoint)
        ads = impact_client.fetch_ads("")
        print(f"Fetched {len(ads)} ads (global)")

        # Step 3: Run full sync (maps + inserts)
        stats = impact_client.sync(conn)
        print(f"Sync stats: {stats}")

        # Step 4: Verify database
        adv_count = conn.execute(
            "SELECT COUNT(*) FROM advertisers WHERE network='impact'"
        ).fetchone()[0]
        ad_count = conn.execute(
            "SELECT COUNT(*) FROM ads WHERE network='impact'"
        ).fetchone()[0]
        print(f"Database: {adv_count} advertisers, {ad_count} ads")
        print("=" * 60)

        assert adv_count > 0
        assert stats["advertisers_synced"] > 0
