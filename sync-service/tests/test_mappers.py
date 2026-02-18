"""Tests for network response mappers."""

import pytest

from src.mappers import get_mapper
from src.mappers.base import Mapper


class TestMapperBase:
    """Test the base mapper functionality."""

    def test_compute_hash_consistent(self):
        """Hash should be consistent for same input."""
        raw = {"id": "123", "name": "Test"}
        hash1 = Mapper.compute_hash(raw)
        hash2 = Mapper.compute_hash(raw)
        assert hash1 == hash2

    def test_compute_hash_different_for_different_input(self):
        """Hash should differ for different input."""
        raw1 = {"id": "123", "name": "Test"}
        raw2 = {"id": "456", "name": "Test"}
        assert Mapper.compute_hash(raw1) != Mapper.compute_hash(raw2)


class TestGetMapper:
    """Test mapper registry."""

    @pytest.mark.parametrize("network", ["flexoffers", "awin", "cj", "impact"])
    def test_get_mapper_returns_correct_type(self, network: str):
        """Should return mapper for valid network."""
        mapper = get_mapper(network)
        assert mapper.network_name == network

    def test_get_mapper_unknown_network_raises(self):
        """Should raise for unknown network."""
        with pytest.raises(ValueError, match="Unknown network"):
            get_mapper("unknown")


class TestFlexOffersMapper:
    """Test FlexOffers mapper."""

    def test_map_advertiser(self):
        """Should map advertiser response correctly."""
        mapper = get_mapper("flexoffers")
        raw = {
            "id": 12345,
            "name": "Test Advertiser",
            "programStatus": "Approved",
            "applicationStatus": "Approved",
            "domainUrl": "https://example.com",
            "categoryNames": "Test Category",
            "sevenDayEpc": "0.25",
        }
        result = mapper.map_advertiser(raw)

        assert result["network"] == "flexoffers"
        assert result["network_program_id"] == "12345"
        assert result["network_program_name"] == "Test Advertiser"
        assert result["status"] == "active"
        assert result["website_url"] == "https://example.com"
        assert result["category"] == "Test Category"
        assert result["epc"] == 0.25
        assert "raw_hash" in result

    def test_map_advertiser_paused_status(self):
        """Should set status to paused when not fully approved."""
        mapper = get_mapper("flexoffers")
        raw = {
            "id": 12345,
            "name": "Pending Advertiser",
            "programStatus": "Approved",
            "applicationStatus": "Pending",
        }
        result = mapper.map_advertiser(raw)
        assert result["status"] == "paused"

    def test_map_ad_banner(self):
        """Should map banner ad response correctly."""
        mapper = get_mapper("flexoffers")
        raw = {
            "linkId": 12345,
            "linkName": "Summer Sale Banner",
            "linkType": "Banner",
            "imageUrl": "https://example.com/banner.jpg",
            "linkUrl": "https://track.example.com/click",
            "bannerWidth": 300,
            "bannerHeight": 250,
        }
        result = mapper.map_ad(raw, advertiser_id=1)

        assert result["network"] == "flexoffers"
        assert result["network_link_id"] == "12345"
        assert result["network_program_id"] == "1"
        assert result["width"] == 300
        assert result["height"] == 250
        assert result["creative_type"] == "banner"
        assert result["image_url"] == "https://example.com/banner.jpg"
        assert "weight" not in result
        assert "raw_data" in result

    def test_map_ad_text_link(self):
        """Should map text link response correctly with 0x0 dimensions."""
        mapper = get_mapper("flexoffers")
        raw = {
            "linkId": 67890,
            "linkName": "Shop Now Text Link",
            "linkType": "Text Link",
            "linkUrl": "https://track.example.com/click",
            "bannerWidth": None,
            "bannerHeight": None,
        }
        result = mapper.map_ad(raw, advertiser_id=2)

        assert result["network"] == "flexoffers"
        assert result["network_link_id"] == "67890"
        assert result["network_program_id"] == "2"
        assert result["width"] == 0
        assert result["height"] == 0
        assert result["creative_type"] == "text"
        assert result["image_url"] == ""


class TestAwinMapper:
    """Test Awin mapper."""

    def test_map_advertiser(self):
        """Should map advertiser response correctly."""
        mapper = get_mapper("awin")
        raw = {"id": "67890", "name": "Awin Merchant", "status": "active"}
        result = mapper.map_advertiser(raw)

        assert result["network"] == "awin"
        assert result["network_program_id"] == "67890"


