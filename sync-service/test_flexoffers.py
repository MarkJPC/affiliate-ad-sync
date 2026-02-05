"""Test script for FlexOffers API client."""

import json
import logging

from src.networks.flexoffers import FlexOffersClient
from src.config import load_config

# Enable debug logging to see API request details
logging.basicConfig(level=logging.DEBUG, format='%(name)s - %(levelname)s - %(message)s')

config = load_config()

if not config.flexoffers_domain_keys:
    print("No FlexOffers domain keys configured.")
    print("Set FLEXOFFERS_DOMAIN_KEYS in .env file.")
    print("Format: domain1:key1,domain2:key2")
    exit(1)

for domain, api_key in config.flexoffers_domain_keys.items():
    print(f"\n{'='*60}")
    print(f"Testing {domain}...")
    print('='*60)

    client = FlexOffersClient(api_key, domain=domain)
    advertisers = client.fetch_advertisers()

    print(f"\nFound {len(advertisers)} advertisers")
    if advertisers:
        print(f"\nFirst advertiser (raw):")
        print(json.dumps(advertisers[0], indent=2, default=str))
