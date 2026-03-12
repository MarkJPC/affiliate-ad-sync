#!/usr/bin/env python3
"""
Awin Diagnostic Script
======================
Investigates why Awin returns only 72 ads (all 0x0) while the creatives
endpoint (banners with real dimensions) appears to return nothing.

Run:  cd sync-service && uv run python diagnose_awin.py
"""

import logging
import sys
import time

import httpx

from src.config import load_config
from src.networks.awin import AwinClient

# ── Logging ──────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.DEBUG,
    format="%(asctime)s %(levelname)-5s %(name)s: %(message)s",
    stream=sys.stdout,
)
logger = logging.getLogger("diagnose_awin")

DELAY_BETWEEN_REQUESTS = 3  # seconds


def section(title: str) -> None:
    print(f"\n{'=' * 60}")
    print(f"  {title}")
    print(f"{'=' * 60}\n")


def main() -> None:
    # ── 1. Load config ───────────────────────────────────────────────────
    section("1. Loading Awin credentials")
    cfg = load_config()

    if not cfg.awin_api_token or not cfg.awin_publisher_id:
        print("ERROR: AWIN_API_TOKEN and AWIN_PUBLISHER_ID must be set in .env")
        sys.exit(1)

    pub_id = cfg.awin_publisher_id
    token = cfg.awin_api_token
    print(f"Publisher ID : {pub_id}")
    print(f"API Token    : {token[:8]}...{token[-4:]}")

    base = AwinClient.BASE_URL
    headers = {"Authorization": f"Bearer {token}"}
    common_params = {"accessToken": token}

    # ── 2. Fetch advertisers (joined) ────────────────────────────────────
    section("2. Fetching joined advertisers via client")
    client = AwinClient(api_token=token, publisher_id=pub_id)
    try:
        advertisers = client.fetch_advertisers()
    except Exception as e:
        print(f"ERROR fetching advertisers: {e}")
        advertisers = []

    print(f"Joined advertisers: {len(advertisers)}")
    for adv in advertisers:
        adv_id = adv.get("id") or adv.get("advertiserId") or adv.get("programmeId")
        name = adv.get("name") or adv.get("displayName") or "?"
        print(f"  - {adv_id}: {name}")

    # ── 3. Compare: total programmes (without relationship=joined) ──────
    section("3. Total programmes (no relationship filter)")
    time.sleep(DELAY_BETWEEN_REQUESTS)

    url = f"{base}/publishers/{pub_id}/programmes"
    try:
        resp = httpx.get(url, headers=headers, params=common_params, timeout=30)
        print(f"Status: {resp.status_code}")
        _print_rate_headers(resp)
        if resp.status_code == 200:
            data = resp.json()
            total = len(data) if isinstance(data, list) else "unknown structure"
            print(f"Total programmes (no filter): {total}")
            if isinstance(data, list) and data:
                print(f"First entry keys: {list(data[0].keys())}")
        else:
            print(f"Body preview: {resp.text[:500]}")
    except Exception as e:
        print(f"ERROR: {e}")

    # ── 4. Test creatives per advertiser ─────────────────────────────────
    section("4. Testing creatives endpoint per advertiser")

    summary_rows: list[dict] = []

    for adv in advertisers:
        adv_id = adv.get("id") or adv.get("advertiserId") or adv.get("programmeId")
        adv_name = adv.get("name") or adv.get("displayName") or "?"

        print(f"\n--- Advertiser: {adv_name} (ID {adv_id}) ---")
        time.sleep(DELAY_BETWEEN_REQUESTS)

        row = {"name": adv_name, "id": adv_id, "promos": 0, "creatives_raw": 0,
               "creatives_client": 0, "errors": []}

        # 4a. Raw HTTP call to creatives
        creatives_url = (
            f"{base}/publishers/{pub_id}/advertisers/{adv_id}/creatives"
        )
        print(f"  GET {creatives_url}")
        try:
            resp = httpx.get(
                creatives_url,
                headers=headers,
                params={**common_params, "page": 1, "pageSize": 200},
                timeout=30,
            )
            print(f"  Status: {resp.status_code}")
            _print_rate_headers(resp, indent="  ")

            if resp.status_code == 200:
                body = resp.json()
                count = len(body) if isinstance(body, list) else "?"
                print(f"  Creatives (raw): {count}")
                if isinstance(body, list) and body:
                    print(f"  First creative keys: {list(body[0].keys())}")
                    first = body[0]
                    print(f"  Sample: id={first.get('id')}, "
                          f"size={first.get('width', '?')}x{first.get('height', '?')}, "
                          f"imageUrl={str(first.get('imageUrl', ''))[:80]}")
                row["creatives_raw"] = count if isinstance(count, int) else 0
            elif resp.status_code == 204:
                print("  204 No Content — no creatives for this advertiser")
            else:
                preview = resp.text[:500]
                print(f"  Body: {preview}")
                row["errors"].append(f"raw HTTP {resp.status_code}")
        except Exception as e:
            print(f"  ERROR (raw): {e}")
            row["errors"].append(f"raw: {e}")

        # 4b. Client method call
        time.sleep(DELAY_BETWEEN_REQUESTS)
        print(f"  Calling client.fetch_creatives({adv_id})...")
        try:
            client_creatives = client.fetch_creatives(adv_id)
            print(f"  Creatives (client): {len(client_creatives)}")
            row["creatives_client"] = len(client_creatives)
        except Exception as e:
            print(f"  ERROR (client): {e}")
            row["errors"].append(f"client: {e}")

        # 4c. Promotions count (via client fetch_ads minus creatives)
        time.sleep(DELAY_BETWEEN_REQUESTS)
        print(f"  Calling client.fetch_ads({adv_id}) for promotions count...")
        try:
            all_ads = client.fetch_ads(adv_id)
            promos = [a for a in all_ads if a.get("_source") != "creatives"]
            creatives_via_ads = [a for a in all_ads if a.get("_source") == "creatives"]
            print(f"  Promotions: {len(promos)}, Creatives (via fetch_ads): {len(creatives_via_ads)}")
            row["promos"] = len(promos)
        except Exception as e:
            print(f"  ERROR (fetch_ads): {e}")
            row["errors"].append(f"fetch_ads: {e}")

        summary_rows.append(row)

    # ── 5. Summary table ─────────────────────────────────────────────────
    section("5. Summary")

    header = f"{'Advertiser':<30} {'ID':>8} {'Promos':>7} {'Cr(raw)':>8} {'Cr(client)':>11} {'Errors'}"
    print(header)
    print("-" * len(header))
    for r in summary_rows:
        errors = "; ".join(r["errors"]) if r["errors"] else "—"
        print(
            f"{r['name'][:30]:<30} {str(r['id']):>8} "
            f"{r['promos']:>7} {r['creatives_raw']:>8} "
            f"{r['creatives_client']:>11} {errors}"
        )

    total_promos = sum(r["promos"] for r in summary_rows)
    total_cr_raw = sum(r["creatives_raw"] for r in summary_rows)
    total_cr_client = sum(r["creatives_client"] for r in summary_rows)
    print(f"\nTotals: {total_promos} promotions, {total_cr_raw} creatives (raw), "
          f"{total_cr_client} creatives (client)")

    client.close()
    print("\nDone.")


def _print_rate_headers(resp: httpx.Response, indent: str = "") -> None:
    """Print any rate-limit related headers from the response."""
    rate_headers = ["Retry-After", "X-RateLimit-Limit", "X-RateLimit-Remaining",
                    "X-RateLimit-Reset", "X-Rate-Limit-Limit", "X-Rate-Limit-Remaining"]
    found = False
    for h in rate_headers:
        val = resp.headers.get(h)
        if val:
            print(f"{indent}  {h}: {val}")
            found = True
    if not found:
        print(f"{indent}  (no rate-limit headers)")


if __name__ == "__main__":
    main()
