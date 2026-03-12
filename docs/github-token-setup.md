# GitHub Token Setup for Dashboard Sync Trigger

The admin dashboard's **"Run Sync"** button (on the Sync Logs page) calls the GitHub Actions API to dispatch the sync workflow. This requires a Personal Access Token with Actions write permission.

## Required Environment Variables

Set these in your Laravel `.env` on cPanel (**not** in version control):

```
GITHUB_TOKEN=github_pat_xxxxxxxxxxxx
GITHUB_REPO=MarkJPC/affiliate-ad-sync
```

## Step-by-Step Setup

1. Go to **GitHub → Settings → Developer settings → Personal access tokens → Fine-grained tokens**
2. Click **Generate new token**
3. Name it something descriptive, e.g. `admin-dashboard-sync-trigger`
4. **Repository access**: select "Only select repositories" → choose `affiliate-ad-sync`
5. **Permissions → Repository permissions → Actions**: set to **Read and write**
6. Set expiration to **90 days** (set a reminder to regenerate before it expires)
7. Click **Generate token** and copy the value

## Configure on cPanel

1. SSH into cPanel or use the File Manager
2. Navigate to the Laravel app root and edit `.env`
3. Add the two environment variables:
   ```
   GITHUB_TOKEN=github_pat_xxxxxxxxxxxx
   GITHUB_REPO=MarkJPC/affiliate-ad-sync
   ```
4. Save the file

> **Note:** The `.env` file lives only on the cPanel server. It is **not** deployed via GitHub Actions — edit it directly on the server.

## Testing

1. Open the admin dashboard → **Sync Logs** page
2. Click **"Run Sync"**
3. Check the repo's **Actions** tab — a new "Sync Affiliate Ads" workflow run should appear

## Token Renewal

Fine-grained tokens expire after the period you set. When your token expires:

1. Generate a new token following the steps above
2. Update `GITHUB_TOKEN` in the cPanel `.env`
3. Test by triggering a sync from the dashboard
