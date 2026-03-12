<?php

namespace App\Livewire;

use App\Models\Ad;
use App\Models\Advertiser;
use App\Models\GeoRegion;
use App\Models\Placement;
use App\Models\Site;
use App\Services\GeoService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdGrid extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $network = '';

    #[Url(as: 'creative_type')]
    public string $creativeType = '';

    #[Url(as: 'approval_status')]
    public string $approvalStatus = '';

    #[Url(as: 'advertiser_id')]
    public string $advertiserId = '';

    #[Url]
    public string $dimensions = '';

    #[Url(as: 'advertiser_status')]
    public string $advertiserStatus = 'allowed';

    #[Url]
    public string $country = '';

    #[Url]
    public string $region = '';

    #[Url(as: 'has_image')]
    public string $hasImage = '1';

    #[Url(as: 'needs_attention')]
    public string $needsAttention = '1';

    #[Url(as: 'placement_sizes')]
    public string $placementSizesOnly = '1';

    #[Url]
    public string $site = '';

    #[Url(as: 'sort')]
    public string $sortField = 'last_synced_at';

    #[Url(as: 'dir')]
    public string $sortDir = 'desc';

    #[Url(as: 'per_page')]
    public int $perPage = 24;

    #[Url(as: 'view_size')]
    public string $viewSize = 'large';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedNetwork(): void
    {
        $this->resetPage();
    }

    public function updatedCreativeType(): void
    {
        $this->resetPage();
    }

    public function updatedApprovalStatus(): void
    {
        $this->resetPage();
    }

    public function updatedAdvertiserId(): void
    {
        $this->resetPage();
    }

    public function updatedDimensions(): void
    {
        $this->resetPage();
    }

    public function updatedAdvertiserStatus(): void
    {
        $this->resetPage();
    }

    public function updatedCountry(): void
    {
        $this->resetPage();
    }

    public function updatedRegion(): void
    {
        $this->resetPage();
    }

    public function updatedHasImage(): void
    {
        $this->resetPage();
    }

    public function updatedNeedsAttention(): void
    {
        $this->resetPage();
    }

    public function updatedPlacementSizesOnly(): void
    {
        $this->resetPage();
    }

    public function updatedSite(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'desc';
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'network', 'creativeType', 'approvalStatus',
            'advertiserId', 'dimensions', 'advertiserStatus',
            'country', 'region', 'site',
            'hasImage', 'needsAttention', 'placementSizesOnly',
        ]);
        $this->hasImage = '1';
        $this->needsAttention = '1';
        $this->placementSizesOnly = '1';
        $this->advertiserStatus = 'allowed';
        $this->resetPage();
    }

    public function markReviewed(): void
    {
        auth()->user()->update(['last_ad_review_at' => now()]);
    }

    public function render()
    {
        $user = auth()->user();

        // Query active placement sizes for filtering
        $activeSizes = Placement::where('is_active', true)
            ->select('width', 'height')
            ->distinct()
            ->get()
            ->map(fn ($p) => [$p->width, $p->height]);
        $activeSizeStrings = $activeSizes->map(fn ($s) => $s[0] . 'x' . $s[1]);

        $query = $this->buildQuery($user, $activeSizes);

        $perPage = in_array($this->perPage, [24, 48, 96]) ? $this->perPage : 24;
        $ads = $query->paginate($perPage);

        // When "Active sizes only" is checked, show only active placement sizes in dropdown
        $dimensionsList = $this->placementSizesOnly === '1' && $activeSizeStrings->isNotEmpty()
            ? $activeSizeStrings->sort()->values()
            : $this->getCachedDimensions();
        $advertisers = $this->getCachedAdvertisers();
        $sites = Site::where('is_active', 1)->orderBy('name')->get(['id', 'name', 'domain']);

        // Needs attention count
        $needsAttentionQuery = Ad::query();
        if ($user->last_ad_review_at) {
            $needsAttentionQuery->where('last_synced_at', '>', $user->last_ad_review_at);
        }
        $needsAttentionCount = $needsAttentionQuery->count();

        $totalMatching = $ads->total();

        // Pre-build ad data for Alpine.js
        $adsJson = $ads->getCollection()->mapWithKeys(fn ($ad) => [
            $ad->id => [
                'id' => $ad->id,
                'advert_name' => $ad->advert_name,
                'network' => $ad->network,
                'creative_type' => $ad->creative_type,
                'image_url' => $ad->image_url,
                'html_snippet' => $ad->html_snippet ?? $ad->bannercode ?? '',
                'tracking_url' => $ad->tracking_url,
                'destination_url' => $ad->destination_url,
                'width' => $ad->width,
                'height' => $ad->height,
                'epc' => $ad->epc,
                'clicks' => $ad->clicks ?? 0,
                'revenue' => $ad->revenue ?? 0,
                'approval_status' => $ad->approval_status ?? 'approved',
                'approval_reason' => $ad->approval_reason,
                'weight_override' => $ad->weight_override,
                'advertiser_name' => $ad->advertiser?->name ?? 'Unknown',
                'advertiser_weight' => $ad->advertiser?->default_weight,
                'advertiser_description' => $ad->advertiser?->description,
                'advertiser_logo_url' => $ad->advertiser?->logo_url,
                'advertiser_network_rank' => $ad->advertiser?->network_rank,
                'advertiser_website_url' => $ad->advertiser?->website_url,
                'advertiser_category' => $ad->advertiser?->category,
                'geo_region' => GeoService::getRegionName($ad->advertiser?->country_code),
                'schedule_start' => $ad->schedule_start,
                'schedule_end' => $ad->schedule_end,
                'last_synced_at' => $ad->last_synced_at,
            ]
        ]);
        $pageIds = $ads->getCollection()->pluck('id');

        $this->dispatch('ads-updated', adsJson: $adsJson, pageIds: $pageIds);

        // Check if any non-default filters are active
        // Country codes and geo regions for filter dropdowns
        $countryList = $this->getCachedCountries();
        $geoRegions = GeoRegion::orderBy('priority')->get();

        $hasActiveFilters = $this->search !== ''
            || $this->network !== ''
            || $this->creativeType !== ''
            || $this->approvalStatus !== ''
            || $this->advertiserId !== ''
            || $this->dimensions !== ''
            || $this->advertiserStatus !== 'allowed'
            || $this->country !== ''
            || $this->region !== ''
            || $this->site !== '';

        return view('livewire.ad-grid', compact(
            'ads',
            'dimensionsList',
            'advertisers',
            'countryList',
            'geoRegions',
            'sites',
            'needsAttentionCount',
            'totalMatching',
            'adsJson',
            'pageIds',
            'hasActiveFilters',
            'activeSizeStrings',
        ));
    }

    private function buildQuery($user, $activeSizes = null)
    {
        $query = Ad::query()->with('advertiser');

        // Base filter: hide ads from advertisers explicitly denied on all sites
        // (has at least one 'denied' rule AND zero 'allowed' rules)
        // Advertisers still pending (all 'default') are NOT hidden.
        if ($this->advertiserStatus !== 'denied_all') {
            $query->where(function ($q) {
                // Show ads where advertiser has NO denied rules at all (pending/default)
                $q->whereDoesntHave('advertiser.siteRules', fn ($sq) => $sq->where('rule', 'denied'))
                  // OR has at least one allowed rule
                  ->orWhereHas('advertiser.siteRules', fn ($sq) => $sq->where('rule', 'allowed'));
            });
        }

        if ($this->search !== '') {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('advert_name', 'like', "%{$search}%")
                  ->orWhereHas('advertiser', fn ($aq) => $aq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($this->network !== '') {
            $query->where('network', $this->network);
        }

        if ($this->creativeType !== '') {
            $query->where('creative_type', $this->creativeType);
        }

        if ($this->approvalStatus !== '') {
            $query->where('approval_status', $this->approvalStatus);
        }

        if ($this->advertiserId !== '') {
            $query->where('advertiser_id', (int) $this->advertiserId);
        }

        if ($this->dimensions !== '') {
            $parts = explode('x', $this->dimensions);
            if (count($parts) === 2) {
                $query->where('width', (int) $parts[0])->where('height', (int) $parts[1]);
            }
        }

        // Improved advertiser status filter
        if ($this->advertiserStatus !== '') {
            if ($this->advertiserStatus === 'allowed') {
                $query->whereHas('advertiser.siteRules', fn ($q) => $q->where('rule', 'allowed'));
            } elseif ($this->advertiserStatus === 'pending') {
                // Advertisers with no 'allowed' or 'denied' rule — only 'default' or no rules at all
                $query->whereDoesntHave('advertiser.siteRules', fn ($q) => $q->whereIn('rule', ['allowed', 'denied']));
            } elseif ($this->advertiserStatus === 'denied_all') {
                // Override base filter: show only advertisers denied on all sites (no allowed rules)
                $query->whereDoesntHave('advertiser.siteRules', fn ($q) => $q->where('rule', 'allowed'))
                      ->whereHas('advertiser.siteRules', fn ($q) => $q->where('rule', 'denied'));
            }
        }

        if ($this->country !== '') {
            $query->whereHas('advertiser', fn ($q) => $q->where('country_code', $this->country));
        }

        if ($this->region !== '') {
            $regionRow = GeoRegion::where('name', $this->region)->first();
            if ($regionRow) {
                $codes = array_map('trim', explode(',', $regionRow->country_codes));
                $query->whereHas('advertiser', fn ($q) => $q->whereIn('country_code', $codes));
            }
        }

        // Filter by site: show only ads from advertisers allowed on the selected site
        if ($this->site !== '') {
            $siteId = (int) $this->site;
            $query->whereHas('advertiser.siteRules', fn ($q) => $q->where('site_id', $siteId)->where('rule', 'allowed'));
        }

        if ($this->hasImage === '1') {
            $query->whereNotNull('image_url')->where('image_url', '!=', '');
        }

        if ($this->needsAttention === '1' && $user->last_ad_review_at) {
            $query->where('last_synced_at', '>', $user->last_ad_review_at);
        }

        // Filter by active placement sizes
        if ($this->placementSizesOnly === '1' && $activeSizes !== null && $activeSizes->isNotEmpty()) {
            $query->where(function ($q) use ($activeSizes) {
                foreach ($activeSizes as [$w, $h]) {
                    $q->orWhere(fn ($sub) => $sub->where('width', $w)->where('height', $h));
                }
            });
        }

        $sortable = ['advert_name', 'network', 'width', 'epc', 'approval_status', 'last_synced_at'];
        $sort = in_array($this->sortField, $sortable) ? $this->sortField : 'last_synced_at';
        $dir = $this->sortDir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        return $query;
    }

    private function getCachedDimensions()
    {
        return Cache::remember('ad_dimensions', 3600, function () {
            return Ad::selectRaw('DISTINCT width, height')
                ->whereNotNull('width')
                ->whereNotNull('height')
                ->where('width', '>', 0)
                ->where('height', '>', 0)
                ->orderBy('width')
                ->orderBy('height')
                ->get()
                ->map(fn ($d) => $d->width . 'x' . $d->height);
        });
    }

    private function getCachedCountries()
    {
        return Cache::remember('advertiser_countries', 3600, function () {
            return Advertiser::whereNotNull('country_code')
                ->where('country_code', '!=', '')
                ->distinct()
                ->orderBy('country_code')
                ->pluck('country_code');
        });
    }

    private function getCachedAdvertisers()
    {
        return Cache::remember('advertiser_list', 3600, function () {
            return Advertiser::select('id', 'name', 'network')
                ->orderBy('name')
                ->get();
        });
    }
}
