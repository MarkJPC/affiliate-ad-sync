<?php

namespace App\Http\Controllers;

use App\Models\Advertiser;
use App\Models\Site;
use App\Models\SiteAdvertiserRule;
use App\Services\GeoService;
use Illuminate\Http\Request;

class AdvertiserController extends Controller
{
    public function updateRule(Request $request, Advertiser $advertiser, Site $site)
    {
        $validated = $request->validate([
            'rule' => 'required|in:allowed,denied,default',
            'reason' => 'nullable|string|max:255',
        ]);

        $siteRule = SiteAdvertiserRule::updateOrCreate(
            ['site_id' => $site->id, 'advertiser_id' => $advertiser->id],
            ['rule' => $validated['rule'], 'reason' => $validated['reason'] ?? null],
        );

        return response()->json(['success' => true, 'rule' => $siteRule]);
    }

    public function updateWeight(Request $request, Advertiser $advertiser)
    {
        $validated = $request->validate([
            'default_weight' => 'nullable|in:2,4,6,8,10',
        ]);

        $advertiser->update([
            'default_weight' => $validated['default_weight'],
        ]);

        return response()->json(['success' => true, 'weight' => $advertiser->default_weight]);
    }

    public function updateCountryCode(Request $request, Advertiser $advertiser)
    {
        $validated = $request->validate([
            'country_code' => 'nullable|string|size:2|alpha',
        ]);

        $code = $validated['country_code'] ? strtoupper($validated['country_code']) : null;
        $advertiser->update(['country_code' => $code]);

        $updatedAds = GeoService::reResolveAdvertiserAds($advertiser);
        $regionName = GeoService::getRegionName($code);

        return response()->json([
            'success' => true,
            'country_code' => $code,
            'region_name' => $regionName,
            'ads_updated' => $updatedAds,
        ]);
    }

    public function bulkRules(Request $request)
    {
        $validated = $request->validate([
            'advertiser_ids' => 'required_without:filter|array',
            'advertiser_ids.*' => 'integer|exists:advertisers,id',
            'filter' => 'nullable|array',
            'site_id' => 'required|exists:sites,id',
            'rule' => 'required|in:allowed,denied,default',
            'reason' => 'nullable|string|max:255',
        ]);

        $ids = $validated['advertiser_ids'] ?? $this->resolveFilterIds($request);

        $count = 0;
        foreach ($ids as $advertiserId) {
            SiteAdvertiserRule::updateOrCreate(
                ['site_id' => $validated['site_id'], 'advertiser_id' => $advertiserId],
                ['rule' => $validated['rule'], 'reason' => $validated['reason'] ?? null],
            );
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function bulkWeight(Request $request)
    {
        $validated = $request->validate([
            'advertiser_ids' => 'required_without:filter|array',
            'advertiser_ids.*' => 'integer|exists:advertisers,id',
            'filter' => 'nullable|array',
            'default_weight' => 'nullable|in:2,4,6,8,10',
        ]);

        $ids = $validated['advertiser_ids'] ?? $this->resolveFilterIds($request);

        $count = Advertiser::whereIn('id', $ids)->update([
            'default_weight' => $validated['default_weight'],
        ]);

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function bulkRegion(Request $request)
    {
        $validated = $request->validate([
            'advertiser_ids' => 'required_without:filter|array',
            'advertiser_ids.*' => 'integer|exists:advertisers,id',
            'filter' => 'nullable|array',
            'country_code' => 'nullable|string|size:2|alpha',
        ]);

        $ids = $validated['advertiser_ids'] ?? $this->resolveFilterIds($request);
        $code = isset($validated['country_code']) ? strtoupper($validated['country_code']) : null;

        Advertiser::whereIn('id', $ids)->update(['country_code' => $code]);

        foreach ($ids as $advertiserId) {
            $advertiser = Advertiser::find($advertiserId);
            if ($advertiser) {
                GeoService::reResolveAdvertiserAds($advertiser);
            }
        }

        return response()->json(['success' => true, 'count' => count($ids)]);
    }

    /**
     * Re-run the index query to resolve IDs when "select all matching" is used.
     */
    private function resolveFilterIds(Request $request): array
    {
        $filter = $request->input('filter', []);
        $query = Advertiser::query();

        if ($search = ($filter['search'] ?? null)) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($network = ($filter['network'] ?? null)) {
            $query->where('network', $network);
        }
        if ($category = ($filter['category'] ?? null)) {
            $query->where('category', $category);
        }
        if (isset($filter['active'])) {
            $query->where('is_active', $filter['active'] === '1');
        }

        return $query->pluck('id')->all();
    }
}
