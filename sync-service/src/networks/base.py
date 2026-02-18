"""Abstract base class for network clients."""

import logging
from abc import ABC, abstractmethod

logger = logging.getLogger(__name__)


class NetworkClient(ABC):
    """Abstract base class for affiliate network API clients.

    Each network (FlexOffers, Awin, CJ, Impact) implements this interface
    to fetch advertisers and ads from their respective APIs.
    """

    @property
    @abstractmethod
    def network_name(self) -> str:
        """Return the network identifier (e.g., 'flexoffers', 'awin')."""
        ...

    @abstractmethod
    def fetch_advertisers(self) -> list[dict]:
        """Fetch all advertisers/programs from the network.

        Returns:
            List of raw advertiser dicts from the API.
        """
        ...

    @abstractmethod
    def fetch_ads(self, advertiser_id: str) -> list[dict]:
        """Fetch all ads/creatives for an advertiser.

        Args:
            advertiser_id: The network-specific advertiser/program ID.

        Returns:
            List of raw ad dicts from the API.
        """
        ...

    def sync(self, conn, site_domain: str | None = None) -> dict:
        """Sync advertisers and ads from this network to the database.

        Fetches all advertisers and their ads, using hash-based change detection
        to skip unchanged records. Creates site_advertiser_rules and cleans up
        stale ads/advertisers.

        Args:
            conn: Database connection.
            site_domain: If set (e.g. FlexOffers per-domain keys), create rules
                for this site only. Otherwise create rules for ALL active sites.

        Returns:
            Dict with sync statistics.
        """
        from .. import db
        from ..mappers import get_mapper

        mapper = get_mapper(self.network_name)
        stats = {
            "advertisers_synced": 0,
            "ads_synced": 0,
            "ads_deleted": 0,
            "errors": 0,
            "ad_types": {},
        }

        log_id = db.create_sync_log(conn, self.network_name, site_domain=site_domain)

        try:
            # Determine which sites to create rules for
            if site_domain:
                site_row = db.get_site_by_domain(conn, site_domain)
                rule_site_ids = [site_row["id"]] if site_row else []
            else:
                rule_site_ids = [s["id"] for s in db.get_active_sites(conn)]

            raw_advertisers = self.fetch_advertisers()
            logger.info(f"[{self.network_name}] Fetched {len(raw_advertisers)} advertisers")

            seen_advertiser_ids: set[str] = set()

            for raw_adv in raw_advertisers:
                try:
                    adv_data = mapper.map_advertiser(raw_adv)
                    seen_advertiser_ids.add(adv_data["network_program_id"])

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

                    # Create site_advertiser_rules for applicable sites
                    for sid in rule_site_ids:
                        db.ensure_site_advertiser_rule(conn, sid, advertiser_id)

                    # Fetch and process ads for this advertiser
                    raw_ads = self.fetch_ads(adv_data["network_program_id"])
                    seen_ad_ids: set[str] = set()

                    for raw_ad in raw_ads:
                        try:
                            ad_data = mapper.map_ad(raw_ad, advertiser_id)

                            # Track ad types for stats
                            creative_type = ad_data.get("creative_type", "banner")
                            stats["ad_types"][creative_type] = stats["ad_types"].get(creative_type, 0) + 1

                            # Warn on missing image_url
                            if not ad_data.get("image_url"):
                                logger.warning(
                                    f"[{self.network_name}] Ad {ad_data.get('network_link_id')} has no image_url"
                                )

                            network_ad_id = ad_data["network_link_id"]
                            seen_ad_ids.add(network_ad_id)

                            # Map to db columns (network_link_id -> network_ad_id)
                            db_ad = {
                                "network": ad_data["network"],
                                "network_ad_id": network_ad_id,
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
                                "image_url": ad_data.get("image_url"),
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

                        except Exception as e:
                            logger.warning(f"[{self.network_name}] Error processing ad: {e}")
                            stats["errors"] += 1

                    # Delete stale ads for this advertiser
                    if seen_ad_ids:
                        deleted = db.delete_stale_ads(conn, self.network_name, advertiser_id, seen_ad_ids)
                        stats["ads_deleted"] += deleted

                except Exception as e:
                    logger.warning(f"[{self.network_name}] Error processing advertiser: {e}")
                    stats["errors"] += 1

            # Deactivate advertisers not seen this sync
            if seen_advertiser_ids:
                db.deactivate_stale_advertisers(conn, self.network_name, seen_advertiser_ids)

            db.update_sync_log(
                conn,
                log_id,
                advertisers_synced=stats["advertisers_synced"],
                ads_synced=stats["ads_synced"],
                ads_deleted=stats["ads_deleted"],
                status="success",
            )

            # Enhanced logging output
            logger.info(f"[{self.network_name}] Sync complete:")
            logger.info(f"[{self.network_name}]   Advertisers: {stats['advertisers_synced']} synced")
            logger.info(f"[{self.network_name}]   Ads: {stats['ads_synced']} synced, {stats['ads_deleted']} deleted")
            if stats["ad_types"]:
                ad_types_str = ", ".join(f"{count} {atype}" for atype, count in sorted(stats["ad_types"].items()))
                logger.info(f"[{self.network_name}]   Ad types: {ad_types_str}")
            if stats["errors"] > 0:
                logger.warning(f"[{self.network_name}]   Errors: {stats['errors']}")

        except Exception as e:
            logger.error(f"[{self.network_name}] Sync failed: {e}")
            db.update_sync_log(conn, log_id, status="failed", error_message=str(e))
            raise

        return stats
