<form method="GET" action="{{ route('ads.index') }}"
    class="rounded-xl border border-gray-200/60 bg-white dark:border-gray-700/40 dark:bg-gray-800/80">

    <div class="flex flex-wrap items-end gap-1.5 px-3 py-2">
        {{-- Search --}}
        <div class="min-w-[180px] flex-1">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search ads or advertisers..."
                class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 pl-3 pr-2 text-gray-900 placeholder-gray-400 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-500">
        </div>

        {{-- Network --}}
        <div class="w-[100px]">
            <select name="network" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Network</option>
                @foreach(['flexoffers', 'awin', 'cj', 'impact'] as $net)
                    <option value="{{ $net }}" @selected(request('network') === $net)>{{ ucfirst($net) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Creative Type --}}
        <div class="w-[100px]">
            <select name="creative_type" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Type</option>
                @foreach(['banner', 'text', 'html'] as $type)
                    <option value="{{ $type }}" @selected(request('creative_type') === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Approval Status --}}
        <div class="w-[100px]">
            <select name="approval_status" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Approval</option>
                <option value="approved" @selected(request('approval_status') === 'approved')>Approved</option>
                <option value="denied" @selected(request('approval_status') === 'denied')>Denied</option>
            </select>
        </div>

        {{-- Dimensions --}}
        <div class="w-[110px]">
            <select name="dimensions" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Dimensions</option>
                @foreach($dimensions as $dim)
                    <option value="{{ $dim }}" @selected(request('dimensions') === $dim)>{{ $dim }}</option>
                @endforeach
            </select>
        </div>

        {{-- Advertiser Status --}}
        <div class="w-[115px]">
            <select name="advertiser_status" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Adv. Status</option>
                <option value="allowed" @selected(request('advertiser_status') === 'allowed')>Allowed</option>
                <option value="denied" @selected(request('advertiser_status') === 'denied')>Denied</option>
            </select>
        </div>

        {{-- Searchable Advertiser Select --}}
        <div class="relative w-[160px]" x-data="advertiserSelect()" @click.outside="open = false">
            <input type="text" x-model="searchText" @focus="open = true" @input="open = true"
                placeholder="Advertiser..."
                class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 pl-3 pr-7 text-gray-900 placeholder-gray-400 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-500">
            <input type="hidden" name="advertiser_id" :value="selectedId">

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
            <input type="checkbox" name="has_image" value="1"
                @checked($hasImage === '1')
                class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700">
            <span class="text-gray-600 dark:text-gray-300">Has image</span>
        </label>

        {{-- Needs Attention toggle --}}
        <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs dark:border-gray-600 dark:bg-gray-700/50">
            <input type="checkbox" name="needs_attention" value="1"
                @checked($needsAttention === '1')
                class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700">
            <span class="text-gray-600 dark:text-gray-300">Needs attention</span>
            @if($needsAttentionCount > 0)
                <span class="rounded-full bg-amber-100 px-1.5 py-0.5 font-mono text-[0.6rem] font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">{{ $needsAttentionCount }}</span>
            @endif
        </label>

        {{-- Per page --}}
        <div class="w-[55px]">
            <select name="per_page" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                @foreach([24, 48, 96] as $pp)
                    <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
                @endforeach
            </select>
        </div>

        {{-- Preserve sort + view_size --}}
        @if(request('sort'))
            <input type="hidden" name="sort" value="{{ request('sort') }}">
        @endif
        @if(request('dir'))
            <input type="hidden" name="dir" value="{{ request('dir') }}">
        @endif
        @if(request('view_size'))
            <input type="hidden" name="view_size" value="{{ request('view_size') }}">
        @endif

        {{-- Actions --}}
        <div class="flex items-end gap-1.5">
            <button type="submit" class="adv-btn-apply rounded-lg px-4 py-1 text-xs font-semibold text-white shadow-sm transition-all hover:shadow-md">
                Filter
            </button>
            @if(request()->hasAny(['search', 'network', 'creative_type', 'approval_status', 'advertiser_id', 'dimensions', 'advertiser_status']))
                <a href="{{ route('ads.index') }}" class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-gray-200">
                    Clear
                </a>
            @endif
        </div>
    </div>
</form>
