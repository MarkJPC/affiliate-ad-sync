# Ad Sync Strategy

## How Often Do We Pull Data?

The sync service runs **every 6 hours** via GitHub Actions:
- 12:00 AM, 6:00 AM, 12:00 PM, 6:00 PM (UTC)

This schedule balances freshness with API rate limits.

## What Happens During a Sync?

1. **Fetch** - We pull all advertisers and ads from each network (FlexOffers, Awin, CJ, Impact)
2. **Compare** - Each ad has a unique "fingerprint" (hash). We compare it to what's already in the database
3. **Update only changes** - If the fingerprint matches, we skip it. If it's different, we update the record

This means:
- New ads are added automatically
- Changed ads (new images, prices, etc.) are updated
- Unchanged ads are left alone (no unnecessary database writes)

## Full Sync vs Incremental

We use a **full sync** approach:
- Every run fetches ALL advertisers and ads from each network
- The hash comparison ensures we only write what's actually changed

Why not incremental?
- Most affiliate APIs don't support "give me only changes since X"
- Full sync is simpler and guarantees we never miss updates
- Hash comparison keeps it efficient

## What If Something Fails?

**Individual failures don't stop the sync:**
- If one advertiser fails, we log the error and continue with the next
- If one ad fails, we log it and continue
- At the end, we record how many succeeded vs failed

**Full sync failures:**
- If the entire sync fails (network down, database error), we log it
- The next scheduled run will try again
- No data is lost - we just retry later

## API Rate Limits

| Network | Rate Limit | Our Usage |
|---------|------------|-----------|
| FlexOffers | ~100 requests/min | Well under limit |
| Awin | 20 requests/min | Paginated carefully |
| CJ | 25 requests/5 sec | Built-in delays |
| Impact | 2,000/day | Comfortable margin |

## Monitoring

Check the `sync_logs` table to see:
- When each sync ran
- How many advertisers/ads were processed
- How many were new vs updated
- Any errors that occurred

## Questions?

Contact Mark (markjpcena@gmail.com) for technical details.
