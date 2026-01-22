"""CJ (Commission Junction) API client - Rag's responsibility."""

from .base import NetworkClient


class CJClient(NetworkClient):
    """Client for the CJ Affiliate network API."""

    def __init__(self, api_token: str, website_id: str):
        self.api_token = api_token
        self.website_id = website_id

    @property
    def network_name(self) -> str:
        return "cj"

    def fetch_advertisers(self) -> list[dict]:
        # TODO: Implement after API access is available
        raise NotImplementedError

    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        # TODO: Implement after API access is available
        raise NotImplementedError
