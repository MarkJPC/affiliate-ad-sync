"""Dump all raw API fields from each network into a CSV for review.

Fetches a small sample of advertisers + ads from each network,
collects every unique field name, and writes a spreadsheet showing:
- Network, Endpoint, Field Name, Sample Value, Currently Used (Y/N)

Usage:
    cd sync-service
    uv run python dump_raw_fields.py
"""

import csv
import json
import logging
import sys
from pathlib import Path

from src.config import load_config
from src.networks.flexoffers import FlexOffersClient
from src.networks.awin import AwinClient
from src.networks.cj import CJClient
from src.networks.impact import ImpactClient

logging.basicConfig(level=logging.WARNING)
logger = logging.getLogger(__name__)

# Fields each mapper currently reads from raw API responses
USED_FIELDS = {
    "flexoffers": {
        "advertisers": {
            "id", "name", "programStatus", "applicationStatus",
            "domainUrl", "categoryNames", "sevenDayEpc",
        },
        "ads": {
            "linkType", "bannerWidth", "bannerHeight", "linkId",
            "linkName", "linkUrl", "imageUrl", "htmlCode", "epc7D",
        },
    },
    "awin": {
        "advertisers": {
            "id", "name", "status", "linkStatus", "relationship",
            "displayUrl", "programmeUrl", "url", "primarySector",
            "sector", "epc", "sevenDayEpc",
        },
        "promotions": {
            "promotionId", "id", "type", "title", "description",
            "terms", "urlTracking", "url", "status", "voucher",
        },
        "creatives": {
            "id", "name", "imageUrl", "clickThroughUrl", "width",
            "height", "code", "_source",
        },
    },
    "cj": {
        "advertisers": {
            "advertiser-id", "advertiser-name", "account-status",
            "program-url", "primary-category/child", "seven-day-epc",
        },
        "ads": {
            "link-type", "creative-width", "creative-height", "link-id",
            "link-name", "clickUrl", "destination", "link-code-html",
            "image-url", "seven-day-epc",
        },
    },
    "impact": {
        "advertisers": {
            "CampaignId", "CampaignName", "ContractStatus", "CampaignUrl",
        },
        "ads": {
            "Id", "Name", "Type", "Width", "Height", "TrackingLink",
            "CreativeUrl", "Code", "LandingPageUrl", "CampaignId",
        },
    },
}


def truncate(value, max_len=120):
    """Truncate a value for display in the CSV."""
    s = str(value)
    if len(s) > max_len:
        return s[:max_len] + "..."
    return s


def flatten_keys(obj, prefix=""):
    """Recursively collect all keys from a dict/list structure."""
    fields = {}
    if isinstance(obj, dict):
        for k, v in obj.items():
            full_key = f"{prefix}{k}" if not prefix else f"{prefix}.{k}"
            if isinstance(v, dict):
                fields.update(flatten_keys(v, full_key))
            elif isinstance(v, list) and v and isinstance(v[0], dict):
                # Array of objects — flatten the first item
                fields.update(flatten_keys(v[0], f"{full_key}[]"))
            else:
                fields[full_key] = v
    return fields


def collect_fields(records: list[dict]) -> dict[str, str]:
    """Collect all unique fields across multiple records with sample values."""
    all_fields: dict[str, str] = {}
    for record in records:
        if isinstance(record, dict):
            flat = flatten_keys(record)
            for k, v in flat.items():
                if k not in all_fields or not all_fields[k]:
                    all_fields[k] = truncate(v)
    return all_fields


