"""FlexOffers API client - Mark's responsibility."""

from .base import NetworkClient


class FlexOffersClient(NetworkClient):
    """Client for the FlexOffers affiliate network API."""

    def __init__(self, api_key: str):
        self.api_key = api_key

    @property
    def network_name(self) -> str:
        return "flexoffers"

    def fetch_advertisers(self) -> list[dict]:
        # TODO: Implement after API access is available
        raise NotImplementedError

    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        # TODO: Implement after API access is available
        raise NotImplementedError
