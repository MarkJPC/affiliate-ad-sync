<form method="GET" action="{{ route('advertisers.index') }}"
    class="rounded-xl border border-gray-200/60 bg-white dark:border-gray-700/40 dark:bg-gray-800/80">

    {{-- Compact filter grid --}}
    <div class="flex flex-wrap items-end gap-1.5 px-3 py-2">
        {{-- Search --}}
        <div class="min-w-[180px] flex-1">
            <input type="text" name="search" id="search" value="{{ request('search') }}"
                placeholder="Search advertiser..."
                class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 pl-3 pr-2 text-gray-900 placeholder-gray-400 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-500">
        </div>

        {{-- Network --}}
        <div class="w-[100px]">
            <select name="network" id="network" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Network</option>
                @foreach(['flexoffers', 'awin', 'cj', 'impact'] as $net)
                    <option value="{{ $net }}" @selected(request('network') === $net)>{{ ucfirst($net) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Category --}}
        <div class="w-[120px]">
            <select name="category" id="category" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Category</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        {{-- Weight --}}
        <div class="w-[85px]">
            <select name="weight" id="weight" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Weight</option>
                <option value="unassigned" @selected(request('weight') === 'unassigned')>None</option>
                @foreach([2, 4, 6, 8, 10] as $w)
                    <option value="{{ $w }}" @selected(request('weight') == $w)>{{ $w }}</option>
                @endforeach
            </select>
        </div>

        {{-- Rule --}}
        <div class="w-[85px]">
            <select name="rule" id="rule" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Rule</option>
                <option value="default" @selected(request('rule') === 'default')>Pending</option>
                <option value="allowed" @selected(request('rule') === 'allowed')>Allowed</option>
                <option value="denied" @selected(request('rule') === 'denied')>Denied</option>
            </select>
        </div>

        {{-- Rule site --}}
        <div class="w-[110px]">
            <select name="rule_site" id="rule_site" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Rule Site</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" @selected(request('rule_site') == $site->id)>{{ $site->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Status --}}
        <div class="w-[80px]">
            <select name="active" id="active" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                <option value="">Status</option>
                <option value="1" @selected(request('active') === '1')>Active</option>
                <option value="0" @selected(request('active') === '0')>Inactive</option>
            </select>
        </div>

        {{-- EPC range --}}
        <div class="flex items-end gap-1">
            <div class="w-[65px]">
                <input type="number" name="epc_min" value="{{ request('epc_min') }}" placeholder="EPC min" step="0.01" min="0"
                    class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 font-mono text-xs text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
            </div>
            <span class="pb-1.5 text-xs text-gray-300 dark:text-gray-600">&ndash;</span>
            <div class="w-[65px]">
                <input type="number" name="epc_max" value="{{ request('epc_max') }}" placeholder="EPC max" step="0.01" min="0"
                    class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 font-mono text-xs text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
            </div>
        </div>

        {{-- Per page --}}
        <div class="w-[60px]">
            <select name="per_page" class="adv-filter-input w-full border-gray-200 bg-gray-50 py-1 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white">
                @foreach([25, 50, 100] as $pp)
                    <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
                @endforeach
            </select>
        </div>

        {{-- Preserve sort --}}
        @if(request('sort'))
            <input type="hidden" name="sort" value="{{ request('sort') }}">
        @endif
        @if(request('dir'))
            <input type="hidden" name="dir" value="{{ request('dir') }}">
        @endif

        {{-- Actions --}}
        <div class="flex items-end gap-1.5">
            <button type="submit" class="adv-btn-apply rounded-lg px-4 py-1 text-xs font-semibold text-white shadow-sm transition-all hover:shadow-md">
                Filter
            </button>
            @if(request()->hasAny(['search', 'network', 'category', 'weight', 'rule', 'active', 'epc_min', 'epc_max', 'rule_site']))
                <a href="{{ route('advertisers.index') }}" class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-gray-200">
                    Clear
                </a>
            @endif
        </div>
    </div>
</form>
