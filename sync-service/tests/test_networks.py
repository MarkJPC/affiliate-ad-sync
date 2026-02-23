"""Integration tests for network API clients.

These tests hit real APIs and require valid credentials.
Tests skip gracefully if credentials are not configured or API is unavailable.
"""

import pytest


class TestFlexOffersClient:
    """Integration tests for FlexOffersClient (requires API key)."""

    def test_fetch_advertisers(self, flexoffers_client):
        """Fetch advertisers returns a non-empty list."""
        advertisers = flexoffers_client.fetch_advertisers()
        assert isinstance(advertisers, list)
        if not advertisers:
            pytest.skip("API returned empty list (may be temporarily unavailable)")
        assert len(advertisers) > 0

    def test_fetch_ads(self, flexoffers_client):
        """Fetch ads for first advertiser returns a list."""
        advertisers = flexoffers_client.fetch_advertisers()
        if not advertisers:
            pytest.skip("No advertisers available (API may be temporarily unavailable)")

        advertiser_id = str(advertisers[0].get("id"))
        ads = flexoffers_client.fetch_ads(advertiser_id)
        assert isinstance(ads, list)


class TestAwinClient:
    """Integration tests for AwinClient (requires API token + publisher ID)."""

    def test_fetch_advertisers(self, awin_client):
        """Fetch advertisers returns a list."""
        advertisers = awin_client.fetch_advertisers()
        assert isinstance(advertisers, list)
        if not advertisers:
            pytest.skip("API returned empty list (may be temporarily unavailable)")
        assert len(advertisers) > 0

    def test_fetch_ads(self, awin_client):
        """Fetch ads for first advertiser returns a list."""
        advertisers = awin_client.fetch_advertisers()
        if not advertisers:
            pytest.skip("No advertisers available (API may be temporarily unavailable)")

        advertiser_id = str(advertisers[0].get("id"))
        ads = awin_client.fetch_ads(advertiser_id)
        assert isinstance(ads, list)


class TestCJClient:
    """Integration tests for CJClient (requires API token + CID + website ID)."""

    def test_fetch_advertisers(self, cj_client):
        """Fetch advertisers returns a list."""
        advertisers = cj_client.fetch_advertisers()
        assert isinstance(advertisers, list)
        if not advertisers:
            pytest.skip("API returned empty list (may be temporarily unavailable)")
        assert len(advertisers) > 0

    def test_fetch_ads(self, cj_client):
        """Fetch ads for first advertiser returns a list."""
        advertisers = cj_client.fetch_advertisers()
        if not advertisers:
            pytest.skip("No advertisers available (API may be temporarily unavailable)")

        advertiser_id = str(advertisers[0].get("advertiser-id"))
        ads = cj_client.fetch_ads(advertiser_id)
        assert isinstance(ads, list)


class TestImpactClient:
    """Integration tests for ImpactClient (requires account SID + auth token)."""

    def test_fetch_advertisers(self, impact_client):
        """Fetch advertisers (campaigns) returns a list."""
        advertisers = impact_client.fetch_advertisers()
        assert isinstance(advertisers, list)
        if not advertisers:
            pytest.skip("API returned empty list (may be temporarily unavailable)")
        assert len(advertisers) > 0

    def test_fetch_ads(self, impact_client):
        """Fetch ads returns a list (Impact uses global ads endpoint)."""
        # Impact's fetch_ads ignores advertiser_id and fetches all ads
        ads = impact_client.fetch_ads("")
        assert isinstance(ads, list)
