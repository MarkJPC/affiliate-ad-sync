"""
Awin API client - Nadia's responsibility.

Implements:
- fetch_advertisers(): GET /publishers/{publisherId}/programmes
- fetch_ads(advertiser_id): POST /publisher/{publisherId}/promotions (Offers API)

Auth:
- Most Publisher APIs use OAuth 2.0 Bearer Token style:
  Authorization: Bearer <your token>
- Awin "API token" shown in the UI is often a UUID string (36 chars). That is OK.
"""

import logging
import httpx

from .base import NetworkClient

logger = logging.getLogger(__name__)

MAX_RETRIES = 3


class AwinClient(NetworkClient):
    """Client for the Awin affiliate network API (Publisher APIs)."""

    BASE_URL = "https://api.awin.com"

    def __init__(self, api_token: str, publisher_id: str | int):
        self.api_token = api_token
        self.publisher_id = int(publisher_id)
        self._client = httpx.Client(timeout=30.0)

    @property
    def network_name(self) -> str:
        return "awin"

    def _get_headers(self) -> dict[str, str]:
        return {"Authorization": f"Bearer {self.api_token}"}

    def fetch_advertisers(self) -> list[dict]:
        """
        Fetch all programmes for this publisher.
        Awin returns an array of programme objects.
        """
        url = f"{self.BASE_URL}/publishers/{self.publisher_id}/programmes"

        logger.info(f"Fetching Awin programmes for publisher {self.publisher_id}")

        response = None
        for attempt in range(1, MAX_RETRIES + 1):
            try:
                response = self._client.get(
                    url,
                    headers=self._get_headers(),
                    # Some Awin docs also show accessToken as a query param.
                    # Including it improves compatibility.
                    params={"accessToken": self.api_token, "relationship": "joined"},
                )
                break
            except httpx.RequestError as e:
                logger.warning(
                    f"Awin programmes request error (attempt {attempt}/{MAX_RETRIES}): {e}"
                )
                if attempt == MAX_RETRIES:
                    return []

        if response.status_code == 401:
            raise httpx.HTTPStatusError(
                "Awin unauthorized (check API token / permissions)",
                request=response.request,
                response=response,
            )

        response.raise_for_status()

        data = response.json()

        # Docs indicate this endpoint returns a JSON array
        if isinstance(data, list):
            return data

        # Fallback if Awin wraps results
        return data.get("data", data.get("results", []))

    def fetch_ads(self, advertiser_id: str | int) -> list[dict]:
        """
        Fetch offers/promotions for a given advertiser/programme.

        Uses:
        POST /publisher/{publisherId}/promotions
        with filters.advertiserIds = [advertiser_id]

        Note: These "offers" are not always banner creatives with dimensions.
        We'll map them appropriately in the AwinMapper once we confirm ads table schema.
        """
        url = f"{self.BASE_URL}/publisher/{self.publisher_id}/promotions"

        offers: list[dict] = []
        page = 1
        page_size = 200  # allowed 10-200 per docs

        logger.debug(
            f"Fetching Awin offers for advertiser {advertiser_id} (publisher {self.publisher_id})"
        )

        while True:
            body = {
                "filters": {
                    "advertiserIds": [int(advertiser_id)],
                    "membership": "all",
                    "status": "active",
                    "type": "all",
                },
                "pagination": {
                    "page": page,
                    "pageSize": page_size,
                },
            }

            response = None
            for attempt in range(1, MAX_RETRIES + 1):
                try:
                    response = self._client.post(
                        url,
                        headers=self._get_headers(),
                        params={"accessToken": self.api_token},
                        json=body,
                    )
                    break
                except httpx.RequestError as e:
                    logger.warning(
                        f"Awin offers request error advertiser {advertiser_id} page {page} "
                        f"(attempt {attempt}/{MAX_RETRIES}): {e}"
                    )
                    if attempt == MAX_RETRIES:
                        return offers

            if response.status_code == 401:
                raise httpx.HTTPStatusError(
                    "Awin unauthorized (check API token / permissions)",
                    request=response.request,
                    response=response,
                )

            response.raise_for_status()

            payload = response.json()

            # Some APIs return list, others wrap
            if isinstance(payload, list):
                page_items = payload
            else:
                page_items = (
                    payload.get("data")
                    or payload.get("results")
                    or payload.get("offers")
                    or []
                )

            if not page_items:
                break

            offers.extend(page_items)

            # end pagination if fewer than requested
            if len(page_items) < page_size:
                break

            page += 1

        return offers

    def close(self) -> None:
        self._client.close()

    def __enter__(self) -> "AwinClient":
        return self

    def __exit__(self, *args) -> None:
        self.close()
