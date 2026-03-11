# Export Phase 0

Phase 0 is the pre-implementation gate for export work. It validates that the
database contract required by banner/text exports is in place before coding the
export feature itself.

## What Phase 0 validates

Required checks:
- Core export tables exist: `ads`, `advertisers`, `sites`, `placements`,
  `site_advertiser_rules`, `export_logs`
- Export view exists: `v_exportable_ads`
- Required schema fields exist for:
  - `ads` export behavior (`approval_status`, `weight_override`, etc.)
  - `placements` size/group mapping
  - `site_advertiser_rules` eligibility filter
  - `export_logs` audit fields
- `v_exportable_ads` includes required export columns

Advisory checks:
- Seeded sites are present
- Active placements exist
- At least one `allowed` advertiser rule exists

## Run the validator

From repo root:

- `python3 scripts/validate_export_phase0.py`

Optional custom database path:

- `python3 scripts/validate_export_phase0.py --db-path /absolute/path/to/db.sqlite`

## Exit codes

- `0`: all required checks passed (ready for Phase 1)
- `1`: one or more required checks failed (blocked)
- `2`: database file not found

## Notes

- This validator currently targets local SQLite workflows.
- Advisory failures do not block the gate, but they indicate that preview/export
  testing may look empty until seed/rules data is updated.
