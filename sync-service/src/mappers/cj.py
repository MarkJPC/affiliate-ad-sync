"""CJ (Commission Junction) API response mapper - Rag's responsibility."""

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
        # TODO: Implement in Commit 2
        raise NotImplementedError
