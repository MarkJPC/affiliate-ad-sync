<?php

namespace App\Livewire;

use App\Models\Ad;
use App\Models\Advertiser;
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
    public string $advertiserStatus = '';

    #[Url(as: 'has_image')]
    public string $hasImage = '1';

    #[Url(as: 'needs_attention')]
    public string $needsAttention = '1';

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

    public function updatedHasImage(): void
    {
        $this->resetPage();
    }

    public function updatedNeedsAttention(): void
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
            'hasImage', 'needsAttention',
        ]);
        $this->hasImage = '1';
        $this->needsAttention = '1';
        $this->resetPage();
    }

    public function markReviewed(): void
    {
        auth()->user()->update(['last_ad_review_at' => now()]);
    }

    public function render()
    {
        $user = auth()->user();
        $query = $this->buildQuery($user);

        $perPage = in_array($this->perPage, [24, 48, 96]) ? $this->perPage : 24;
        $ads = $query->paginate($perPage);

        $dimensionsList = $this->getCachedDimensions();
        $advertisers = $this->getCachedAdvertisers();

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
                'last_synced_at' => $ad->last_synced_at,
            ]
        ]);
        $pageIds = $ads->getCollection()->pluck('id');

        $this->dispatch('ads-updated', adsJson: $adsJson, pageIds: $pageIds);

        // Check if any non-default filters are active
        $hasActiveFilters = $this->search !== ''
            || $this->network !== ''
            || $this->creativeType !== ''
            || $this->approvalStatus !== ''
            || $this->advertiserId !== ''
            || $this->dimensions !== ''
            || $this->advertiserStatus !== '';

        return view('livewire.ad-grid', compact(
            'ads',
            'dimensionsList',
            'advertisers',
            'needsAttentionCount',
            'totalMatching',
            'adsJson',
            'pageIds',
            'hasActiveFilters',
        ));
    }

    private function buildQuery($user)
    {
        $query = Ad::query()->with('advertiser');

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

        if ($this->advertiserStatus !== '') {
            if ($this->advertiserStatus === 'allowed') {
                $query->whereHas('advertiser.siteRules', fn ($q) => $q->where('rule', 'allowed'));
            } elseif ($this->advertiserStatus === 'denied') {
                $query->whereHas('advertiser.siteRules', fn ($q) => $q->where('rule', 'denied'));
            }
        }

        if ($this->hasImage === '1') {
            $query->whereNotNull('image_url')->where('image_url', '!=', '');
        }

        if ($this->needsAttention === '1' && $user->last_ad_review_at) {
            $query->where('last_synced_at', '>', $user->last_ad_review_at);
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

    private function getCachedAdvertisers()
    {
        return Cache::remember('advertiser_list', 3600, function () {
            return Advertiser::select('id', 'name', 'network')
                ->orderBy('name')
                ->get();
        });
    }
}
