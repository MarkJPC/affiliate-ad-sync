"""
Awin API response mapper - Nadia's responsibility.
"""

import re

from .base import Mapper


class AwinMapper(Mapper):
    """Map Awin API responses to canonical schema."""

    @property
    def network_name(self) -> str:
        return "awin"

    def map_advertiser(self, raw: dict) -> dict:
        """
        Map Awin programme object to canonical advertiser schema.

        Expected raw fields from:
        GET /publishers/{publisherId}/programmes
        """
        # Normalize programme status to match other mappers ("active"/"paused")
        status_raw = str(raw.get("status") or raw.get("linkStatus") or raw.get("relationship") or "").lower()
        status = "active" if status_raw in {"joined", "active", "approved"} else "paused"

        return {
            "network": "awin",
            "network_program_id": str(raw.get("id", "")),
            "network_program_name": raw.get("name", "") or "",
            "status": status,
            # Best-effort fields (safe defaults if missing)
            "website_url": raw.get("displayUrl") or raw.get("programmeUrl") or raw.get("url") or "",
            "category": raw.get("primarySector") or raw.get("sector") or "",
            # EPC usually not present in programmes response; keep consistent type
            "epc": float(raw.get("epc") or raw.get("sevenDayEpc") or 0),
            "raw_hash": Mapper.compute_hash(raw),
        }

    def map_ad(self, raw: dict, advertiser_id: int) -> dict:
        """
        Map Awin promotion/voucher object to canonical ads schema used by AdRotate.

        Observed raw fields from:
        POST /publisher/{publisherId}/promotions

        Example keys:
        promotionId, type, advertiser, title, description, terms, startDate, endDate,
        status, url, urlTracking, dateAdded, campaign, regions, categories, voucher
        """
        promo_id = str(raw.get("promotionId") or raw.get("id") or "")
        promo_type = str(raw.get("type") or "").lower()

        # Determine creative_type (Awin promotions endpoint often yields vouchers/promotions)
        if promo_type in {"voucher", "coupon"}:
            creative_type = "text"
        elif promo_type == "promotion":
            creative_type = "html"
        else:
            creative_type = "html"

        name = raw.get("title") or raw.get("description") or raw.get("terms") or ""
        tracking_url = raw.get("urlTracking") or ""
        destination_url = raw.get("url") or ""

        # Promotions endpoint generally doesn't include banner assets/dimensions
        width = 0
        height = 0
        image_url = ""

        # Build advert_name: {width}X{height}-{advertiser_id}-{sanitized_name}-{promo_id}-General
        sanitized_name = self._sanitize_name(str(name))
        advert_name = f"{width}X{height}-{advertiser_id}-{sanitized_name}-{promo_id}-General"

        # Include voucher code in link text if present
        voucher_code = ""
        voucher = raw.get("voucher") or {}
        if isinstance(voucher, dict):
            voucher_code = voucher.get("code") or ""

        link_text = (name or "").strip() or "View offer"
        if voucher_code:
            link_text = f"{link_text} (Code: {voucher_code})"

        bannercode = f'<a href="{tracking_url}" rel="sponsored">{self._escape_html(link_text)}</a>'

        # Normalize status to match other mappers
        status_raw = str(raw.get("status") or "").lower()
        # Treat non-expired statuses as active; be conservative if needed
        status = "active" if status_raw not in {"expired", "inactive"} else "paused"

        return {
            # Internal fields
            "network": "awin",
            "network_link_id": promo_id,
            "network_program_id": str(advertiser_id),
            "advertiser_id": advertiser_id,
            "creative_type": creative_type,
            "tracking_url": tracking_url,
            "destination_url": destination_url,
            "status": status,
            # EPC comes from separate reporting endpoints (not promotions response)
            "epc": 0.0,
            "raw_hash": Mapper.compute_hash(raw),
            "name": name,
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
        """Remove special characters and spaces for advert_name."""
        return re.sub(r"[^a-zA-Z0-9]", "", name or "")

    def _escape_html(self, text: str) -> str:
        """Minimal escaping for safe anchor text."""
        return (
            (text or "")
            .replace("&", "&amp;")
            .replace("<", "&lt;")
            .replace(">", "&gt;")
            .replace('"', "&quot;")
            .replace("'", "&#39;")
        )