class TestCJMapper:
    """Test CJ mapper."""

    def test_map_advertiser(self):
        """Should map advertiser response correctly."""
        mapper = get_mapper("cj")
        raw = {
            "advertiser-id": "cj-123",
            "advertiser-name": "CJ Merchant",
            "account-status": "Active",
            "program-url": "https://example.com",
            "primary-category/child": "Retail",
            "seven-day-epc": "1.50",
        }
        result = mapper.map_advertiser(raw)

        assert result["network"] == "cj"
        assert result["network_program_id"] == "cj-123"
        assert result["network_program_name"] == "CJ Merchant"
        assert result["status"] == "active"
        assert result["website_url"] == "https://example.com"
        assert result["category"] == "Retail"
        assert result["epc"] == 1.50


class TestImpactMapper:
    """Test Impact mapper."""

    def test_map_advertiser(self):
        """Should map campaign response to canonical advertiser schema."""
        mapper = get_mapper("impact")
        raw = {
            "CampaignId": "2181",
            "CampaignName": "Wine Express",
            "CampaignUrl": "http://www.wineexpress.com/",
            "ContractStatus": "Active",
            "AdvertiserName": "Wine Express",
            "AdvertiserUrl": "https://www.wineexpress.com",
        }
        result = mapper.map_advertiser(raw)

        assert result["network"] == "impact"
        assert result["network_program_id"] == "2181"
        assert result["network_program_name"] == "Wine Express"
        assert result["status"] == "active"
        assert result["website_url"] == "http://www.wineexpress.com/"
        assert result["epc"] == 0
        assert "raw_hash" in result
        assert len(result["raw_hash"]) == 64  # SHA-256 hex

    def test_map_advertiser_paused_status(self):
        """Should set status to paused when ContractStatus is not Active."""
        mapper = get_mapper("impact")
        raw = {
            "CampaignId": "11630",
            "CampaignName": "SunnySports",
            "ContractStatus": "Expired",
        }
        result = mapper.map_advertiser(raw)
        assert result["status"] == "paused"

    def test_map_advertiser_missing_fields(self):
        """Should handle missing optional fields gracefully."""
        mapper = get_mapper("impact")
        raw = {"CampaignId": "999", "ContractStatus": "Active"}
        result = mapper.map_advertiser(raw)

        assert result["network_program_id"] == "999"
        assert result["network_program_name"] == ""
        assert result["website_url"] == ""
        assert result["category"] == ""

    def test_map_ad_banner(self):
        """Should map BANNER ad response with correct dimensions."""
        mapper = get_mapper("impact")
        raw = {
            "Id": "1502989",
            "Name": "Learning Rewards - 728x90",
            "Type": "BANNER",
            "TrackingLink": "https://coinbase-consumer.sjv.io/c/4545284/1502989/9251",
            "LandingPageUrl": "https://www.coinbase.com/learning-rewards",
            "Code": '<a rel="sponsored" href="https://example.com"><img src="//a.impactradius-go.com/display-ad/9251-1502989" width="728" height="90"/></a>',
            "CreativeUrl": "//a.impactradius-go.com/display-ad/9251-1502989",
            "Width": "728",
            "Height": "90",
            "CampaignId": "9251",
        }
        result = mapper.map_ad(raw, advertiser_id=1)

        assert result["network"] == "impact"
        assert result["network_link_id"] == "1502989"
        assert result["creative_type"] == "banner"
        assert result["width"] == 728
        assert result["height"] == 90
        assert isinstance(result["width"], int)
        assert isinstance(result["height"], int)
        assert result["tracking_url"] == "https://coinbase-consumer.sjv.io/c/4545284/1502989/9251"
        assert result["destination_url"] == "https://www.coinbase.com/learning-rewards"
        assert result["image_url"] == "//a.impactradius-go.com/display-ad/9251-1502989"
        assert result["status"] == "active"
        assert "raw_hash" in result

    def test_map_ad_text_link(self):
        """Should map TEXT_LINK ad with 0x0 dimensions."""
        mapper = get_mapper("impact")
        raw = {
            "Id": "1477107",
            "Name": "RC Products",
            "Type": "TEXT_LINK",
            "TrackingLink": "https://harfington.pxf.io/c/4545284/1477107/15745",
            "LandingPageUrl": "https://www.harfington.com/collections/model-aircraft",
            "Code": '<h3 id="1477107"><a rel="sponsored" href="https://example.com">10% OFF</a></h3>',
            "CreativeUrl": "",
            "Width": "",
            "Height": "",
            "CampaignId": "15745",
        }
        result = mapper.map_ad(raw, advertiser_id=2)

        assert result["creative_type"] == "text"
        assert result["width"] == 0
        assert result["height"] == 0
        assert result["image_url"] == ""
        assert result["network_link_id"] == "1477107"

    def test_map_ad_advert_name_format(self):
        """Should construct advert_name in correct format."""
        mapper = get_mapper("impact")
        raw = {
            "Id": "401987",
            "Name": "Backcountry Logo 88x31",
            "Type": "BANNER",
            "TrackingLink": "https://example.com",
            "Code": "<a>test</a>",
            "CreativeUrl": "//example.com/img.jpg",
            "Width": "88",
            "Height": "31",
        }
        result = mapper.map_ad(raw, advertiser_id=5)

        # Format: {width}X{height}-{advertiser_id}-{sanitized_name}-{ad_id}-General
        assert result["advert_name"] == "88X31-5-BackcountryLogo88x31-401987-General"

    def test_map_ad_bannercode_uses_code_field(self):
        """Should use the Code field from API as bannercode."""
        mapper = get_mapper("impact")
        html = '<a href="https://track.example.com"><img src="//img.example.com/ad.jpg" /></a>'
        raw = {
            "Id": "123",
            "Name": "Test Ad",
            "Type": "BANNER",
            "TrackingLink": "https://track.example.com",
            "Code": html,
            "CreativeUrl": "//img.example.com/ad.jpg",
            "Width": "300",
            "Height": "250",
        }
        result = mapper.map_ad(raw, advertiser_id=1)

        assert result["bannercode"] == html

    def test_map_ad_bannercode_fallback(self):
        """Should construct bannercode when Code field is empty."""
        mapper = get_mapper("impact")
        raw = {
            "Id": "456",
            "Name": "No Code Ad",
            "Type": "BANNER",
            "TrackingLink": "https://track.example.com",
            "Code": "",
            "CreativeUrl": "//img.example.com/ad.jpg",
            "Width": "300",
            "Height": "250",
        }
        result = mapper.map_ad(raw, advertiser_id=1)

        assert 'href="https://track.example.com"' in result["bannercode"]
        assert 'src="//img.example.com/ad.jpg"' in result["bannercode"]
        assert 'rel="sponsored"' in result["bannercode"]

    def test_map_ad_adrotate_defaults(self):
        """Should include all required AdRotate default fields."""
        mapper = get_mapper("impact")
        raw = {
            "Id": "789",
            "Name": "Test",
            "Type": "BANNER",
            "TrackingLink": "https://example.com",
            "Code": "<a>test</a>",
            "CreativeUrl": "",
            "Width": "300",
            "Height": "250",
        }
        result = mapper.map_ad(raw, advertiser_id=1)

        assert result["campaign_name"] == "General Promotion"
        assert result["enable_stats"] == "Y"
        assert result["show_everyone"] == "Y"
        assert result["show_desktop"] == "Y"
        assert result["show_mobile"] == "Y"
        assert result["show_tablet"] == "Y"
        assert "weight" not in result
        assert result["autodelete"] == "Y"
        assert result["budget"] == 0
        assert result["geo_cities"] == "a:0:{}"
        assert result["schedule_start"] == 0
        assert result["schedule_end"] == 2650941780

    def test_map_ad_hash_changes_with_data(self):
        """Should produce different hashes for different ad data."""
        mapper = get_mapper("impact")
        base = {
            "Id": "100",
            "Name": "Ad v1",
            "Type": "BANNER",
            "TrackingLink": "https://example.com",
            "Code": "<a>test</a>",
            "CreativeUrl": "",
            "Width": "300",
            "Height": "250",
        }
        result1 = mapper.map_ad(base, advertiser_id=1)

        modified = dict(base, Name="Ad v2")
        result2 = mapper.map_ad(modified, advertiser_id=1)

        assert result1["raw_hash"] != result2["raw_hash"]
