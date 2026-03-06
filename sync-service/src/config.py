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

    # CJ (Rag) - dict mapping domain -> website_id
    cj_api_token: str | None = None
    cj_cid: str | None = None
    cj_domain_website_ids: dict[str, str] = field(default_factory=dict)

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

    # Parse CJ_DOMAIN_WEBSITE_IDS env var
    # Format: "domain1:website_id1,domain2:website_id2"
    cj_website_ids: dict[str, str] = {}
    raw_cj = os.getenv("CJ_DOMAIN_WEBSITE_IDS", "")
    if raw_cj:
        for pair in raw_cj.split(","):
            if ":" in pair:
                domain, wid = pair.strip().split(":", 1)
                cj_website_ids[domain.strip()] = wid.strip()

    return Config(
        flexoffers_domain_keys=flexoffers_keys,
        awin_api_token=os.getenv("AWIN_API_TOKEN"),
        awin_publisher_id=os.getenv("AWIN_PUBLISHER_ID"),
        cj_api_token=os.getenv("CJ_API_TOKEN"),
        cj_cid=os.getenv("CJ_CID"),
        cj_domain_website_ids=cj_website_ids,
        impact_account_sid=os.getenv("IMPACT_ACCOUNT_SID"),
        impact_auth_token=os.getenv("IMPACT_AUTH_TOKEN"),
    )
