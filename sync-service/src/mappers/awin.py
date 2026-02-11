"""Awin API response mapper - Nadia's responsibility."""

from .base import Mapper


class AwinMapper(Mapper):
    """Map Awin API responses to canonical schema."""

    @property
    def network_name(self) -> str:
        return "awin"

    def map_advertiser(self, raw: dict) -> dict:
        """
        Map Awin programme object to canonical advertiser schema.

        Expected raw fields from:
        GET /publishers/{publisherId}/programmes
        """

        return {
            "network": self.network_name,
            "network_program_id": str(raw.get("id")),
            "network_program_name": raw.get("name") or "",
            "status": (raw.get("status") or raw.get("linkStatus") or "unknown").lower(),
        }

    def map_ad(self, raw: dict, advertiser_id: int) -> dict:
        """
        Map Awin creative/offer object to canonical ads schema.

        NOTE:
        We are temporarily mapping offers structure.
        Once we confirm banner creative endpoint, we may update this.
        """

        return {
            "network": self.network_name,
            "network_ad_id": str(raw.get("promotionId") or raw.get("id")),
            "advertiser_id": advertiser_id,
            "creative_type": raw.get("type", "unknown"),
            "tracking_url": raw.get("urlTracking") or "",
            "destination_url": raw.get("url") or "",
            "html_snippet": None,  # Awin offers usually don't return full HTML
            "status": "active",
            "clicks": 0,
            "revenue": 0.0,
            "epc": 0.0,
        }
