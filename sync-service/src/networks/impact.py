"""Impact API client - Rag's responsibility."""

from .base import NetworkClient


class ImpactClient(NetworkClient):
    """Client for the Impact affiliate network API."""

    def __init__(self, account_sid: str, auth_token: str):
        self.account_sid = account_sid
        self.auth_token = auth_token

    @property
    def network_name(self) -> str:
        return "impact"

    def fetch_advertisers(self) -> list[dict]:
        # TODO: Implement after API access is available
        raise NotImplementedError

    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        # TODO: Implement after API access is available
        raise NotImplementedError
