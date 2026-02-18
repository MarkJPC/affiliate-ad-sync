"""Impact API client - Rag's responsibility."""

import logging
from collections import defaultdict

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

    def sync(self, conn) -> dict:
        """Sync campaigns and ads from Impact to the database.

        Overrides the base class sync() because Impact's per-campaign ads
        endpoint returns 403. Instead, we batch-fetch all ads globally
        and group them by CampaignId before processing.

        Args:
            conn: Database connection.

        Returns:
            Dict with sync statistics.
        """
        from .. import db
        from ..mappers import get_mapper

        mapper = get_mapper(self.network_name)
        stats = {
            "advertisers_synced": 0,
            "ads_synced": 0,
            "ads_created": 0,
            "ads_updated": 0,
            "errors": 0,
            "ad_types": {},
        }

        log_id = db.create_sync_log(conn, self.network_name)

        try:
            # Fetch all campaigns and all ads up front
            raw_campaigns = self.fetch_advertisers()
            logger.info(f"[impact] Fetched {len(raw_campaigns)} campaigns")

            raw_ads = self.fetch_ads("")
            logger.info(f"[impact] Fetched {len(raw_ads)} ads")

            # Group ads by CampaignId for lookup
            ads_by_campaign: dict[str, list[dict]] = defaultdict(list)
            for raw_ad in raw_ads:
                ads_by_campaign[raw_ad.get("CampaignId", "")].append(raw_ad)

            # Process each campaign and its ads
            for raw_camp in raw_campaigns:
                try:
                    adv_data = mapper.map_advertiser(raw_camp)

                    # Map canonical keys to db columns
                    db_adv = {
                        "network": adv_data["network"],
                        "network_advertiser_id": adv_data["network_program_id"],
                        "name": adv_data["network_program_name"],
                        "website_url": adv_data.get("website_url"),
                        "category": adv_data.get("category"),
                        "epc": adv_data.get("epc", 0),
                        "raw_hash": adv_data["raw_hash"],
                    }

                    advertiser_id, _ = db.upsert_advertiser(conn, db_adv)
                    stats["advertisers_synced"] += 1

                    # Look up ads for this campaign from the grouped dict
                    campaign_id = adv_data["network_program_id"]
                    campaign_ads = ads_by_campaign.get(campaign_id, [])

                    for raw_ad in campaign_ads:
                        try:
                            ad_data = mapper.map_ad(raw_ad, advertiser_id)

                            # Track ad types for stats
                            creative_type = ad_data.get("creative_type", "banner")
                            stats["ad_types"][creative_type] = stats["ad_types"].get(creative_type, 0) + 1

                            db_ad = {
                                "network": ad_data["network"],
                                "network_ad_id": ad_data["network_link_id"],
                                "advertiser_id": advertiser_id,
                                "creative_type": ad_data.get("creative_type", "banner"),
                                "tracking_url": ad_data["tracking_url"],
                                "destination_url": ad_data.get("destination_url"),
                                "status": ad_data.get("status", "active"),
                                "epc": ad_data.get("epc", 0),
                                "raw_hash": ad_data["raw_hash"],
                                "advert_name": ad_data["advert_name"],
                                "bannercode": ad_data["bannercode"],
                                "imagetype": ad_data.get("imagetype", ""),
                                "image_url": ad_data["image_url"],
                                "width": ad_data["width"],
                                "height": ad_data["height"],
                                "campaign_name": ad_data.get("campaign_name", "General Promotion"),
                                "enable_stats": ad_data.get("enable_stats", "Y"),
                                "show_everyone": ad_data.get("show_everyone", "Y"),
                                "show_desktop": ad_data.get("show_desktop", "Y"),
                                "show_mobile": ad_data.get("show_mobile", "Y"),
                                "show_tablet": ad_data.get("show_tablet", "Y"),
                                "show_ios": ad_data.get("show_ios", "Y"),
                                "show_android": ad_data.get("show_android", "Y"),
                                "weight": ad_data.get("weight", 2),
                                "autodelete": ad_data.get("autodelete", "Y"),
                                "autodisable": ad_data.get("autodisable", "N"),
                                "budget": ad_data.get("budget", 0),
                                "click_rate": ad_data.get("click_rate", 0),
                                "impression_rate": ad_data.get("impression_rate", 0),
                                "state_required": ad_data.get("state_required", "N"),
                                "geo_cities": ad_data.get("geo_cities", "a:0:{}"),
                                "geo_states": ad_data.get("geo_states", "a:0:{}"),
                                "geo_countries": ad_data.get("geo_countries", "a:0:{}"),
                                "schedule_start": ad_data.get("schedule_start", 0),
                                "schedule_end": ad_data.get("schedule_end", 2650941780),
                            }

                            _, changed = db.upsert_ad(conn, db_ad)
                            stats["ads_synced"] += 1
                            if changed:
                                stats["ads_updated"] += 1

                        except Exception as e:
                            logger.warning(f"[impact] Error processing ad: {e}")
                            stats["errors"] += 1

                except Exception as e:
                    logger.warning(f"[impact] Error processing campaign: {e}")
                    stats["errors"] += 1

            # Don't pass ad_types to update_sync_log (not a db column)
            db_stats = {k: v for k, v in stats.items() if k != "ad_types"}
            db.update_sync_log(conn, log_id, **db_stats)

            # Enhanced logging output
            logger.info("[impact] Sync complete:")
            logger.info(f"[impact]   Advertisers: {stats['advertisers_synced']} synced")
            logger.info(f"[impact]   Ads: {stats['ads_synced']} synced, {stats['ads_updated']} updated")
            if stats["ad_types"]:
                ad_types_str = ", ".join(f"{count} {atype}" for atype, count in sorted(stats["ad_types"].items()))
                logger.info(f"[impact]   Ad types: {ad_types_str}")
            if stats["errors"] > 0:
                logger.warning(f"[impact]   Errors: {stats['errors']}")

        except Exception as e:
            logger.error(f"[impact] Sync failed: {e}")
            db.update_sync_log(conn, log_id, errors=1, error_message=str(e))
            raise

        return stats

    def close(self) -> None:
        """Close the HTTP client."""
        self._client.close()

    def __enter__(self) -> "ImpactClient":
        return self

    def __exit__(self, *args) -> None:
        self.close()
