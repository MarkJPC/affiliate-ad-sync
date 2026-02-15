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
        """Fetch all campaigns (advertisers), handling pagination.

        Returns:
            List of raw campaign dicts from the API. May return partial
            results if some pages fail after retries.
        """
        campaigns: list[dict] = []
        page = 1
        page_size = 100  # Impact default and max

        logger.info("Fetching Impact campaigns")

        while True:
            params = {"Page": page, "PageSize": page_size}

            # Retry logic for this page
            response = None
            for attempt in range(1, MAX_RETRIES + 1):
                try:
                    response = self._client.get(
                        f"{BASE_URL}/Mediapartners/{self.account_sid}/Campaigns",
                        params=params,
                    )
                    break  # Success, exit retry loop
                except httpx.RequestError as e:
                    logger.warning(
                        f"Request error on page {page} (attempt {attempt}/{MAX_RETRIES}): {e}"
                    )
                    if attempt < MAX_RETRIES:
                        continue
                    # All retries exhausted
                    logger.warning(
                        f"Failed to fetch page {page} after {MAX_RETRIES} retries, "
                        f"returning {len(campaigns)} partial results"
                    )
                    return campaigns

            # Handle specific error codes
            if response.status_code == 401:
                raise httpx.HTTPStatusError(
                    "Invalid Impact credentials",
                    request=response.request,
                    response=response,
                )
            if response.status_code == 403:
                raise httpx.HTTPStatusError(
                    "Impact rate limit exceeded or access denied",
                    request=response.request,
                    response=response,
                )
            if response.status_code == 204:
                logger.debug(f"Page {page}: no content (204)")
                break

            try:
                response.raise_for_status()
            except httpx.HTTPStatusError as e:
                logger.warning(
                    f"HTTP error on page {page}: {e}, "
                    f"returning {len(campaigns)} partial results"
                )
                return campaigns

            data = response.json()
            page_campaigns = data.get("Campaigns", [])

            if not page_campaigns:
                logger.debug(f"Page {page}: empty response, stopping pagination")
                break

            for camp in page_campaigns:
                logger.debug(
                    f"Campaign: id={camp.get('CampaignId')}, "
                    f"name={camp.get('CampaignName')}, "
                    f"status={camp.get('ContractStatus')}"
                )

            campaigns.extend(page_campaigns)
            logger.debug(f"Page {page}: fetched {len(page_campaigns)} campaigns")

            # Check if there are more pages
            next_page_uri = data.get("@nextpageuri", "")
            if not next_page_uri:
                break

            page += 1

        logger.info(f"Fetched {len(campaigns)} total campaigns")
        return campaigns

    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        """Fetch all ads globally, handling pagination.

        Note: Impact's per-campaign ads endpoint returns 403, so this
        fetches ALL ads from the global /Ads endpoint regardless of
        the advertiser_id parameter. Grouping by campaign is handled
        in the sync() override.

        Args:
            advertiser_id: Ignored — exists to satisfy base class interface.

        Returns:
            List of raw ad dicts from the API. May return partial
            results if some pages fail after retries.
        """
        ads: list[dict] = []
        page = 1
        page_size = 100  # Impact default and max

        logger.info("Fetching Impact ads (global endpoint)")

        while True:
            params = {"Page": page, "PageSize": page_size}

            # Retry logic for this page
            response = None
            for attempt in range(1, MAX_RETRIES + 1):
                try:
                    response = self._client.get(
                        f"{BASE_URL}/Mediapartners/{self.account_sid}/Ads",
                        params=params,
                    )
                    break  # Success, exit retry loop
                except httpx.RequestError as e:
                    logger.warning(
                        f"Request error fetching ads page {page} "
                        f"(attempt {attempt}/{MAX_RETRIES}): {e}"
                    )
                    if attempt < MAX_RETRIES:
                        continue
                    # All retries exhausted
                    logger.warning(
                        f"Failed to fetch ads page {page} after {MAX_RETRIES} retries, "
                        f"returning {len(ads)} partial results"
                    )
                    return ads

            # Handle specific error codes
            if response.status_code == 401:
                raise httpx.HTTPStatusError(
                    "Invalid Impact credentials",
                    request=response.request,
                    response=response,
                )
            if response.status_code == 403:
                raise httpx.HTTPStatusError(
                    "Impact rate limit exceeded or access denied",
                    request=response.request,
                    response=response,
                )
            if response.status_code == 204:
                logger.debug(f"Ads page {page}: no content (204)")
                break

            try:
                response.raise_for_status()
            except httpx.HTTPStatusError as e:
                logger.warning(
                    f"HTTP error on ads page {page}: {e}, "
                    f"returning {len(ads)} partial results"
                )
                return ads

            data = response.json()
            page_ads = data.get("Ads", [])

            if not page_ads:
                logger.debug(f"Ads page {page}: empty response, stopping pagination")
                break

            for ad in page_ads:
                logger.debug(
                    f"Ad: id={ad.get('Id')}, name={ad.get('Name')}, "
                    f"type={ad.get('Type')}, campaign={ad.get('CampaignId')}"
                )

            ads.extend(page_ads)
            logger.debug(f"Ads page {page}: fetched {len(page_ads)} ads")

            # Check if there are more pages
            next_page_uri = data.get("@nextpageuri", "")
            if not next_page_uri:
                break

            page += 1

        logger.info(f"Fetched {len(ads)} total ads")
        return ads

    def close(self) -> None:
        """Close the HTTP client."""
        self._client.close()

    def __enter__(self) -> "ImpactClient":
        return self

    def __exit__(self, *args) -> None:
        self.close()
