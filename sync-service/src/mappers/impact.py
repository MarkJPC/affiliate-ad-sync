"""Impact API response mapper - Rag's responsibility."""

import re

from .base import Mapper


class ImpactMapper(Mapper):
    """Map Impact API responses to canonical schema."""

    @property
    def network_name(self) -> str:
        return "impact"

    def map_advertiser(self, raw: dict) -> dict:
        """Map Impact campaign response to canonical advertiser schema.

        Args:
            raw: Raw API response for a campaign.

        Returns:
            Dict with canonical advertiser fields.
        """
        # Status is active only if ContractStatus is "Active"
        contract_status = raw.get("ContractStatus", "")
        is_active = contract_status == "Active"

        return {
            "network": "impact",
            "network_program_id": str(raw.get("CampaignId", "")),
            "network_program_name": raw.get("CampaignName", ""),
            "status": "active" if is_active else "paused",
            "website_url": raw.get("CampaignUrl", ""),
            "category": "",  # Impact doesn't provide category in campaign response
            "epc": 0,
            "raw_hash": Mapper.compute_hash(raw),
        }

    def map_ad(self, raw: dict, advertiser_id: int) -> dict:
        """Map Impact ad response to canonical ad schema.

        Args:
            raw: Raw API response for an ad/creative.
            advertiser_id: Database ID of the parent advertiser.

        Returns:
            Dict with canonical ad fields matching the ads table schema.
        """
        ad_type = raw.get("Type", "").upper()

        # Determine creative_type
        if ad_type == "BANNER":
            creative_type = "banner"
        elif ad_type == "TEXT_LINK":
            creative_type = "text"
        else:
            creative_type = "html"

        # Dimensions: strings to ints, empty string -> 0
        width = int(raw.get("Width") or 0)
        height = int(raw.get("Height") or 0)

        # Extract fields
        ad_id = str(raw.get("Id", ""))
        ad_name = raw.get("Name", "")
        tracking_url = raw.get("TrackingLink", "")
        image_url = raw.get("CreativeUrl", "")

        # Build advert_name: {width}X{height}-{advertiser_id}-{sanitized_name}-{ad_id}-General
        sanitized_name = self._sanitize_name(ad_name)
        advert_name = f"{width}X{height}-{advertiser_id}-{sanitized_name}-{ad_id}-General"

        # Build bannercode: prefer Code from API (already includes tracking pixel),
        # fall back to constructing it from tracking URL + image URL
        code = raw.get("Code", "")
        if code:
            bannercode = code
        else:
            bannercode = self._construct_bannercode(tracking_url, image_url)

        return {
            # Internal fields
            "network": "impact",
            "network_link_id": ad_id,
            "network_program_id": str(advertiser_id),
            "advertiser_id": advertiser_id,
            "creative_type": creative_type,
            "tracking_url": tracking_url,
            "destination_url": raw.get("LandingPageUrl", ""),
            "status": "active",  # Impact ads don't have a State field
            "epc": 0,  # EPC comes from separate reporting endpoint
            "raw_hash": Mapper.compute_hash(raw),
            "name": ad_name,

            # AdRotate fields
            "advert_name": advert_name,
            "bannercode": bannercode,
            "imagetype": "",
            "image_url": image_url,
            "width": width,
            "height": height,
            "campaign_name": "General Promotion",

            # Display settings (all Y)
            "enable_stats": "Y",
            "show_everyone": "Y",
            "show_desktop": "Y",
            "show_mobile": "Y",
            "show_tablet": "Y",
            "show_ios": "Y",
            "show_android": "Y",

            # Auto settings
            "autodelete": "Y",
            "autodisable": "N",

            # Budget (all 0)
            "budget": 0,
            "click_rate": 0,
            "impression_rate": 0,

            # Geo targeting (PHP serialized empty arrays)
            "state_required": "N",
            "geo_cities": "a:0:{}",
            "geo_states": "a:0:{}",
            "geo_countries": "a:0:{}",

            # Schedule (no start, far future end)
            "schedule_start": 0,
            "schedule_end": 2650941780,
        }

    def _sanitize_name(self, name: str) -> str:
        """Remove special characters and spaces for advert_name.

        Args:
            name: Original name string.

        Returns:
            Sanitized name with only alphanumeric characters.
        """
        return re.sub(r'[^a-zA-Z0-9]', '', name)

    def _construct_bannercode(self, tracking_url: str, image_url: str) -> str:
        """Build HTML banner code as fallback when Code is not provided.

        Args:
            tracking_url: The affiliate tracking URL.
            image_url: The banner image URL.

        Returns:
            HTML string for the banner.
        """
        return f'<a href="{tracking_url}" rel="sponsored"><img src="{image_url}" /></a>'
