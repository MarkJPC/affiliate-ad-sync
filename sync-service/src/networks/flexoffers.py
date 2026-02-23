"""FlexOffers API client - Mark's responsibility."""

import logging
import time

import httpx

from .base import NetworkClient

logger = logging.getLogger(__name__)

MAX_RETRIES = 3


class FlexOffersClient(NetworkClient):
    """Client for the FlexOffers affiliate network API v3.

    FlexOffers assigns a separate API key per domain. Each key only returns
    advertisers/ads approved for that specific domain.
    """

    BASE_URL = "https://api.flexoffers.com/v3"

    def __init__(self, api_key: str, domain: str | None = None):
        """Initialize the FlexOffers client.

        Args:
            api_key: FlexOffers API key for the domain.
            domain: Domain name this client is for (for logging/tracking).
        """
        self.api_key = api_key
        self.domain = domain
        self._client = httpx.Client(timeout=30.0)

    @property
    def network_name(self) -> str:
        return "flexoffers"

    def _get_headers(self) -> dict[str, str]:
        """Return headers with API key authentication."""
        return {"apiKey": self.api_key}

    def fetch_advertisers(self) -> list[dict]:
        """Fetch all approved advertisers, handling pagination.

        Returns:
            List of raw advertiser dicts from the API. May return partial
            results if some pages fail after retries.
        """
        advertisers: list[dict] = []
        page = 1
        page_size = 25  # Max allowed by FlexOffers API

        domain_info = f" for {self.domain}" if self.domain else ""
        logger.info(f"Fetching FlexOffers advertisers{domain_info}")

        while True:
            params = {
                "ProgramStatus": "Approved",
                "ApplicationStatus": "Approved",
                "Page": page,
                "pageSize": page_size,
            }

            # Retry logic for this page
            response = None
            for attempt in range(1, MAX_RETRIES + 1):
                try:
                    response = self._client.get(
                        f"{self.BASE_URL}/advertisers",
                        headers=self._get_headers(),
                        params=params,
                    )
                    # Handle 403 rate limit with exponential backoff
                    if response.status_code == 403:
                        if attempt < MAX_RETRIES:
                            wait_time = 2 ** attempt  # 2s, 4s, 8s
                            logger.warning(
                                f"Rate limit hit on page {page} (attempt {attempt}/{MAX_RETRIES}), "
                                f"waiting {wait_time}s before retry..."
                            )
                            time.sleep(wait_time)
                            continue
                        # All retries exhausted
                        logger.warning(
                            f"Rate limit exceeded on page {page} after {MAX_RETRIES} retries, "
                            f"returning {len(advertisers)} partial results{domain_info}"
                        )
                        return advertisers
                    break  # Success or other status, exit retry loop
                except httpx.RequestError as e:
                    logger.warning(
                        f"Request error on page {page} (attempt {attempt}/{MAX_RETRIES}): {e}"
                    )
                    if attempt < MAX_RETRIES:
                        continue
                    # All retries exhausted
                    logger.warning(
                        f"Failed to fetch page {page} after {MAX_RETRIES} retries, "
                        f"returning {len(advertisers)} partial results{domain_info}"
                    )
                    return advertisers

            # Handle specific error codes
            if response.status_code == 401:
                raise httpx.HTTPStatusError(
                    "Invalid FlexOffers API key",
                    request=response.request,
                    response=response,
                )
            if response.status_code == 204:
                # No content - empty result
                logger.debug(f"Page {page}: no content (204)")
                break

            try:
                response.raise_for_status()
            except httpx.HTTPStatusError as e:
                logger.warning(
                    f"HTTP error on page {page}: {e}, "
                    f"returning {len(advertisers)} partial results{domain_info}"
                )
                return advertisers

            data = response.json()

            # Handle both list response and paginated object response
            if isinstance(data, list):
                page_advertisers = data
            else:
                page_advertisers = data.get("results", data.get("advertisers", data.get("data", [])))

            if not page_advertisers:
                logger.debug(f"Page {page}: empty response, stopping pagination")
                break

            # Log each advertiser at DEBUG level
            for adv in page_advertisers:
                logger.debug(
                    f"Advertiser: id={adv.get('id')}, name={adv.get('name')}, "
                    f"domainUrl={adv.get('domainUrl')}"
                )

            advertisers.extend(page_advertisers)
            logger.debug(f"Page {page}: fetched {len(page_advertisers)} advertisers")

            # If we got fewer than page_size, we've reached the end
            if len(page_advertisers) < page_size:
                break

            page += 1

        logger.info(f"Fetched {len(advertisers)} total advertisers{domain_info}")
        return advertisers

    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        """Fetch all ads for an advertiser (banners and text links).

        Args:
            advertiser_id: The FlexOffers advertiser ID.

        Returns:
            List of raw link/promotion dicts from the API.
            Includes both banners (with dimensions) and text links (0x0).
            May return partial results if some pages fail after retries.
        """
        ads: list[dict] = []
        page = 1
        page_size = 100  # Max allowed by FlexOffers API for promotions

        logger.debug(f"Fetching ads for advertiser {advertiser_id}")

        while True:
            params = {
                "page": page,
                "pageSize": page_size,
                "advertiserIds": advertiser_id,
            }

            # Retry logic for this page
            response = None
            for attempt in range(1, MAX_RETRIES + 1):
                try:
                    response = self._client.get(
                        f"{self.BASE_URL}/promotions",
                        headers=self._get_headers(),
                        params=params,
                    )
                    # Handle 403 rate limit with exponential backoff
                    if response.status_code == 403:
                        if attempt < MAX_RETRIES:
                            wait_time = 2 ** attempt  # 2s, 4s, 8s
                            logger.warning(
                                f"Rate limit hit for advertiser {advertiser_id} "
                                f"(attempt {attempt}/{MAX_RETRIES}), waiting {wait_time}s before retry..."
                            )
                            time.sleep(wait_time)
                            continue
                        # All retries exhausted
                        logger.warning(
                            f"Rate limit exceeded for advertiser {advertiser_id} after {MAX_RETRIES} retries, "
                            f"returning {len(ads)} partial results"
                        )
                        return ads
                    break  # Success or other status, exit retry loop
                except httpx.RequestError as e:
                    logger.warning(
                        f"Request error fetching ads for advertiser {advertiser_id} "
                        f"page {page} (attempt {attempt}/{MAX_RETRIES}): {e}"
                    )
                    if attempt < MAX_RETRIES:
                        continue
                    # All retries exhausted
                    logger.warning(
                        f"Failed to fetch ads for advertiser {advertiser_id} page {page} "
                        f"after {MAX_RETRIES} retries, returning {len(ads)} partial results"
                    )
                    return ads

            # Handle specific error codes
            if response.status_code == 401:
                raise httpx.HTTPStatusError(
                    "Invalid FlexOffers API key",
                    request=response.request,
                    response=response,
                )
            if response.status_code == 204:
                # No content - empty result
                logger.debug(f"Advertiser {advertiser_id} page {page}: no content (204)")
                break

            try:
                response.raise_for_status()
            except httpx.HTTPStatusError as e:
                logger.warning(
                    f"HTTP error fetching ads for advertiser {advertiser_id} page {page}: {e}, "
                    f"returning {len(ads)} partial results"
                )
                return ads

            data = response.json()

            # API returns {"results": [...], "totalCount": N}
            if isinstance(data, dict):
                page_ads = data.get("results", [])
            else:
                page_ads = data if isinstance(data, list) else []

            if not page_ads:
                logger.debug(f"Advertiser {advertiser_id} page {page}: empty response")
                break

            # Log each ad at DEBUG level
            for ad in page_ads:
                link_type = ad.get("linkType", "unknown")
                width = ad.get("bannerWidth") or 0
                height = ad.get("bannerHeight") or 0
                logger.debug(
                    f"Ad: id={ad.get('linkId')}, name={ad.get('linkName')}, "
                    f"type={link_type}, {width}x{height}"
                )

            # Store all ads; export logic filters by dimension
            ads.extend(page_ads)
            logger.debug(
                f"Advertiser {advertiser_id} page {page}: fetched {len(page_ads)} ads"
            )

            # If we got fewer than page_size, we've reached the end
            if len(page_ads) < page_size:
                break

            page += 1

        logger.debug(f"Fetched {len(ads)} total ads for advertiser {advertiser_id}")
        return ads

    def close(self) -> None:
        """Close the HTTP client."""
        self._client.close()

    def __enter__(self) -> "FlexOffersClient":
        return self

    def __exit__(self, *args) -> None:
        self.close()
