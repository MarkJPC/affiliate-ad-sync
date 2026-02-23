<div class="rounded-lg border border-gray-200 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-800">
    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
    </svg>
    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No advertisers found</h3>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        @if(request()->hasAny(['search', 'network', 'category', 'weight', 'rule', 'active', 'epc_min', 'epc_max']))
            No advertisers match your current filters.
            <a href="{{ route('advertisers.index') }}" class="text-blue-600 hover:underline dark:text-blue-400">Clear all filters</a>
        @else
            Run a sync to import advertisers from your affiliate networks.
        @endif
    </p>
</div>
