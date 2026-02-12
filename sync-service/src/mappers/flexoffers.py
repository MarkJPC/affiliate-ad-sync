"""FlexOffers API response mapper - Mark's responsibility."""

import re

from .base import Mapper


class FlexOffersMapper(Mapper):
    """Map FlexOffers API responses to canonical schema."""

    @property
    def network_name(self) -> str:
        return "flexoffers"

    def map_advertiser(self, raw: dict) -> dict:
        """Map FlexOffers advertiser/program response to canonical schema.

        Args:
            raw: Raw API response for an advertiser.

        Returns:
            Dict with canonical advertiser fields.
        """
        # Status is active only if both programStatus and applicationStatus are "Approved"
        program_status = raw.get("programStatus", "")
        application_status = raw.get("applicationStatus", "")
        is_active = program_status == "Approved" and application_status == "Approved"

        return {
            "network": "flexoffers",
            "network_program_id": str(raw.get("id", "")),
            "network_program_name": raw.get("name", ""),
            "status": "active" if is_active else "paused",
            "website_url": raw.get("domainUrl", ""),
            "category": raw.get("categoryNames", ""),
            "epc": float(raw.get("sevenDayEpc") or 0),
            "raw_hash": Mapper.compute_hash(raw),
        }

    def map_ad(self, raw: dict, advertiser_id: int) -> dict:
        """Map FlexOffers promotion to canonical ad schema.

        Args:
            raw: Raw API response for an ad/creative.
            advertiser_id: Database ID of the parent advertiser.

        Returns:
            Dict with canonical ad fields matching the ads table schema.
        """
        link_type = raw.get("linkType", "").lower()

        # Determine creative_type
        if "banner" in link_type:
            creative_type = "banner"
        elif "text" in link_type:
            creative_type = "text"
        else:
            creative_type = "html"

        # Dimensions: use actual values or 0 for text links
        width = raw.get("bannerWidth") or 0
        height = raw.get("bannerHeight") or 0

        # Extract link info
        link_id = str(raw.get("linkId", ""))
        link_name = raw.get("linkName", "")
        tracking_url = raw.get("linkUrl", "")
        image_url = raw.get("imageUrl", "")

        # Build advert_name: {width}X{height}-{advertiser_id}-{sanitized_name}-{link_id_suffix}-General
        sanitized_name = self._sanitize_name(link_name)
        # Use last segment of linkId for brevity (split on '.')
        link_id_suffix = link_id.split(".")[-1] if "." in link_id else link_id
        advert_name = f"{width}X{height}-{advertiser_id}-{sanitized_name}-{link_id_suffix}-General"

        # Build bannercode
        html_code = raw.get("htmlCode")
        if html_code:
            bannercode = html_code
        else:
            bannercode = self._construct_bannercode(tracking_url, image_url)

        return {
            # Internal fields
            "network": "flexoffers",
            "network_link_id": link_id,
            "network_program_id": str(advertiser_id),
            "advertiser_id": advertiser_id,
            "creative_type": creative_type,
            "tracking_url": tracking_url,
            "status": "active",
            "epc": float(raw.get("epc7D") or 0),
            "raw_hash": Mapper.compute_hash(raw),
            "name": link_name,
            "raw_data": raw,

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

            # Weight and auto settings
            "weight": 2,
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
        """Build HTML banner code.

        Args:
            tracking_url: The affiliate tracking URL.
            image_url: The banner image URL.

        Returns:
            HTML string for the banner.
        """
        return f'<a href="{tracking_url}" rel="sponsored"><img src="{image_url}" /></a>'
