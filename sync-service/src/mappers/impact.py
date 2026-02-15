"""Impact API response mapper - Rag's responsibility."""

from .base import Mapper


class ImpactMapper(Mapper):
    """Map Impact API responses to canonical schema."""

    @property
    def network_name(self) -> str:
        return "impact"

    def map_advertiser(self, raw: dict) -> dict:
        """Map Impact campaign response to canonical advertiser schema.

        Args:
            raw: Raw API response for a campaign.

        Returns:
            Dict with canonical advertiser fields.
        """
        # Status is active only if ContractStatus is "Active"
        contract_status = raw.get("ContractStatus", "")
        is_active = contract_status == "Active"

        return {
            "network": "impact",
            "network_program_id": str(raw.get("CampaignId", "")),
            "network_program_name": raw.get("CampaignName", ""),
            "status": "active" if is_active else "paused",
            "website_url": raw.get("CampaignUrl", ""),
            "category": "",  # Impact doesn't provide category in campaign response
            "epc": 0,
            "raw_hash": Mapper.compute_hash(raw),
        }

    def map_ad(self, raw: dict, advertiser_id: int) -> dict:
        # TODO: Implement after API access and schema finalized
        raise NotImplementedError
