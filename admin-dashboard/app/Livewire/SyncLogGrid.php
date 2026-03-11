<?php

namespace App\Livewire;

use App\Models\SyncLog;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SyncLogGrid extends Component
{
    use WithPagination;

    #[Url]
    public string $network = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'site_domain')]
    public string $siteDomain = '';

    #[Url(as: 'date_from')]
    public string $dateFrom = '';

    #[Url(as: 'date_to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortField = 'started_at';

    #[Url(as: 'dir')]
    public string $sortDir = 'desc';

    #[Url(as: 'per_page')]
    public int $perPage = 25;

    public function updatedNetwork(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSiteDomain(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
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
        $this->reset(['network', 'status', 'siteDomain', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        // Summary stats scoped to date range
        $statsQuery = SyncLog::query()->dateRange($this->dateFrom, $this->dateTo);
        $totalCount = (clone $statsQuery)->count();
        $successCount = (clone $statsQuery)->where('status', 'success')->count();
        $failedCount = (clone $statsQuery)->where('status', 'failed')->count();

        // Last sync per network (always unfiltered — absolute latest)
        $networks = ['flexoffers', 'awin', 'cj', 'impact'];
        $lastSyncs = [];
        foreach ($networks as $net) {
            $lastSyncs[$net] = SyncLog::where('network', $net)
                ->orderByDesc('started_at')
                ->first();
        }

        // Build filtered query
        $query = SyncLog::query()->dateRange($this->dateFrom, $this->dateTo);

        if ($this->network !== '') {
            $query->where('network', $this->network);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->siteDomain !== '') {
            $query->where('site_domain', 'like', '%' . $this->siteDomain . '%');
        }

        // Sort
        $sortable = ['id', 'network', 'site_domain', 'started_at', 'completed_at', 'status', 'advertisers_synced', 'ads_synced', 'ads_deleted'];
        $sort = in_array($this->sortField, $sortable) ? $this->sortField : 'started_at';
        $dir = $this->sortDir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $perPage = in_array($this->perPage, [10, 25, 50, 100]) ? $this->perPage : 25;
        $logs = $query->paginate($perPage);

        $hasActiveFilters = $this->network !== ''
            || $this->status !== ''
            || $this->siteDomain !== ''
            || $this->dateFrom !== ''
            || $this->dateTo !== '';

        return view('livewire.sync-log-grid', compact(
            'logs',
            'totalCount',
            'successCount',
            'failedCount',
            'lastSyncs',
            'networks',
            'hasActiveFilters',
        ));
    }
}
