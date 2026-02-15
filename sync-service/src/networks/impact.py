"""Impact API client - Rag's responsibility."""

import logging

import httpx

from .base import NetworkClient

logger = logging.getLogger(__name__)

MAX_RETRIES = 3
BASE_URL = "https://api.impact.com"


class ImpactClient(NetworkClient):
    """Client for the Impact affiliate network API.

    Impact uses HTTP Basic Auth (Account SID + Auth Token) and returns
    XML by default, so we must request JSON via Accept header.
    """

    def __init__(self, account_sid: str, auth_token: str):
        """Initialize the Impact client.

        Args:
            account_sid: Impact Account SID (used as Basic Auth username).
            auth_token: Impact Auth Token (used as Basic Auth password).
        """
        self.account_sid = account_sid
        self.auth_token = auth_token
        self._client = httpx.Client(
            timeout=30.0,
            auth=(account_sid, auth_token),
            headers={"Accept": "application/json"},
        )

    @property
    def network_name(self) -> str:
        return "impact"

    def fetch_advertisers(self) -> list[dict]:
        # TODO: Implement - GET /Mediapartners/{AccountSid}/Campaigns
        raise NotImplementedError

    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        # TODO: Implement - GET /Mediapartners/{AccountSid}/Ads
        raise NotImplementedError

    def close(self) -> None:
        """Close the HTTP client."""
        self._client.close()

    def __enter__(self) -> "ImpactClient":
        return self

    def __exit__(self, *args) -> None:
        self.close()
