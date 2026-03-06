<div x-data="adReviewGrid()" x-cloak class="ad-review font-body">

    {{-- Page header — compact --}}
    <div class="adv-header relative mb-2 overflow-hidden rounded-lg border border-gray-200/60 bg-white px-4 py-2.5 dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="adv-header-texture"></div>
        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div>
                    <h1 class="font-display text-lg font-500 tracking-tight text-gray-900 dark:text-white">Ad Review</h1>
                    <p class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-mono tabular-nums">{{ number_format($ads->total()) }}</span> ads
                        @if($needsAttentionCount > 0)
                            <span class="inline-block h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[0.6rem] font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                                <span class="inline-block h-1 w-1 animate-pulse rounded-full bg-amber-500"></span>
                                {{ number_format($needsAttentionCount) }} new
                            </span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                {{-- View size toggle --}}
                <div class="flex items-center rounded border border-gray-200 bg-gray-50 p-px dark:border-gray-600 dark:bg-gray-700/50">
                    <button x-on:click="setViewSize('large')" class="rounded px-1.5 py-0.5 transition-colors"
                        :class="viewSize === 'large' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-600 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                    </button>
                    <button x-on:click="setViewSize('medium')" class="rounded px-1.5 py-0.5 transition-colors"
                        :class="viewSize === 'medium' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-600 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    </button>
                    <button x-on:click="setViewSize('small')" class="rounded px-1.5 py-0.5 transition-colors"
                        :class="viewSize === 'small' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-600 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="4" height="4" rx="0.5"/><rect x="10" y="3" width="4" height="4" rx="0.5"/><rect x="17" y="3" width="4" height="4" rx="0.5"/><rect x="3" y="10" width="4" height="4" rx="0.5"/><rect x="10" y="10" width="4" height="4" rx="0.5"/><rect x="17" y="10" width="4" height="4" rx="0.5"/></svg>
                    </button>
                </div>

                {{-- Sort --}}
                <select wire:model.live="sortField"
                    class="adv-filter-input border-gray-200 bg-gray-50 py-0.5 text-[0.7rem] text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="last_synced_at">Last synced</option>
                    <option value="advert_name">Name</option>
                    <option value="network">Network</option>
                    <option value="width">Size</option>
                    <option value="epc">EPC</option>
                    <option value="approval_status">Status</option>
                </select>
                <button wire:click="sortBy('{{ $this->sortField }}')"
                    class="rounded border border-gray-200 bg-gray-50 p-1 text-gray-400 transition-colors hover:text-gray-600 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-500 dark:hover:text-gray-300">
                    @if($this->sortDir === 'asc')
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                    @else
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/></svg>
                    @endif
                </button>

                {{-- Mark as Reviewed --}}
                @if($needsAttentionCount > 0)
                    <button wire:click="markReviewed"
                        class="adv-btn-apply inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-[0.7rem] font-semibold text-white shadow-sm">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Mark Reviewed
                    </button>
                @endif

                {{-- Select all on page --}}
                <label class="flex cursor-pointer items-center gap-1">
                    <input type="checkbox" x-on:change="toggleSelectAll($event)"
                        :checked="allOnPageSelected()"
                        class="h-3.5 w-3.5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700">
                    <span class="text-[0.65rem] text-gray-500 dark:text-gray-400">All</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Filter bar (inline, reactive) --}}
    <div class="mb-2 rounded-xl border border-gray-200/60 bg-white dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="flex flex-wrap items-end gap-1.5 px-3 py-2">
            {{-- Search --}}
            <div class="min-w-[180px] flex-1">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search ads or advertisers..."
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

            {{-- Creative Type --}}
            <div class="w-[100px]">
                <select wire:model.live="creativeType" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Type</option>
                    @foreach(['banner', 'text', 'html'] as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Approval Status --}}
            <div class="w-[100px]">
                <select wire:model.live="approvalStatus" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Approval</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                </select>
            </div>

            {{-- Dimensions --}}
            <div class="w-[130px]">
                <select wire:model.live="dimensions" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Dimensions</option>
                    @foreach($dimensionsList as $dim)
                        <option value="{{ $dim }}">{{ $dim }}{{ $activeSizeStrings->contains($dim) ? ' (active)' : '' }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Advertiser Status --}}
            <div class="w-[130px]">
                <select wire:model.live="advertiserStatus" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Adv. Status</option>
                    <option value="allowed">Approved on any site</option>
                    <option value="pending">Pending only</option>
                    <option value="denied_all">Denied on all sites</option>
                </select>
            </div>

            {{-- Country --}}
            <div class="w-[85px]">
                <select wire:model.live="country" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    <option value="">Country</option>
                    @foreach($countryList as $cc)
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

            {{-- Searchable Advertiser Select --}}
            <div class="relative w-[160px]" x-data="advertiserSelect()" @click.outside="open = false">
                <input type="text" x-model="searchText" @focus="open = true" @input="open = true"
                    placeholder="Advertiser..."
                    class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 pl-3 pr-7 text-gray-900 placeholder-gray-400 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-500">

                {{-- Clear button --}}
                <button type="button" x-show="selectedId" @click="clear()" class="absolute right-1.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                {{-- Dropdown --}}
                <div x-show="open && filtered().length > 0" x-cloak
                    class="absolute left-0 top-full z-30 mt-1 max-h-48 w-64 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700">
                    <template x-for="adv in filtered()" :key="adv.id">
                        <button type="button" @click="select(adv)"
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs hover:bg-gray-50 dark:hover:bg-gray-600">
                            <span class="truncate text-gray-900 dark:text-white" x-text="adv.name"></span>
                            <span class="adv-badge ml-auto shrink-0"
                                :class="{
                                    'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': adv.network === 'flexoffers',
                                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': adv.network === 'awin',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': adv.network === 'cj',
                                    'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': adv.network === 'impact',
                                }" x-text="adv.network"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Has Image toggle --}}
            <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs dark:border-gray-600 dark:bg-gray-700/50">
                <input type="checkbox" value="1"
                    x-on:change="$wire.set('hasImage', $event.target.checked ? '1' : '0')"
                    :checked="$wire.hasImage === '1'"
                    class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-gray-600 dark:text-gray-300">Has image</span>
            </label>

            {{-- Needs Attention toggle --}}
            <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs dark:border-gray-600 dark:bg-gray-700/50">
                <input type="checkbox" value="1"
                    x-on:change="$wire.set('needsAttention', $event.target.checked ? '1' : '0')"
                    :checked="$wire.needsAttention === '1'"
                    class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-gray-600 dark:text-gray-300">Needs attention</span>
                @if($needsAttentionCount > 0)
                    <span class="rounded-full bg-amber-100 px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">{{ $needsAttentionCount }}</span>
                @endif
            </label>

            {{-- Active Placement Sizes toggle --}}
            <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs dark:border-gray-600 dark:bg-gray-700/50">
                <input type="checkbox" value="1"
                    x-on:change="$wire.set('placementSizesOnly', $event.target.checked ? '1' : '0')"
                    :checked="$wire.placementSizesOnly === '1'"
                    class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-gray-600 dark:text-gray-300">Active sizes only</span>
            </label>

            {{-- Per page --}}
            <div class="w-[55px]">
                <select wire:model.live="perPage" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                    @foreach([24, 48, 96] as $pp)
                        <option value="{{ $pp }}">{{ $pp }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Reset (only when filters active) --}}
            @if($hasActiveFilters)
                <button wire:click="clearFilters" class="text-xs font-medium text-gray-500 transition-colors hover:text-cyan-600 dark:text-gray-400 dark:hover:text-cyan-400">
                    Reset
                </button>
            @endif
        </div>
    </div>

    {{-- Active filter pills --}}
    @if($hasActiveFilters || $this->hasImage !== '1' || $this->needsAttention !== '1' || $this->placementSizesOnly !== '1')
    <div class="flex flex-wrap gap-1.5 mb-2" wire:key="active-filters">
        @if($this->search !== '')
            <span class="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                "{{ Str::limit($this->search, 20) }}"
                <button wire:click="$set('search', '')" class="ml-0.5 hover:text-cyan-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->network !== '')
            <span class="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                {{ ucfirst($this->network) }}
                <button wire:click="$set('network', '')" class="ml-0.5 hover:text-cyan-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->creativeType !== '')
            <span class="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                {{ ucfirst($this->creativeType) }}
                <button wire:click="$set('creativeType', '')" class="ml-0.5 hover:text-cyan-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->approvalStatus !== '')
            <span class="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                {{ ucfirst($this->approvalStatus) }}
                <button wire:click="$set('approvalStatus', '')" class="ml-0.5 hover:text-cyan-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->advertiserId !== '')
            <span class="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                Advertiser #{{ $this->advertiserId }}
                <button wire:click="$set('advertiserId', '')" class="ml-0.5 hover:text-cyan-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->dimensions !== '')
            <span class="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                {{ $this->dimensions }}
                <button wire:click="$set('dimensions', '')" class="ml-0.5 hover:text-cyan-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->advertiserStatus !== '')
            <span class="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                Adv: {{ ucfirst($this->advertiserStatus) }}
                <button wire:click="$set('advertiserStatus', '')" class="ml-0.5 hover:text-cyan-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->country !== '')
            <span class="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                Country: {{ $this->country }}
                <button wire:click="$set('country', '')" class="ml-0.5 hover:text-cyan-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->region !== '')
            <span class="inline-flex items-center gap-1 rounded-full bg-cyan-50 px-2.5 py-0.5 text-[0.65rem] font-medium text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                Region: {{ $this->region }}
                <button wire:click="$set('region', '')" class="ml-0.5 hover:text-cyan-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->hasImage !== '1')
            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-[0.65rem] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                All (incl. no image)
                <button wire:click="$set('hasImage', '1')" class="ml-0.5 hover:text-gray-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->needsAttention !== '1')
            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-[0.65rem] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                All (incl. reviewed)
                <button wire:click="$set('needsAttention', '1')" class="ml-0.5 hover:text-gray-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
        @if($this->placementSizesOnly !== '1')
            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-[0.65rem] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                All sizes (incl. inactive)
                <button wire:click="$set('placementSizesOnly', '1')" class="ml-0.5 hover:text-gray-900 dark:hover:text-white">&times;</button>
            </span>
        @endif
    </div>
    @endif

    {{-- Bulk action bar --}}
    <div class="mb-2">
        @include('ads.partials.bulk-action-bar')
    </div>

    {{-- Grid or empty state --}}
    @if($ads->isEmpty())
        @include('ads.partials.empty-state')
    @else
        {{-- Grid wrapper with loading states --}}
        <div class="relative">
            {{-- Shimmer overlay during load --}}
            <div wire:loading.delay.shortest class="absolute inset-0 z-10 grid gap-2 pointer-events-none"
                :class="getGridClasses()">
                @for($i = 0; $i < 12; $i++)
                <div class="animate-pulse rounded-lg border border-gray-200/40 bg-white dark:border-gray-700/30 dark:bg-gray-800/60">
                    <div class="px-2 pt-1.5 pb-1 flex justify-between">
                        <div class="h-3 w-3 rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="h-3 w-10 rounded bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                    <div class="px-2"><div class="h-[100px] rounded bg-gray-100 dark:bg-gray-700/50"></div></div>
                    <div class="px-2 py-2"><div class="h-3 w-3/4 rounded bg-gray-200 dark:bg-gray-700"></div></div>
                </div>
                @endfor
            </div>

            {{-- Actual grid with staggered card entrance --}}
            <div wire:loading.class="opacity-30 transition-opacity duration-200" class="grid gap-2" :class="getGridClasses()">
                @foreach($ads as $ad)
                    <div class="ad-card-enter" style="animation-delay: {{ $loop->index * 30 }}ms" wire:key="ad-{{ $ad->id }}">
                        @include('ads.partials.card', ['ad' => $ad])
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $ads->links() }}
        </div>
    @endif

    {{-- Modals --}}
    @include('ads.partials.deny-reason-modal')
    @include('ads.partials.detail-modal')

    {{-- Toast notification --}}
    <div x-show="toast.show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-4 opacity-0"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 rounded-lg px-4 py-2.5 text-sm font-medium shadow-lg"
        :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        x-text="toast.message">
    </div>
</div>
