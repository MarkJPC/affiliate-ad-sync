"""Awin API client - Nadia's responsibility."""

from .base import NetworkClient


class AwinClient(NetworkClient):
    """Client for the Awin affiliate network API."""

    def __init__(self, api_token: str, publisher_id: str):
        self.api_token = api_token
        self.publisher_id = publisher_id

    @property
    def network_name(self) -> str:
        return "awin"

    def fetch_advertisers(self) -> list[dict]:
        # TODO: Implement after API access is available
        raise NotImplementedError

    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        # TODO: Implement after API access is available
        raise NotImplementedError
