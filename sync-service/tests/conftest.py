"""Shared pytest fixtures for sync-service tests."""

import pytest

from src.config import load_config


@pytest.fixture
def flexoffers_client():
    """Provide FlexOffersClient for first configured domain.

    Skips test if FLEXOFFERS_DOMAIN_KEYS environment variable is not set.
    """
    from src.networks.flexoffers import FlexOffersClient

    config = load_config()
    if not config.flexoffers_domain_keys:
        pytest.skip("FLEXOFFERS_DOMAIN_KEYS not configured")

    domain, api_key = next(iter(config.flexoffers_domain_keys.items()))
    client = FlexOffersClient(api_key, domain=domain)
    yield client
    client.close()
