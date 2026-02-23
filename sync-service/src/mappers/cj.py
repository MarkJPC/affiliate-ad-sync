"""CJ (Commission Junction) API response mapper - Rag's responsibility."""

import re

from .base import Mapper


class CJMapper(Mapper):
    """Map CJ API responses to canonical schema.

    CJ API returns XML which the client parses into flat dicts.
    Field names use hyphens (e.g., 'advertiser-id') and nested elements
    are flattened with '/' separators (e.g., 'primary-category/child').
    """

    @property
    def network_name(self) -> str:
        return "cj"

    def _parse_epc(self, value: str) -> float:
        """Safely parse CJ EPC values which can be 'N/A' or numeric strings.

        Args:
            value: Raw EPC string from CJ API.

        Returns:
            Float EPC value, or 0.0 if unparseable.
        """
        if not value or value == "N/A":
            return 0.0
        try:
            return float(value)
        except (ValueError, TypeError):
            return 0.0

    def map_advertiser(self, raw: dict) -> dict:
        """Map CJ advertiser XML dict to canonical schema.

        Args:
            raw: Parsed XML dict from CJClient (keys use hyphens).

        Returns:
            Dict with canonical advertiser fields.
        """
        account_status = raw.get("account-status", "")
        is_active = account_status.lower() == "active"

        return {
            "network": "cj",
            "network_program_id": str(raw.get("advertiser-id", "")),
            "network_program_name": raw.get("advertiser-name", ""),
            "status": "active" if is_active else "paused",
            "website_url": raw.get("program-url", ""),
            "category": raw.get("primary-category/child", ""),
            "epc": self._parse_epc(raw.get("seven-day-epc", "")),
            "raw_hash": Mapper.compute_hash(raw),
        }

    def map_ad(self, raw: dict, advertiser_id: int) -> dict:
        """Map CJ link XML dict to canonical ad schema.

        Args:
            raw: Parsed XML dict from CJClient (keys use hyphens,
                 except 'clickUrl' which is camelCase).
            advertiser_id: Database ID of the parent advertiser.

        Returns:
            Dict with canonical ad fields matching the ads table schema.
        """
        link_type = raw.get("link-type", "").lower()

        # Determine creative_type
        if "banner" in link_type:
            creative_type = "banner"
        elif "text" in link_type:
            creative_type = "text"
        else:
            creative_type = "html"

        # Dimensions: 0 for text links
        width = int(raw.get("creative-width") or 0)
        height = int(raw.get("creative-height") or 0)

        # Extract link info — note clickUrl is camelCase, not hyphenated
        link_id = str(raw.get("link-id", ""))
        link_name = raw.get("link-name", "")
        tracking_url = raw.get("clickUrl", "")
        destination_url = raw.get("destination", "")

        # Build advert_name: {width}X{height}-{advertiser_id}-{sanitized_name}-{link_id}-General
        sanitized_name = self._sanitize_name(link_name)
        advert_name = f"{width}X{height}-{advertiser_id}-{sanitized_name}-{link_id}-General"

        # Build bannercode: use CJ's link-code-html if available, else construct
        html_code = raw.get("link-code-html", "").strip()
        if html_code:
            bannercode = html_code
        else:
            image_url = raw.get("image-url", "")
            bannercode = self._construct_bannercode(tracking_url, image_url)

        return {
            # Internal fields
            "network": "cj",
            "network_link_id": link_id,
            "network_program_id": str(advertiser_id),
            "advertiser_id": advertiser_id,
            "creative_type": creative_type,
            "tracking_url": tracking_url,
            "destination_url": destination_url,
            "status": "active",
            "epc": self._parse_epc(raw.get("seven-day-epc", "")),
            "raw_hash": Mapper.compute_hash(raw),
            "name": link_name,
            "raw_data": raw,

            # AdRotate fields
            "advert_name": advert_name,
            "bannercode": bannercode,
            "imagetype": "",
            "image_url": raw.get("image-url", ""),
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
        """Build HTML banner code as fallback when link-code-html is empty.

        Args:
            tracking_url: The affiliate tracking/click URL.
            image_url: The banner image URL.

        Returns:
            HTML string for the banner.
        """
        return f'<a href="{tracking_url}" rel="sponsored"><img src="{image_url}" /></a>'
