<div>
    {{-- Page header with texture --}}
    <div class="adv-header relative mb-3 overflow-hidden rounded-xl border border-gray-200/60 bg-white px-5 py-3 dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="adv-header-texture"></div>
        <div class="relative flex items-end justify-between">
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-[0.2em] text-cyan-600 dark:text-cyan-400">Affiliate Network</p>
                <h1 class="font-display text-2xl font-500 tracking-tight text-gray-900 dark:text-white">Advertiser Grid</h1>
                <p class="mt-2 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-mono text-xs tabular-nums">{{ number_format($advertisers->total()) }}</span> advertisers
                    <span class="inline-block h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    <span class="font-mono text-xs tabular-nums">{{ $sites->count() }}</span> sites
                    @if($this->rule === 'default')
                        <span class="inline-block h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                            <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span>
                            Showing pending only
                        </span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Dirty indicator + Apply button --}}
                <div x-show="isDirty()" x-transition.opacity class="flex items-center gap-3">
                    <div class="flex items-center gap-2 rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-1.5 dark:border-cyan-800/50 dark:bg-cyan-900/20">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-cyan-500"></span>
                        </span>
                        <span class="font-mono text-xs font-medium text-cyan-700 dark:text-cyan-300" x-text="dirtyCount() + ' unsaved'"></span>
                    </div>
                    <button @click="discardChanges()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Discard
                    </button>
                    <button @click="showConfirmModal = true"
                        class="adv-btn-apply group inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-cyan-500/25 transition-all hover:shadow-cyan-500/40">
                        <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Apply Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter bar (inline, reactive) --}}
    <div class="mb-2 rounded-xl border border-gray-200/60 bg-white dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="flex flex-wrap items-end gap-1.5 px-3 py-2">
            {{-- Search --}}
            <div class="min-w-[180px] flex-1">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search advertiser..."
                    class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 pl-3 pr-2 text-gray-900 placeholder-gray-400 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-500">
            </div>

            {{-- Network --}}
            <div class="w-[100px]">
                <select wire:model.live="network" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Network</option>
                    @foreach(['flexoffers', 'awin', 'cj', 'impact'] as $net)
                        <option value="{{ $net }}">{{ ucfirst($net) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Category --}}
            <div class="w-[120px]">
                <select wire:model.live="category" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Weight --}}
            <div class="w-[85px]">
                <select wire:model.live="weight" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Weight</option>
                    <option value="unassigned">None</option>
                    @foreach([2, 4, 6, 8, 10] as $w)
                        <option value="{{ $w }}">{{ $w }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Rule --}}
            <div class="w-[85px]">
                <select wire:model.live="rule" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Rule</option>
                    <option value="default">Pending</option>
                    <option value="allowed">Allowed</option>
                    <option value="denied">Denied</option>
                </select>
            </div>

            {{-- Rule site --}}
            <div class="w-[110px]">
                <select wire:model.live="ruleSite" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Rule Site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Country --}}
            <div class="w-[85px]">
                <select wire:model.live="country" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Country</option>
                    @foreach($countries as $cc)
                        <option value="{{ $cc }}">{{ $cc }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Region --}}
            <div class="w-[100px]">
                <select wire:model.live="region" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Region</option>
                    @foreach($geoRegions as $gr)
                        <option value="{{ $gr->name }}">{{ $gr->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="w-[80px]">
                <select wire:model.live="active" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            {{-- EPC range --}}
            <div class="flex items-end gap-1">
                <div class="w-[65px]">
                    <input type="number" wire:model.live.debounce.500ms="epcMin" placeholder="EPC min" step="0.01" min="0"
                        class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 font-mono text-xs text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                </div>
                <span class="pb-1.5 text-xs text-gray-300 dark:text-gray-600">&ndash;</span>
                <div class="w-[65px]">
                    <input type="number" wire:model.live.debounce.500ms="epcMax" placeholder="EPC max" step="0.01" min="0"
                        class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 font-mono text-xs text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                </div>
            </div>

            {{-- Per page --}}
            <div class="w-[60px]">
                <select wire:model.live="perPage" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    @foreach([25, 50, 100] as $pp)
                        <option value="{{ $pp }}">{{ $pp }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Duplicates only --}}
            <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs dark:border-gray-600 dark:bg-gray-700/50">
                <input type="checkbox" value="1"
                    x-on:change="$wire.set('duplicatesOnly', $event.target.checked ? '1' : '')"
                    :checked="$wire.duplicatesOnly === '1'"
                    class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-gray-600 dark:text-gray-300">Duplicates only</span>
            </label>

            {{-- Reset (only when filters active) --}}
            @if($hasActiveFilters)
                <button wire:click="clearFilters" class="text-xs font-medium text-gray-500 transition-colors hover:text-cyan-600 dark:text-gray-400 dark:hover:text-cyan-400">
                    Reset
                </button>
            @endif
        </div>
    </div>

    {{-- Bulk action bar --}}
    <div class="mb-2">
        @include('advertisers.partials.bulk-action-bar')
    </div>

    {{-- Loading skeleton + dots --}}
    <div wire:loading.delay.shortest class="min-h-[320px]">
        {{-- Skeleton table rows --}}
        <div class="overflow-hidden rounded-xl border border-gray-200/60 bg-white dark:border-gray-700/40 dark:bg-gray-800/80">
            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @for($i = 0; $i < 6; $i++)
                    <div class="flex animate-pulse items-center gap-4 px-4 py-3">
                        <div class="h-3 w-8 rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="h-3 rounded bg-gray-200 dark:bg-gray-700" style="width: {{ [45, 60, 35, 55, 40, 50][$i] }}%"></div>
                        <div class="ml-auto h-3 w-16 rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="h-3 w-12 rounded bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                @endfor
            </div>
        </div>
        {{-- Loading dots --}}
        <div class="mt-4 flex justify-center">
            @include('components.loading-dots', ['text' => 'Loading advertisers...', 'size' => 'md'])
        </div>
    </div>

    {{-- Content — hidden during load --}}
    <div wire:loading.remove>
        @if($advertisers->isEmpty())
            @include('advertisers.partials.empty-state')
        @else
            @include('advertisers.partials.table')
        @endif
    </div>

    {{-- Modals --}}
    @include('advertisers.partials.detail-modal')
    @include('advertisers.partials.confirm-modal')
</div>
