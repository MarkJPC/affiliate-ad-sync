"""Test script for FlexOffers API client - full sync flow demo."""

import json
import logging

from src.networks.flexoffers import FlexOffersClient
from src.mappers.flexoffers import FlexOffersMapper
from src.config import load_config

# Enable debug logging to see API request details
logging.basicConfig(level=logging.DEBUG, format='%(name)s - %(levelname)s - %(message)s')

config = load_config()

if not config.flexoffers_domain_keys:
    print("No FlexOffers domain keys configured.")
    print("Set FLEXOFFERS_DOMAIN_KEYS in .env file.")
    print("Format: domain1:key1,domain2:key2")
    exit(1)

mapper = FlexOffersMapper()

# Track totals across all domains
total_advertisers = 0
total_ads = 0
total_banners = 0  # ads with width > 0 and height > 0
total_text_links = 0  # ads with 0x0 dimensions

for domain, api_key in config.flexoffers_domain_keys.items():
    print(f"\n{'='*60}")
    print(f"Testing {domain}...")
    print('='*60)

    client = FlexOffersClient(api_key, domain=domain)
    advertisers = client.fetch_advertisers()

    print(f"\n=== ADVERTISERS (would insert to `advertisers` table) ===")
    print(f"Found {len(advertisers)} advertisers\n")

    for i, raw_adv in enumerate(advertisers, 1):
        mapped = mapper.map_advertiser(raw_adv)
        print(f"{i}. {mapped['network_program_name']}")
        print(f"   network_program_id: {mapped['network_program_id']}")
        print(f"   status: {mapped['status']}")
        print(f"   website_url: {mapped['website_url']}")
        print(f"   category: {mapped['category']}")
        print(f"   epc: {mapped['epc']}")
        print()

    total_advertisers += len(advertisers)

    # Fetch ads for first 5 advertisers (more chance of finding banners)
    print(f"\n=== ADS (would insert to `ads` table) ===")
    advertisers_to_fetch = advertisers[:5]

    if not advertisers_to_fetch:
        print("No advertisers to fetch ads for.")
        continue

    domain_ad_count = 0
    for raw_adv in advertisers_to_fetch:
        adv_id = raw_adv.get("id")
        adv_name = raw_adv.get("name", "Unknown")

        print(f"\nAdvertiser: {adv_name} ({adv_id})")

        ads = client.fetch_ads(str(adv_id))

        if not ads:
            print("  (no ads found)")
            continue

        # Print raw JSON for first 2 ads to debug field names
        print(f"\n  --- RAW JSON (first 2 ads) ---")
        for raw_ad in ads[:2]:
            print(f"  {json.dumps(raw_ad, indent=4)}")
        print(f"  --- END RAW JSON ---\n")

        for j, raw_ad in enumerate(ads, 1):
            mapped_ad = mapper.map_ad(raw_ad, adv_id)
            print(f"  {j}. {mapped_ad['advert_name']}")
            print(f"     width: {mapped_ad['width']}, height: {mapped_ad['height']}")
            print(f"     creative_type: {mapped_ad['creative_type']}")
            print(f"     tracking_url: {mapped_ad['tracking_url'][:80]}..." if len(mapped_ad['tracking_url']) > 80 else f"     tracking_url: {mapped_ad['tracking_url']}")
            print(f"     image_url: {mapped_ad['image_url'][:80]}..." if mapped_ad['image_url'] and len(mapped_ad['image_url']) > 80 else f"     image_url: {mapped_ad['image_url']}")
            print(f"     status: {mapped_ad['status']}")
            print()

            # Track banner vs text link
            if mapped_ad['width'] > 0 and mapped_ad['height'] > 0:
                total_banners += 1
            else:
                total_text_links += 1

        domain_ad_count += len(ads)

    total_ads += domain_ad_count
    print(f"\n[{domain}] Fetched {domain_ad_count} ads from {len(advertisers_to_fetch)} advertisers")

    client.close()

# Summary
print(f"\n{'='*60}")
print("SUMMARY")
print('='*60)
print(f"Total advertisers across all domains: {total_advertisers}")
print(f"Total ads fetched (from first 5 advertisers per domain): {total_ads}")
print()
print("=== DIMENSION BREAKDOWN ===")
print(f"Banners (width > 0 and height > 0): {total_banners}")
print(f"Text links (0x0 dimensions): {total_text_links}")
