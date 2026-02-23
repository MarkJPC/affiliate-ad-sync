<?php

namespace App\Http\Controllers;

use App\Models\Advertiser;
use App\Models\Site;
use App\Models\SiteAdvertiserRule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdvertiserController extends Controller
{
    public function index(Request $request)
    {
        $sites = Site::where('is_active', true)->orderBy('name')->get();
        $siteIds = $sites->pluck('id');

        $query = Advertiser::query()
            ->withCount('ads')
            ->with(['siteRules' => fn ($q) => $q->whereIn('site_id', $siteIds)]);

        // Filters
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($network = $request->input('network')) {
            $query->where('network', $network);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($request->filled('weight')) {
            $weight = $request->input('weight');
            if ($weight === 'unassigned') {
                $query->whereNull('default_weight');
            } else {
                $query->where('default_weight', (int) $weight);
            }
        }

        if ($request->filled('epc_min')) {
            $query->where('epc', '>=', (float) $request->input('epc_min'));
        }

        if ($request->filled('epc_max')) {
            $query->where('epc', '<=', (float) $request->input('epc_max'));
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->input('active') === '1');
        }

        // Filter by rule status for a specific site
        if ($rule = $request->input('rule')) {
            $ruleSiteId = $request->input('rule_site');
            if ($rule === 'default' && !$ruleSiteId) {
                // "Pending" filter: advertisers with no rule or 'default' rule on any site
                $query->where(function ($q) use ($siteIds) {
                    $q->whereDoesntHave('siteRules', function ($sq) use ($siteIds) {
                        $sq->whereIn('site_id', $siteIds)->whereIn('rule', ['allowed', 'denied']);
                    });
                });
            } elseif ($ruleSiteId) {
                $query->whereHas('siteRules', function ($q) use ($rule, $ruleSiteId) {
                    $q->where('site_id', $ruleSiteId)->where('rule', $rule);
                });
            }
        }

        // Sorting
        $sortable = ['name', 'network', 'epc', 'commission_rate', 'default_weight', 'last_synced_at'];
        $sort = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'name';
        $dir = $request->input('dir') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $dir);

        // Pagination
        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100]) ? (int) $request->input('per_page') : 25;
        $advertisers = $query->paginate($perPage)->withQueryString();

        // Post-process: group duplicates by lowercase name
        $grouped = $advertisers->getCollection()->groupBy(fn ($a) => Str::lower($a->name));
        $duplicateNames = $grouped->filter(fn ($group) => $group->count() > 1)->keys()->all();

        // Index rules by site_id for each advertiser
        $advertisers->getCollection()->each(function ($advertiser) {
            $advertiser->rulesBySite = $advertiser->siteRules->keyBy('site_id');
        });

        // Distinct categories for filter dropdown
        $categories = Advertiser::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        // Total matching count (for "select all matching" in bulk)
        $totalMatching = $advertisers->total();

        return view('advertisers.index', compact(
            'advertisers',
            'sites',
            'categories',
            'duplicateNames',
            'totalMatching',
            'sort',
            'dir',
            'perPage',
        ));
    }

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
