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
            "advertiserId": "cj-123",
            "advertiserName": "CJ Merchant",
            "relationshipStatus": "joined",
        }
        result = mapper.map_advertiser(raw)

        assert result["network"] == "cj"
        assert result["network_program_id"] == "cj-123"
        assert result["status"] == "active"


class TestImpactMapper:
    """Test Impact mapper."""

    def test_map_advertiser(self):
        """Should map advertiser response correctly."""
        mapper = get_mapper("impact")
        raw = {
            "CampaignId": "imp-456",
            "CampaignName": "Impact Brand",
            "ContractStatus": "Active",
        }
        result = mapper.map_advertiser(raw)

        assert result["network"] == "impact"
        assert result["network_program_id"] == "imp-456"
        assert result["status"] == "active"
