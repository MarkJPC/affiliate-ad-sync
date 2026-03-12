<?php

namespace App\Livewire;

use App\Models\Advertiser;
use App\Models\GeoRegion;
use App\Models\Site;
use App\Services\GeoService;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdvertiserGrid extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $network = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $weight = '';

    #[Url]
    public string $rule = '';

    #[Url(as: 'rule_site')]
    public string $ruleSite = '';

    #[Url]
    public string $country = '';

    #[Url]
    public string $region = '';

    #[Url]
    public string $active = '';

    #[Url(as: 'epc_min')]
    public string $epcMin = '';

    #[Url(as: 'epc_max')]
    public string $epcMax = '';

    #[Url(as: 'duplicates_only')]
    public string $duplicatesOnly = '';

    #[Url(as: 'per_page')]
    public int $perPage = 25;

    #[Url(as: 'sort')]
    public string $sortField = 'name';

    #[Url(as: 'dir')]
    public string $sortDir = 'asc';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedNetwork(): void { $this->resetPage(); }
    public function updatedCategory(): void { $this->resetPage(); }
    public function updatedWeight(): void { $this->resetPage(); }
    public function updatedRule(): void { $this->resetPage(); }
    public function updatedRuleSite(): void { $this->resetPage(); }
    public function updatedCountry(): void { $this->resetPage(); }
    public function updatedRegion(): void { $this->resetPage(); }
    public function updatedActive(): void { $this->resetPage(); }
    public function updatedEpcMin(): void { $this->resetPage(); }
    public function updatedEpcMax(): void { $this->resetPage(); }
    public function updatedDuplicatesOnly(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'network', 'category', 'weight', 'rule', 'ruleSite',
            'country', 'region', 'active', 'epcMin', 'epcMax', 'duplicatesOnly',
        ]);
        $this->resetPage();
    }

    public function render()
    {
        $sites = Site::where('is_active', true)->orderBy('name')->get();
        $siteIds = $sites->pluck('id');

        $query = Advertiser::query()
            ->withCount('ads')
            ->with(['siteRules' => fn ($q) => $q->whereIn('site_id', $siteIds)]);

        // Filters
        if ($this->search !== '') {
            $search = $this->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($this->network !== '') {
            $query->where('network', $this->network);
        }

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        if ($this->weight !== '') {
            if ($this->weight === 'unassigned') {
                $query->whereNull('default_weight');
            } else {
                $query->where('default_weight', (int) $this->weight);
            }
        }

        if ($this->epcMin !== '') {
            $query->where('epc', '>=', (float) $this->epcMin);
        }

        if ($this->epcMax !== '') {
            $query->where('epc', '<=', (float) $this->epcMax);
        }

        if ($this->active !== '') {
            $query->where('is_active', $this->active === '1');
        }

        // Filter by rule status for a specific site
        if ($this->rule !== '') {
            if ($this->rule === 'default' && $this->ruleSite === '') {
                $query->where(function ($q) use ($siteIds) {
                    $q->whereDoesntHave('siteRules', function ($sq) use ($siteIds) {
                        $sq->whereIn('site_id', $siteIds)->whereIn('rule', ['allowed', 'denied']);
                    });
                });
            } elseif ($this->ruleSite !== '') {
                $ruleSiteId = $this->ruleSite;
                $rule = $this->rule;
                $query->whereHas('siteRules', function ($q) use ($rule, $ruleSiteId) {
                    $q->where('site_id', $ruleSiteId)->where('rule', $rule);
                });
            }
        }

        // Duplicates only filter
        if ($this->duplicatesOnly === '1') {
            $duplicateNamesList = Advertiser::select('name')
                ->selectRaw('COUNT(DISTINCT network) as net_count')
                ->groupBy('name')
                ->havingRaw('COUNT(DISTINCT network) > 1')
                ->pluck('name');
            $query->whereIn('name', $duplicateNamesList);
        }

        // Country filter
        if ($this->country !== '') {
            $query->where('country_code', $this->country);
        }

        // Region filter
        if ($this->region !== '') {
            $regionRow = GeoRegion::where('name', $this->region)->first();
            if ($regionRow) {
                $codes = array_map('trim', explode(',', $regionRow->country_codes));
                $query->whereIn('country_code', $codes);
            }
        }

        // Sorting
        $sortable = ['name', 'network', 'epc', 'commission_rate', 'default_weight', 'last_synced_at'];
        $sort = in_array($this->sortField, $sortable) ? $this->sortField : 'name';
        $dir = $this->sortDir === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $dir);

        // Pagination
        $perPage = in_array($this->perPage, [25, 50, 100]) ? $this->perPage : 25;
        $advertisers = $query->paginate($perPage);

        // Post-process: group duplicates by lowercase name
        $grouped = $advertisers->getCollection()->groupBy(fn ($a) => Str::lower($a->name));
        $duplicateNames = $grouped->filter(fn ($group) => $group->count() > 1)->keys()->all();

        // Index rules by site_id for each advertiser
        $advertisers->getCollection()->each(function ($advertiser) {
            $advertiser->rulesBySite = $advertiser->siteRules->keyBy('site_id');
        });

        // Compute region per advertiser
        $advertisers->getCollection()->each(function ($advertiser) {
            $regionInfo = GeoService::getRegionForCountryCode($advertiser->country_code);
            $advertiser->region_name = $regionInfo ? $regionInfo->name : null;
            $advertiser->region_id = $regionInfo ? $regionInfo->id : null;
        });

        // Dropdown data
        $categories = Advertiser::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $countries = Advertiser::whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->distinct()
            ->orderBy('country_code')
            ->pluck('country_code');

        $geoRegions = GeoRegion::orderBy('priority')->get();
        $regionCountryMap = $geoRegions->mapWithKeys(function ($region) {
            $firstCode = strtoupper(trim(explode(',', $region->country_codes)[0]));
            return [$region->id => $firstCode];
        });

        $regionNameMap = $geoRegions->pluck('name', 'id');

        $totalMatching = $advertisers->total();

        // Check active filters
        $hasActiveFilters = $this->search !== ''
            || $this->network !== ''
            || $this->category !== ''
            || $this->weight !== ''
            || $this->rule !== ''
            || $this->ruleSite !== ''
            || $this->country !== ''
            || $this->region !== ''
            || $this->active !== ''
            || $this->epcMin !== ''
            || $this->epcMax !== ''
            || $this->duplicatesOnly === '1';

        // Dispatch Alpine data via event
        $weights = $advertisers->getCollection()->pluck('default_weight', 'id')->map(fn ($v) => $v === null ? '' : (string) $v);
        $regions = $advertisers->getCollection()->pluck('region_id', 'id')->map(fn ($v) => $v === null ? '' : (string) $v);
        $rules = $advertisers->getCollection()->flatMap(function ($adv) use ($sites) {
            $map = [];
            foreach ($sites as $site) {
                $rule = $adv->rulesBySite->get($site->id);
                $key = $adv->id . '-' . $site->id;
                $map[$key] = $rule ? $rule->rule : null;
            }
            return $map;
        });
        $eligibility = $advertisers->getCollection()->flatMap(function ($adv) use ($sites) {
            $map = [];
            foreach ($sites as $site) {
                $key = $adv->id . '-' . $site->id;
                $map[$key] = $adv->rulesBySite->has($site->id);
            }
            return $map;
        });
        $advertiserNames = $advertisers->getCollection()->pluck('name', 'id');
        $siteNames = $sites->pluck('name', 'id');
        $pageIds = $advertisers->getCollection()->pluck('id');

        $this->dispatch('advertisers-updated',
            weights: $weights,
            regions: $regions,
            rules: $rules,
            eligibility: $eligibility,
            advertiserNames: $advertiserNames,
            siteNames: $siteNames,
            pageIds: $pageIds,
            regionCountryMap: $regionCountryMap,
            regionNameMap: $regionNameMap,
        );

        return view('livewire.advertiser-grid', compact(
            'advertisers',
            'sites',
            'categories',
            'countries',
            'geoRegions',
            'regionCountryMap',
            'duplicateNames',
            'totalMatching',
            'hasActiveFilters',
        ));
    }
}
