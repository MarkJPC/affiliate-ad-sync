#!/usr/bin/env python3
"""
Phase 0 validator for export feature readiness.

Checks local SQLite schema/data prerequisites before implementing
banner/text CSV exports.
"""

from __future__ import annotations

import argparse
import sqlite3
import sys
from dataclasses import dataclass
from pathlib import Path


DEFAULT_DB_PATH = Path(__file__).resolve().parents[1] / "database" / "affiliate_ads.sqlite"


@dataclass
class CheckResult:
    name: str
    ok: bool
    level: str  # "required" | "advisory"
    detail: str


def get_table_columns(conn: sqlite3.Connection, table: str) -> set[str]:
    cursor = conn.execute(f"PRAGMA table_info({table})")
    return {row[1] for row in cursor.fetchall()}


def get_view_columns(conn: sqlite3.Connection, view: str) -> set[str]:
    cursor = conn.execute(f"SELECT * FROM {view} LIMIT 0")
    return {col[0] for col in (cursor.description or [])}


def table_exists(conn: sqlite3.Connection, table: str) -> bool:
    row = conn.execute(
        "SELECT 1 FROM sqlite_master WHERE type='table' AND name=? LIMIT 1",
        (table,),
    ).fetchone()
    return row is not None


def view_exists(conn: sqlite3.Connection, view: str) -> bool:
    row = conn.execute(
        "SELECT 1 FROM sqlite_master WHERE type='view' AND name=? LIMIT 1",
        (view,),
    ).fetchone()
    return row is not None


def count(conn: sqlite3.Connection, query: str) -> int:
    return int(conn.execute(query).fetchone()[0])


def validate(db_path: Path) -> tuple[list[CheckResult], int]:
    results: list[CheckResult] = []
    conn = sqlite3.connect(db_path)

    required_tables = [
        "ads",
        "advertisers",
        "sites",
        "placements",
        "site_advertiser_rules",
        "export_logs",
    ]
    for table in required_tables:
        exists = table_exists(conn, table)
        results.append(
            CheckResult(
                name=f"table:{table}",
                ok=exists,
                level="required",
                detail="exists" if exists else "missing",
            )
        )

    required_view = "v_exportable_ads"
    has_view = view_exists(conn, required_view)
    results.append(
        CheckResult(
            name=f"view:{required_view}",
            ok=has_view,
            level="required",
            detail="exists" if has_view else "missing",
        )
    )

    if table_exists(conn, "ads"):
        ads_required_cols = {
            "creative_type",
            "approval_status",
            "weight_override",
            "advert_name",
            "bannercode",
            "tracking_url",
            "width",
            "height",
            "geo_countries",
        }
        ads_cols = get_table_columns(conn, "ads")
        missing = sorted(ads_required_cols - ads_cols)
        results.append(
            CheckResult(
                name="schema:ads_export_columns",
                ok=not missing,
                level="required",
                detail="ok" if not missing else f"missing columns: {', '.join(missing)}",
            )
        )

    if table_exists(conn, "placements"):
        placement_required_cols = {"site_id", "width", "height", "is_active", "adrotate_group_id"}
        placement_cols = get_table_columns(conn, "placements")
        missing = sorted(placement_required_cols - placement_cols)
        results.append(
            CheckResult(
                name="schema:placements_columns",
                ok=not missing,
                level="required",
                detail="ok" if not missing else f"missing columns: {', '.join(missing)}",
            )
        )

    if table_exists(conn, "site_advertiser_rules"):
        rules_required_cols = {"site_id", "advertiser_id", "rule"}
        rules_cols = get_table_columns(conn, "site_advertiser_rules")
        missing = sorted(rules_required_cols - rules_cols)
        results.append(
            CheckResult(
                name="schema:site_rules_columns",
                ok=not missing,
                level="required",
                detail="ok" if not missing else f"missing columns: {', '.join(missing)}",
            )
        )

    if table_exists(conn, "export_logs"):
        logs_required_cols = {"site_id", "filename", "ads_exported", "exported_at", "exported_by"}
        logs_cols = get_table_columns(conn, "export_logs")
        missing = sorted(logs_required_cols - logs_cols)
        results.append(
            CheckResult(
                name="schema:export_logs_columns",
                ok=not missing,
                level="required",
                detail="ok" if not missing else f"missing columns: {', '.join(missing)}",
            )
        )

    if has_view:
        view_required_cols = {
            "ad_id",
            "advertiser_id",
            "advertiser_name",
            "network",
            "site_id",
            "final_weight",
            "advert_name",
            "bannercode",
            "imagetype",
            "image_url",
            "width",
            "height",
            "geo_countries",
            "schedule_start",
            "schedule_end",
        }
        view_cols = get_view_columns(conn, required_view)
        missing = sorted(view_required_cols - view_cols)
        results.append(
            CheckResult(
                name="view:v_exportable_ads_columns",
                ok=not missing,
                level="required",
                detail="ok" if not missing else f"missing columns: {', '.join(missing)}",
            )
        )

    # Advisory readiness checks (useful but not schema blockers)
    if table_exists(conn, "sites"):
        site_count = count(conn, "SELECT COUNT(*) FROM sites")
        results.append(
            CheckResult(
                name="data:seed_sites",
                ok=site_count > 0,
                level="advisory",
                detail=f"{site_count} site rows",
            )
        )

    if table_exists(conn, "placements"):
        active_placements = count(conn, "SELECT COUNT(*) FROM placements WHERE is_active = 1")
        results.append(
            CheckResult(
                name="data:active_placements",
                ok=active_placements > 0,
                level="advisory",
                detail=f"{active_placements} active placements",
            )
        )

    if table_exists(conn, "site_advertiser_rules"):
        allowed_rules = count(conn, "SELECT COUNT(*) FROM site_advertiser_rules WHERE rule = 'allowed'")
        results.append(
            CheckResult(
                name="data:allowed_rules",
                ok=allowed_rules > 0,
                level="advisory",
                detail=f"{allowed_rules} allowed rules",
            )
        )

    conn.close()
    required_failures = sum(1 for result in results if result.level == "required" and not result.ok)
    return results, required_failures


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Validate Phase 0 readiness for export features (SQLite)."
    )
    parser.add_argument(
        "--db-path",
        default=str(DEFAULT_DB_PATH),
        help="Path to SQLite database file.",
    )
    args = parser.parse_args()
    db_path = Path(args.db_path)

    if not db_path.exists():
        print(f"[FAIL] database missing: {db_path}")
        return 2

    results, required_failures = validate(db_path)

    print(f"Phase 0 validation on: {db_path}")
    print("-" * 72)
    for result in results:
        status = "PASS" if result.ok else "FAIL"
        print(f"[{status}] ({result.level}) {result.name} -> {result.detail}")

    print("-" * 72)
    if required_failures:
        print(f"Gate result: BLOCKED ({required_failures} required check(s) failed)")
        return 1

    print("Gate result: READY (all required checks passed)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
