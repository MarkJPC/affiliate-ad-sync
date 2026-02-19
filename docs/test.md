  How to Run

  cd sync-service

  # Just mapper tests (no API keys needed)
  uv run pytest tests/test_mappers.py -v

  # Just network fetch tests (needs API keys)
  uv run pytest tests/test_networks.py -v

  # E2E sync tests WITH VERBOSE OUTPUT
  uv run pytest tests/test_sync.py -v -s

  # Single network (all test types)
  uv run pytest -v -s -k "flexoffers"
  uv run pytest -v -s -k "impact"