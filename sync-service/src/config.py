"""Configuration management - loads environment variables."""

import os
from dataclasses import dataclass

from dotenv import load_dotenv

load_dotenv()


@dataclass
class Config:
    """Application configuration loaded from environment."""

    # FlexOffers (Mark)
    flexoffers_api_key: str | None = None

    # Awin (Nadia)
    awin_api_token: str | None = None
    awin_publisher_id: str | None = None

    # CJ (Rag)
    cj_api_token: str | None = None
    cj_website_id: str | None = None

    # Impact (Rag)
    impact_account_sid: str | None = None
    impact_auth_token: str | None = None


def load_config() -> Config:
    """Load configuration from environment variables."""
    return Config(
        flexoffers_api_key=os.getenv("FLEXOFFERS_API_KEY"),
        awin_api_token=os.getenv("AWIN_API_TOKEN"),
        awin_publisher_id=os.getenv("AWIN_PUBLISHER_ID"),
        cj_api_token=os.getenv("CJ_API_TOKEN"),
        cj_website_id=os.getenv("CJ_WEBSITE_ID"),
        impact_account_sid=os.getenv("IMPACT_ACCOUNT_SID"),
        impact_auth_token=os.getenv("IMPACT_AUTH_TOKEN"),
    )
