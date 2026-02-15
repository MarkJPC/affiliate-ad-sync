"""Configuration management - loads environment variables."""

import os
from dataclasses import dataclass, field

from dotenv import load_dotenv

load_dotenv()


@dataclass
class Config:
    """Application configuration loaded from environment."""

    # FlexOffers (Mark) - dict mapping domain -> api_key
    flexoffers_domain_keys: dict[str, str] = field(default_factory=dict)

    # Awin (Nadia)
    awin_api_token: str | None = None
    awin_publisher_id: str | None = None

    # CJ (Rag)
    cj_api_token: str | None = None
    cj_cid: str | None = None
    cj_website_id: str | None = None

    # Impact (Rag)
    impact_account_sid: str | None = None
    impact_auth_token: str | None = None


def load_config() -> Config:
    """Load configuration from environment variables."""
    # Parse FLEXOFFERS_DOMAIN_KEYS env var
    # Format: "domain1:key1,domain2:key2"
    flexoffers_keys: dict[str, str] = {}
    raw_keys = os.getenv("FLEXOFFERS_DOMAIN_KEYS", "")
    if raw_keys:
        for pair in raw_keys.split(","):
            if ":" in pair:
                domain, key = pair.strip().split(":", 1)
                flexoffers_keys[domain.strip()] = key.strip()

    return Config(
        flexoffers_domain_keys=flexoffers_keys,
        awin_api_token=os.getenv("AWIN_API_TOKEN"),
        awin_publisher_id=os.getenv("AWIN_PUBLISHER_ID"),
        cj_api_token=os.getenv("CJ_API_TOKEN"),
        cj_cid=os.getenv("CJ_CID"),
        cj_website_id=os.getenv("CJ_WEBSITE_ID"),
        impact_account_sid=os.getenv("IMPACT_ACCOUNT_SID"),
        impact_auth_token=os.getenv("IMPACT_AUTH_TOKEN"),
    )
