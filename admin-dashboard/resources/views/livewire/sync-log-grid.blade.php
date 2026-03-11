<div>
    {{-- Page header --}}
    <div class="sl-header relative mb-4 overflow-hidden rounded-lg border border-gray-200/60 bg-white px-5 py-3 dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="sl-header-texture"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <h1 class="font-display text-xl font-500 tracking-tight text-gray-900 dark:text-white">Sync Logs</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Monitor sync operations across all networks
                </p>
            </div>
            <button @click="triggerSync()" :disabled="syncing"
                class="sl-btn-primary inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                <svg x-show="!syncing" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <svg x-show="syncing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="syncing ? 'Triggering...' : 'Run Sync'"></span>
            </button>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        {{-- Total --}}
        <div class="rounded-lg border border-gray-200/60 bg-white p-4 dark:border-gray-700/40 dark:bg-gray-800/80">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Syncs</p>
                    <p class="font-mono text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($totalCount) }}</p>
                </div>
            </div>
        </div>

        {{-- Success --}}
        <div class="rounded-lg border border-green-200/60 bg-green-50/50 p-4 dark:border-green-800/40 dark:bg-green-900/10">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-green-600 dark:text-green-400">Successful</p>
                    <p class="font-mono text-2xl font-semibold tabular-nums text-green-700 dark:text-green-300">{{ number_format($successCount) }}</p>
                </div>
            </div>
        </div>

        {{-- Failed --}}
        <div class="rounded-lg border border-red-200/60 bg-red-50/50 p-4 dark:border-red-800/40 dark:bg-red-900/10">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-red-100 p-2 dark:bg-red-900/30">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-red-600 dark:text-red-400">Failed</p>
                    <p class="font-mono text-2xl font-semibold tabular-nums text-red-700 dark:text-red-300">{{ number_format($failedCount) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Network status cards --}}
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($networks as $net)
            @php $last = $lastSyncs[$net]; @endphp
            <div class="rounded-lg border border-gray-200/60 bg-white p-4 dark:border-gray-700/40 dark:bg-gray-800/80">
                <div class="mb-2 flex items-center justify-between">
                    <span class="inline-block rounded px-2 py-0.5 font-mono text-[0.65rem] font-semibold uppercase tracking-wider sl-net-{{ $net }}">
                        {{ $net }}
                    </span>
                    @if($last)
                        @if($last->status === 'success')
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[0.6rem] font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                OK
                            </span>
                        @elseif($last->status === 'running')
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[0.6rem] font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-blue-500"></span>
                                Running
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[0.6rem] font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                Failed
                            </span>
                        @endif
                    @else
                        <span class="text-[0.6rem] text-gray-400 dark:text-gray-500">No data</span>
                    @endif
                </div>
                @if($last)
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $last->started_at?->diffForHumans() ?? 'Unknown' }}
                    </p>
                    <div class="mt-1.5 flex items-center gap-3 text-[0.65rem] text-gray-500 dark:text-gray-400">
                        <span title="Advertisers synced">{{ $last->advertisers_synced ?? 0 }} adv</span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span title="Ads synced">{{ $last->ads_synced ?? 0 }} ads</span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span title="Ads deleted">{{ $last->ads_deleted ?? 0 }} del</span>
                    </div>
                @else
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Never synced</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Filter bar --}}
    <div class="mb-4 rounded-xl border border-gray-200/60 bg-white dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="flex flex-wrap items-end gap-2 px-4 py-3">
            {{-- Network --}}
            <div class="w-[120px]">
                <label class="mb-0.5 block text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Network</label>
                <select wire:model.live="network" class="sl-filter-input w-full border-gray-200 bg-gray-50 py-1.5 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">All</option>
                    @foreach(['flexoffers', 'awin', 'cj', 'impact'] as $net)
                        <option value="{{ $net }}">{{ ucfirst($net) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="w-[110px]">
                <label class="mb-0.5 block text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Status</label>
                <select wire:model.live="status" class="sl-filter-input w-full border-gray-200 bg-gray-50 py-1.5 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">All</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                    <option value="running">Running</option>
                </select>
            </div>

            {{-- Date From --}}
            <div class="w-[140px]">
                <label class="mb-0.5 block text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">From</label>
                <input type="date" wire:model.live="dateFrom"
                    class="sl-filter-input w-full border-gray-200 bg-gray-50 py-1.5 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
            </div>

            {{-- Date To --}}
            <div class="w-[140px]">
                <label class="mb-0.5 block text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">To</label>
                <input type="date" wire:model.live="dateTo"
                    class="sl-filter-input w-full border-gray-200 bg-gray-50 py-1.5 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
            </div>

            {{-- Site Domain search --}}
            <div class="min-w-[150px] flex-1">
                <label class="mb-0.5 block text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Site Domain</label>
                <input type="text" wire:model.live.debounce.300ms="siteDomain"
                    placeholder="Search domain..."
                    class="sl-filter-input w-full border-gray-200 bg-gray-50 py-1.5 pl-3 pr-2 text-gray-900 placeholder-gray-400 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-500">
            </div>

            {{-- Per page --}}
            <div class="w-[70px]">
                <label class="mb-0.5 block text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Per Page</label>
                <select wire:model.live="perPage" class="sl-filter-input w-full border-gray-200 bg-gray-50 py-1.5 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    @foreach([10, 25, 50, 100] as $pp)
                        <option value="{{ $pp }}">{{ $pp }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Reset --}}
            @if($hasActiveFilters)
                <button wire:click="clearFilters" class="mb-0.5 text-xs font-medium text-gray-500 transition-colors hover:text-cyan-600 dark:text-gray-400 dark:hover:text-cyan-400">
                    Reset
                </button>
            @endif
        </div>
    </div>

    {{-- Data table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200/60 bg-white dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-800/50">
                        @php
                            $columns = [
                                'id' => 'ID',
                                'network' => 'Network',
                                'site_domain' => 'Site Domain',
                                'started_at' => 'Started At',
                                'completed_at' => 'Completed At',
                                'status' => 'Status',
                                'advertisers_synced' => 'Advertisers',
                                'ads_synced' => 'Ads',
                                'ads_deleted' => 'Deleted',
                            ];
                        @endphp
                        @foreach($columns as $col => $label)
                            <th wire:click="sortBy('{{ $col }}')"
                                class="cursor-pointer select-none whitespace-nowrap px-4 py-2.5 text-[0.65rem] font-semibold uppercase tracking-wider text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <span class="inline-flex items-center gap-1">
                                    {{ $label }}
                                    @if($sortField === $col)
                                        @if($sortDir === 'asc')
                                            <svg class="h-3 w-3 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                        @else
                                            <svg class="h-3 w-3 text-cyan-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                        @endif
                                    @else
                                        <svg class="h-3 w-3 opacity-0 group-hover:opacity-30" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse($logs as $log)
                        <tr class="transition-colors {{ $log->status === 'failed' ? 'bg-red-50/40 hover:bg-red-50/70 dark:bg-red-900/5 dark:hover:bg-red-900/10 cursor-pointer' : 'hover:bg-gray-50/80 dark:hover:bg-gray-700/20' }}"
                            @if($log->status === 'failed' && $log->error_message)
                                @click="openError(@js($log->error_message))"
                            @endif
                        >
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $log->id }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">
                                <span class="inline-block rounded px-2 py-0.5 font-mono text-[0.6rem] font-semibold uppercase tracking-wider sl-net-{{ $log->network }}">
                                    {{ $log->network }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-xs text-gray-700 dark:text-gray-300">{{ $log->site_domain ?: '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs tabular-nums text-gray-600 dark:text-gray-400">
                                {{ $log->started_at?->format('M j, Y H:i:s') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs tabular-nums text-gray-600 dark:text-gray-400">
                                {{ $log->completed_at?->format('M j, Y H:i:s') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5">
                                @if($log->status === 'success')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[0.65rem] font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                        Success
                                    </span>
                                @elseif($log->status === 'running')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[0.65rem] font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                        <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-blue-500"></span>
                                        Running
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[0.65rem] font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                        Failed
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs tabular-nums text-gray-600 dark:text-gray-400">{{ $log->advertisers_synced ?? 0 }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs tabular-nums text-gray-600 dark:text-gray-400">{{ $log->ads_synced ?? 0 }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs tabular-nums text-gray-600 dark:text-gray-400">{{ $log->ads_deleted ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No sync logs found</p>
                                    @if($hasActiveFilters)
                                        <button wire:click="clearFilters" class="text-xs font-medium text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 dark:hover:text-cyan-300">
                                            Clear filters
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
