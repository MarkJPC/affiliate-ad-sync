"""Impact API response mapper - Rag's responsibility."""

from .base import Mapper


class ImpactMapper(Mapper):
    """Map Impact API responses to canonical schema."""

    @property
    def network_name(self) -> str:
        return "impact"

    def map_advertiser(self, raw: dict) -> dict:
        # TODO: Implement after API access and schema finalized
        raise NotImplementedError

    def map_ad(self, raw: dict, advertiser_id: int) -> dict:
        # TODO: Implement after API access and schema finalized
        raise NotImplementedError
