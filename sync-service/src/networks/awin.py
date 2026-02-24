"""
Awin API client - Nadia's responsibility.

Implements:
- fetch_advertisers(): GET /publishers/{publisherId}/programmes
- fetch_ads(advertiser_id): Merges two endpoints:
    1. POST /publisher/{publisherId}/promotions (vouchers/text offers)
    2. GET /publishers/{publisherId}/advertisers/{advertiserId}/creatives (banners)

Auth:
- Most Publisher APIs use OAuth 2.0 Bearer Token style:
  Authorization: Bearer <your token>
- Awin "API token" shown in the UI is often a UUID string (36 chars). That is OK.
"""

import logging
import time

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
                # Handle 403 rate limit with exponential backoff
                if response.status_code == 403:
                    if attempt < MAX_RETRIES:
                        wait_time = 2 ** attempt  # 2s, 4s, 8s
                        logger.warning(
                            f"Rate limit hit on programmes (attempt {attempt}/{MAX_RETRIES}), "
                            f"waiting {wait_time}s before retry..."
                        )
                        time.sleep(wait_time)
                        continue
                    logger.warning(
                        f"Rate limit exceeded on programmes after {MAX_RETRIES} retries, "
                        f"returning empty list"
                    )
                    return []
                break  # Success or other status, exit retry loop
            except httpx.RequestError as e:
                logger.warning(
                    f"Awin programmes request error (attempt {attempt}/{MAX_RETRIES}): {e}"
                )
                if attempt < MAX_RETRIES:
                    time.sleep(2 ** attempt)
                    continue
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

    def fetch_creatives(self, advertiser_id: str | int) -> list[dict]:
        """
        Fetch banner creatives for a given advertiser/programme.

        Uses:
        GET /publishers/{publisherId}/advertisers/{advertiserId}/creatives

        Returns list of raw creative dicts with imageUrl, width, height, etc.
        """
        url = (
            f"{self.BASE_URL}/publishers/{self.publisher_id}"
            f"/advertisers/{advertiser_id}/creatives"
        )

        creatives: list[dict] = []
        page = 1
        page_size = 200

        logger.debug(
            f"Fetching Awin creatives for advertiser {advertiser_id} "
            f"(publisher {self.publisher_id})"
        )

        while True:
            response = None
            for attempt in range(1, MAX_RETRIES + 1):
                try:
                    response = self._client.get(
                        url,
                        headers=self._get_headers(),
                        params={
                            "accessToken": self.api_token,
                            "page": page,
                            "pageSize": page_size,
                        },
                    )
                    # Handle 403 rate limit with exponential backoff
                    if response.status_code == 403:
                        if attempt < MAX_RETRIES:
                            wait_time = 2 ** attempt
                            logger.warning(
                                f"Rate limit hit for creatives advertiser {advertiser_id} "
                                f"(attempt {attempt}/{MAX_RETRIES}), waiting {wait_time}s..."
                            )
                            time.sleep(wait_time)
                            continue
                        logger.warning(
                            f"Rate limit exceeded for creatives advertiser {advertiser_id} "
                            f"after {MAX_RETRIES} retries, returning {len(creatives)} partial results"
                        )
                        return creatives
                    break
                except httpx.RequestError as e:
                    logger.warning(
                        f"Awin creatives request error advertiser {advertiser_id} page {page} "
                        f"(attempt {attempt}/{MAX_RETRIES}): {e}"
                    )
                    if attempt < MAX_RETRIES:
                        time.sleep(2 ** attempt)
                        continue
                    return creatives

            if response.status_code == 204:
                logger.debug(f"Advertiser {advertiser_id} creatives page {page}: no content (204)")
                break

            if response.status_code == 401:
                raise httpx.HTTPStatusError(
                    "Awin unauthorized (check API token / permissions)",
                    request=response.request,
                    response=response,
                )

            response.raise_for_status()

            payload = response.json()

            if isinstance(payload, list):
                page_items = payload
            else:
                page_items = (
                    payload.get("data")
                    or payload.get("results")
                    or payload.get("creatives")
                    or []
                )

            if not page_items:
                break

            # Log each creative at DEBUG level
            for item in page_items:
                logger.debug(
                    f"Creative: id={item.get('id')}, "
                    f"size={item.get('width', 0)}x{item.get('height', 0)}, "
                    f"imageUrl={item.get('imageUrl', '')[:80]}"
                )

            creatives.extend(page_items)
            logger.debug(
                f"Advertiser {advertiser_id} creatives page {page}: "
                f"fetched {len(page_items)} creatives"
            )

            if len(page_items) < page_size:
                break

            page += 1

        logger.debug(
            f"Fetched {len(creatives)} total creatives for advertiser {advertiser_id}"
        )
        return creatives

    def fetch_ads(self, advertiser_id: str | int) -> list[dict]:
        """
        Fetch offers/promotions and creatives for a given advertiser/programme.

        Merges results from two endpoints:
        1. POST /publisher/{publisherId}/promotions — vouchers/text offers
        2. GET /publishers/{publisherId}/advertisers/{advertiserId}/creatives — banners

        Creative dicts are tagged with _source="creatives" so the mapper can
        distinguish them from promotions.
        """
        # --- Promotions endpoint ---
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
                    # Handle 403 rate limit with exponential backoff
                    if response.status_code == 403:
                        if attempt < MAX_RETRIES:
                            wait_time = 2 ** attempt
                            logger.warning(
                                f"Rate limit hit for offers advertiser {advertiser_id} "
                                f"(attempt {attempt}/{MAX_RETRIES}), waiting {wait_time}s..."
                            )
                            time.sleep(wait_time)
                            continue
                        logger.warning(
                            f"Rate limit exceeded for offers advertiser {advertiser_id} "
                            f"after {MAX_RETRIES} retries, returning {len(offers)} partial results"
                        )
                        return offers
                    break
                except httpx.RequestError as e:
                    logger.warning(
                        f"Awin offers request error advertiser {advertiser_id} page {page} "
                        f"(attempt {attempt}/{MAX_RETRIES}): {e}"
                    )
                    if attempt < MAX_RETRIES:
                        time.sleep(2 ** attempt)
                        continue
                    return offers

            if response.status_code == 204:
                logger.debug(f"Advertiser {advertiser_id} page {page}: no content (204)")
                break

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

            # Log each offer at DEBUG level
            for item in page_items:
                logger.debug(
                    f"Offer: id={item.get('promotionId')}, "
                    f"type={item.get('type', 'unknown')}, "
                    f"title={str(item.get('title', ''))[:60]}"
                )

            offers.extend(page_items)
            logger.debug(
                f"Advertiser {advertiser_id} page {page}: fetched {len(page_items)} offers"
            )

            # end pagination if fewer than requested
            if len(page_items) < page_size:
                break

            page += 1

        logger.debug(
            f"Fetched {len(offers)} total offers for advertiser {advertiser_id}"
        )

        # --- Creatives endpoint ---
        try:
            creatives = self.fetch_creatives(advertiser_id)
            # Tag each creative so the mapper can distinguish them
            for c in creatives:
                c["_source"] = "creatives"
            offers.extend(creatives)
            logger.debug(
                f"Advertiser {advertiser_id}: merged {len(creatives)} creatives, "
                f"{len(offers)} total items"
            )
        except Exception as e:
            logger.warning(
                f"Failed to fetch creatives for advertiser {advertiser_id}: {e}. "
                f"Continuing with {len(offers)} promotions only."
            )

        return offers

    def close(self) -> None:
        self._client.close()

    def __enter__(self) -> "AwinClient":
        return self

    def __exit__(self, *args) -> None:
        self.close()
