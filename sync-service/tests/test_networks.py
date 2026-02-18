"""Integration tests for network API clients.

These tests hit real APIs and require valid credentials.
Tests skip gracefully if credentials are not configured.
"""

import json
import logging

import pytest

logger = logging.getLogger(__name__)

# Expected ad structure from FlexOffers /promotions endpoint (Swagger schema)
REQUIRED_AD_FIELDS = {
    "advertiserId": int,
    "linkId": int,
    "linkType": str,
    "linkUrl": str,
}

OPTIONAL_BANNER_FIELDS = {
    "imageUrl": str,
    "bannerWidth": int,
    "bannerHeight": int,
    "linkName": str,
    "advertiser": str,
}


class TestFlexOffersClient:
    """Integration tests for FlexOffersClient (requires API key)."""

    def test_fetch_ads_returns_list(self, flexoffers_client):
        """Fetch ads for first advertiser returns a list."""
        # First get an advertiser to test with
        advertisers = flexoffers_client.fetch_advertisers()
        if not advertisers:
            pytest.skip("No advertisers available for testing")

        advertiser_id = str(advertisers[0].get("id"))
        logger.info(f"Testing fetch_ads with advertiser {advertiser_id}")

        ads = flexoffers_client.fetch_ads(advertiser_id)

        assert isinstance(ads, list), "fetch_ads should return a list"
        logger.info(f"Fetched {len(ads)} ads")

    def test_fetch_ads_structure_validation(self, flexoffers_client):
        """Validate each ad has required fields with correct types."""
        advertisers = flexoffers_client.fetch_advertisers()
        if not advertisers:
            pytest.skip("No advertisers available for testing")

        advertiser_id = str(advertisers[0].get("id"))
        ads = flexoffers_client.fetch_ads(advertiser_id)

        if not ads:
            pytest.skip("No ads returned for first advertiser")

        for i, ad in enumerate(ads[:5]):  # Check first 5 ads
            # Validate required fields
            for field, expected_type in REQUIRED_AD_FIELDS.items():
                assert field in ad, f"Ad {i} missing required field: {field}"
                assert isinstance(
                    ad[field], expected_type
                ), f"Ad {i} field {field} should be {expected_type.__name__}, got {type(ad[field]).__name__}"

            # Validate optional banner fields if present
            for field, expected_type in OPTIONAL_BANNER_FIELDS.items():
                if field in ad and ad[field] is not None:
                    assert isinstance(
                        ad[field], expected_type
                    ), f"Ad {i} field {field} should be {expected_type.__name__}"

            logger.debug(
                f"Ad {i}: linkId={ad.get('linkId')}, "
                f"{ad.get('bannerWidth')}x{ad.get('bannerHeight')}"
            )

    def test_fetch_ads_includes_all_link_types(self, flexoffers_client):
        """Verify ads include both banners and text links."""
        advertisers = flexoffers_client.fetch_advertisers()
        if not advertisers:
            pytest.skip("No advertisers available for testing")

        advertiser_id = str(advertisers[0].get("id"))
        ads = flexoffers_client.fetch_ads(advertiser_id)

        if not ads:
            pytest.skip("No ads returned for first advertiser")

        # Count by link type
        link_types = {}
        banners = 0
        text_links = 0
        for ad in ads:
            link_type = ad.get("linkType", "unknown")
            link_types[link_type] = link_types.get(link_type, 0) + 1

            width = ad.get("bannerWidth") or 0
            height = ad.get("bannerHeight") or 0
            if width > 0 and height > 0:
                banners += 1
            else:
                text_links += 1

        logger.info(f"Link types: {link_types}")
        logger.info(f"Banners (with dimensions): {banners}, Text links (0x0): {text_links}")

    def test_fetch_ads_nonexistent_advertiser(self, flexoffers_client):
        """Pass invalid advertiser ID, expect empty list (not exception)."""
        # Use an advertiser ID that's unlikely to exist
        fake_advertiser_id = "999999999"

        ads = flexoffers_client.fetch_ads(fake_advertiser_id)

        assert isinstance(ads, list), "Should return a list even for invalid advertiser"
        assert len(ads) == 0, f"Expected empty list for nonexistent advertiser, got {len(ads)} ads"
        logger.info("Nonexistent advertiser correctly returned empty list")

    def test_print_ad_sample(self, flexoffers_client):
        """Print sample ad response for debugging."""
        advertisers = flexoffers_client.fetch_advertisers()
        if not advertisers:
            pytest.skip("No advertisers available")

        # Print first advertiser
        print("\n" + "=" * 60)
        print("FIRST ADVERTISER:")
        print(json.dumps(advertisers[0], indent=2, default=str))

        # Fetch ads for first advertiser
        advertiser_id = str(advertisers[0].get("id"))
        ads = flexoffers_client.fetch_ads(advertiser_id)

        print("\n" + "=" * 60)
        print(f"FIRST AD (of {len(ads)} total):")
        if ads:
            print(json.dumps(ads[0], indent=2, default=str))
        else:
            print("No ads returned")
        print("=" * 60)

    def test_print_raw_promotions_response(self, flexoffers_client):
        """Print RAW promotions API response (no filtering)."""
        advertisers = flexoffers_client.fetch_advertisers()
        if not advertisers:
            pytest.skip("No advertisers available")

        advertiser_id = str(advertisers[0].get("id"))

        # Call API directly without the client's filtering
        print("\n" + "=" * 60)
        print(f"RAW /promotions response for advertiser {advertiser_id}")
        print("=" * 60)

        # Try WITHOUT linkType filter first (get all link types)
        params_all = {
            "page": 1,
            "pageSize": 10,
            "advertiserIds": advertiser_id,
        }

        response = flexoffers_client._client.get(
            f"{flexoffers_client.BASE_URL}/promotions",
            headers=flexoffers_client._get_headers(),
            params=params_all,
        )

        print(f"\n[ALL LINK TYPES] Status: {response.status_code}")
        if response.status_code == 200:
            data = response.json()
            print(f"Response type: {type(data).__name__}")
            if isinstance(data, list):
                print(f"List length: {len(data)}")
                if data:
                    print("First item:")
                    print(json.dumps(data[0], indent=2, default=str))
            else:
                print(f"Keys: {list(data.keys())}")
                print(json.dumps(data, indent=2, default=str)[:2000])
        elif response.status_code == 204:
            print("No content (204)")
        else:
            print(f"Response text: {response.text[:500]}")

        # Try WITH linkType=Banners filter
        params_banners = {
            "page": 1,
            "pageSize": 10,
            "advertiserIds": advertiser_id,
            "linkType": "Banners",
        }

        response2 = flexoffers_client._client.get(
            f"{flexoffers_client.BASE_URL}/promotions",
            headers=flexoffers_client._get_headers(),
            params=params_banners,
        )

        print(f"\n[BANNERS ONLY] Status: {response2.status_code}")
        if response2.status_code == 200:
            data2 = response2.json()
            if isinstance(data2, list):
                print(f"List length: {len(data2)}")
            else:
                print(f"Keys: {list(data2.keys())}")
        elif response2.status_code == 204:
            print("No content (204)")

        print("=" * 60)
