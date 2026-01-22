"""Tests for network response mappers."""

import pytest

from mappers import get_mapper
from mappers.base import Mapper


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
        raw = {"id": "12345", "name": "Test Advertiser", "status": "active"}
        result = mapper.map_advertiser(raw)

        assert result["network"] == "flexoffers"
        assert result["network_program_id"] == "12345"
        assert result["network_program_name"] == "Test Advertiser"
        assert result["status"] == "active"

    def test_map_ad(self):
        """Should map ad response correctly."""
        mapper = get_mapper("flexoffers")
        raw = {
            "id": "ad-001",
            "name": "Summer Sale Banner",
            "imageUrl": "https://example.com/banner.jpg",
            "trackingUrl": "https://track.example.com/click",
            "width": 300,
            "height": 250,
        }
        result = mapper.map_ad(raw, advertiser_id=1)

        assert result["network"] == "flexoffers"
        assert result["network_link_id"] == "ad-001"
        assert result["advertiser_id"] == 1
        assert result["width"] == 300
        assert result["height"] == 250
        assert "raw_hash" in result


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
