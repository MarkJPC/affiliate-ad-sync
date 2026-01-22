"""Main entry point for the sync service."""

import logging
import sys

from .config import load_config
from .db import get_connection, test_connection
from .networks.awin import AwinClient
from .networks.cj import CJClient
from .networks.flexoffers import FlexOffersClient
from .networks.impact import ImpactClient

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
)
logger = logging.getLogger(__name__)


def main() -> int:
    """Run the sync service."""
    logger.info("Starting affiliate ad sync")

    # Test database connection on startup (exits if fails)
    test_connection()

    try:
        config = load_config()
    except ValueError as e:
        logger.error(f"Configuration error: {e}")
        return 1

    # Initialize network clients based on available credentials
    clients = []

    if config.flexoffers_api_key:
        clients.append(FlexOffersClient(config.flexoffers_api_key))
        logger.info("FlexOffers client initialized")

    if config.awin_api_token and config.awin_publisher_id:
        clients.append(AwinClient(config.awin_api_token, config.awin_publisher_id))
        logger.info("Awin client initialized")

    if config.cj_api_token and config.cj_website_id:
        clients.append(CJClient(config.cj_api_token, config.cj_website_id))
        logger.info("CJ client initialized")

    if config.impact_account_sid and config.impact_auth_token:
        clients.append(ImpactClient(config.impact_account_sid, config.impact_auth_token))
        logger.info("Impact client initialized")

    if not clients:
        logger.warning("No network clients configured - check API credentials")
        return 0

    # Sync each network
    with get_connection() as conn:
        for client in clients:
            try:
                logger.info(f"Syncing {client.network_name}...")
                client.sync(conn)
                logger.info(f"Completed {client.network_name} sync")
            except Exception as e:
                logger.error(f"Error syncing {client.network_name}: {e}")

    logger.info("Sync complete")
    return 0


if __name__ == "__main__":
    sys.exit(main())