def main():
    config = load_config()
    rows = []

    # ── FlexOffers ──────────────────────────────────────────────
    if config.flexoffers_domain_keys:
        domain = next(iter(config.flexoffers_domain_keys))
        api_key = config.flexoffers_domain_keys[domain]
        print(f"[FlexOffers] Fetching sample from {domain}...")
        client = FlexOffersClient(api_key, domain=domain)
        try:
            advertisers = client.fetch_advertisers()
            print(f"  Got {len(advertisers)} advertisers")
            fields = collect_fields(advertisers[:10])
            used = USED_FIELDS["flexoffers"]["advertisers"]
            for field_name, sample in sorted(fields.items()):
                rows.append(["FlexOffers", "Advertisers", field_name, sample,
                             "Y" if field_name in used else ""])

            # Fetch ads from first advertiser
            if advertisers:
                adv_id = str(advertisers[0].get("id", ""))
                ads = client.fetch_ads(adv_id)
                print(f"  Got {len(ads)} ads from advertiser {adv_id}")
                fields = collect_fields(ads[:10])
                used = USED_FIELDS["flexoffers"]["ads"]
                for field_name, sample in sorted(fields.items()):
                    rows.append(["FlexOffers", "Ads", field_name, sample,
                                 "Y" if field_name in used else ""])
        except Exception as e:
            print(f"  ERROR: {e}")
        finally:
            client.close()
    else:
        print("[FlexOffers] No credentials configured, skipping")

    # ── Awin ────────────────────────────────────────────────────
    if config.awin_api_token and config.awin_publisher_id:
        print("[Awin] Fetching advertisers...")
        client = AwinClient(config.awin_api_token, config.awin_publisher_id)
        try:
            advertisers = client.fetch_advertisers()
            print(f"  Got {len(advertisers)} advertisers")
            fields = collect_fields(advertisers[:10])
            used = USED_FIELDS["awin"]["advertisers"]
            for field_name, sample in sorted(fields.items()):
                rows.append(["Awin", "Advertisers", field_name, sample,
                             "Y" if field_name in used else ""])

            # Fetch promotions + creatives from first advertiser
            if advertisers:
                adv_id = str(advertisers[0].get("id", ""))
                ads = client.fetch_ads(adv_id)
                # Split promotions vs creatives
                promos = [a for a in ads if a.get("_source") != "creatives"]
                creatives = [a for a in ads if a.get("_source") == "creatives"]

                if promos:
                    print(f"  Got {len(promos)} promotions from advertiser {adv_id}")
                    fields = collect_fields(promos[:10])
                    used = USED_FIELDS["awin"]["promotions"]
                    for field_name, sample in sorted(fields.items()):
                        rows.append(["Awin", "Promotions", field_name, sample,
                                     "Y" if field_name in used else ""])

                if creatives:
                    print(f"  Got {len(creatives)} creatives from advertiser {adv_id}")
                    fields = collect_fields(creatives[:10])
                    used = USED_FIELDS["awin"]["creatives"]
                    for field_name, sample in sorted(fields.items()):
                        rows.append(["Awin", "Creatives", field_name, sample,
                                     "Y" if field_name in used else ""])

                if not ads:
                    # Try a few more advertisers to find one with ads
                    for adv in advertisers[1:6]:
                        adv_id = str(adv.get("id", ""))
                        ads = client.fetch_ads(adv_id)
                        if ads:
                            promos = [a for a in ads if a.get("_source") != "creatives"]
                            creatives = [a for a in ads if a.get("_source") == "creatives"]
                            if promos:
                                print(f"  Got {len(promos)} promotions from advertiser {adv_id}")
                                fields = collect_fields(promos[:10])
                                used = USED_FIELDS["awin"]["promotions"]
                                for field_name, sample in sorted(fields.items()):
                                    rows.append(["Awin", "Promotions", field_name, sample,
                                                 "Y" if field_name in used else ""])
                            if creatives:
                                print(f"  Got {len(creatives)} creatives from advertiser {adv_id}")
                                fields = collect_fields(creatives[:10])
                                used = USED_FIELDS["awin"]["creatives"]
                                for field_name, sample in sorted(fields.items()):
                                    rows.append(["Awin", "Creatives", field_name, sample,
                                                 "Y" if field_name in used else ""])
                            break
        except Exception as e:
            print(f"  ERROR: {e}")
        finally:
            client.close()
    else:
        print("[Awin] No credentials configured, skipping")

    # ── CJ ──────────────────────────────────────────────────────
    if config.cj_api_token and config.cj_cid and config.cj_domain_website_ids:
        domain = next(iter(config.cj_domain_website_ids))
        website_id = config.cj_domain_website_ids[domain]
        print(f"[CJ] Fetching sample for {domain}...")
        client = CJClient(config.cj_api_token, config.cj_cid, website_id, domain=domain)
        try:
            advertisers = client.fetch_advertisers()
            print(f"  Got {len(advertisers)} advertisers")
            fields = collect_fields(advertisers[:10])
            used = USED_FIELDS["cj"]["advertisers"]
            for field_name, sample in sorted(fields.items()):
                rows.append(["CJ", "Advertisers", field_name, sample,
                             "Y" if field_name in used else ""])

            # Fetch ads from first advertiser
            if advertisers:
                adv_id = str(advertisers[0].get("advertiser-id", ""))
                ads = client.fetch_ads(adv_id)
                print(f"  Got {len(ads)} ads from advertiser {adv_id}")
                fields = collect_fields(ads[:10])
                used = USED_FIELDS["cj"]["ads"]
                for field_name, sample in sorted(fields.items()):
                    rows.append(["CJ", "Ads", field_name, sample,
                                 "Y" if field_name in used else ""])
        except Exception as e:
            print(f"  ERROR: {e}")
        finally:
            client.close()
    else:
        print("[CJ] No credentials configured, skipping")

    # ── Impact ──────────────────────────────────────────────────
    if config.impact_account_sid and config.impact_auth_token:
        print("[Impact] Fetching campaigns...")
        client = ImpactClient(config.impact_account_sid, config.impact_auth_token)
        try:
            advertisers = client.fetch_advertisers()
            print(f"  Got {len(advertisers)} campaigns")
            fields = collect_fields(advertisers[:10])
            used = USED_FIELDS["impact"]["advertisers"]
            for field_name, sample in sorted(fields.items()):
                rows.append(["Impact", "Advertisers", field_name, sample,
                             "Y" if field_name in used else ""])

            # Fetch ads (global endpoint)
            ads = client.fetch_ads("")
            print(f"  Got {len(ads)} ads")
            fields = collect_fields(ads[:10])
            used = USED_FIELDS["impact"]["ads"]
            for field_name, sample in sorted(fields.items()):
                rows.append(["Impact", "Ads", field_name, sample,
                             "Y" if field_name in used else ""])
        except Exception as e:
            print(f"  ERROR: {e}")
        finally:
            client.close()
    else:
        print("[Impact] No credentials configured, skipping")

    # ── Write CSV ───────────────────────────────────────────────
    output_path = Path("../docs/raw_api_fields.csv")
    output_path.parent.mkdir(parents=True, exist_ok=True)

    with open(output_path, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["Network", "Endpoint", "Field Name", "Sample Value", "Currently Used"])
        writer.writerows(rows)

    print(f"\nWrote {len(rows)} fields to {output_path}")
    print("Open in Excel/Sheets to review with Richard.")


if __name__ == "__main__":
    main()
