"""
Awin API response mapper - Nadia's responsibility.

Maps two types of raw dicts to canonical ad schema:
- Promotions (from POST /publisher/{publisherId}/promotions) — vouchers/text offers
- Creatives (from GET /publishers/{publisherId}/advertisers/{advertiserId}/creatives) — banners
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
        Map Awin raw dict to canonical ads schema.

        Dispatches to _map_creative() or _map_promotion() based on the source.
        """
        if raw.get("_source") == "creatives" or (
            raw.get("imageUrl") and not raw.get("promotionId")
        ):
            return self._map_creative(raw, advertiser_id)
        return self._map_promotion(raw, advertiser_id)

    def _map_promotion(self, raw: dict, advertiser_id: int) -> dict:
        """
        Map Awin promotion/voucher object to canonical ads schema.

        Raw fields from POST /publisher/{publisherId}/promotions:
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
        status = "active" if status_raw not in {"expired", "inactive"} else "paused"

        return self._build_ad_dict(
            network_link_id=promo_id,
            advertiser_id=advertiser_id,
            creative_type=creative_type,
            tracking_url=tracking_url,
            destination_url=destination_url,
            status=status,
            name=name,
            advert_name=advert_name,
            bannercode=bannercode,
            image_url=image_url,
            width=width,
            height=height,
            raw=raw,
        )

    def _map_creative(self, raw: dict, advertiser_id: int) -> dict:
        """
        Map Awin creative (banner) object to canonical ads schema.

        Raw fields from GET /publishers/{publisherId}/advertisers/{advertiserId}/creatives:
        id, name, imageUrl, clickThroughUrl, width, height, code
        """
        creative_id = str(raw.get("id") or "")
        name = raw.get("name") or ""
        image_url = raw.get("imageUrl") or ""
        tracking_url = raw.get("clickThroughUrl") or ""
        width = int(raw.get("width") or 0)
        height = int(raw.get("height") or 0)
        code = raw.get("code") or ""

        # Determine creative_type
        creative_type = "banner" if (width > 0 and height > 0) else "html"

        # Build advert_name
        sanitized_name = self._sanitize_name(str(name))
        advert_name = f"{width}X{height}-{advertiser_id}-{sanitized_name}-{creative_id}-General"

        # Use provided HTML code, or construct fallback bannercode
        if code.strip():
            bannercode = code
        else:
            bannercode = self._construct_bannercode(tracking_url, image_url)

        return self._build_ad_dict(
            network_link_id=creative_id,
            advertiser_id=advertiser_id,
            creative_type=creative_type,
            tracking_url=tracking_url,
            destination_url="",
            status="active",
            name=name,
            advert_name=advert_name,
            bannercode=bannercode,
            image_url=image_url,
            width=width,
            height=height,
            raw=raw,
        )

    def _build_ad_dict(
        self,
        *,
        network_link_id: str,
        advertiser_id: int,
        creative_type: str,
        tracking_url: str,
        destination_url: str,
        status: str,
        name: str,
        advert_name: str,
        bannercode: str,
        image_url: str,
        width: int,
        height: int,
        raw: dict,
    ) -> dict:
        """Build the canonical ad dict with all AdRotate fields."""
        return {
            # Internal fields
            "network": "awin",
            "network_link_id": network_link_id,
            "network_program_id": str(advertiser_id),
            "advertiser_id": advertiser_id,
            "creative_type": creative_type,
            "tracking_url": tracking_url,
            "destination_url": destination_url,
            "status": status,
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

    def _construct_bannercode(self, tracking_url: str, image_url: str) -> str:
        """Construct fallback bannercode HTML for banner creatives."""
        return f'<a href="{tracking_url}" rel="sponsored"><img src="{image_url}" /></a>'

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
