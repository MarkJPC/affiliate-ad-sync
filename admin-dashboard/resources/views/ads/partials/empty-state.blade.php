@if($this->needsAttention === '1' && !$hasActiveFilters)
    {{-- All caught up --}}
    <div class="flex flex-col items-center justify-center rounded-xl border border-gray-200/60 bg-white px-6 py-16 dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="font-display text-lg font-500 text-gray-900 dark:text-white">All caught up!</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No new ads need review.</p>
    </div>
@else
    {{-- No matching results --}}
    <div class="flex flex-col items-center justify-center rounded-xl border border-gray-200/60 bg-white px-6 py-16 dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
            <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 002.25-2.25V5.25a2.25 2.25 0 00-2.25-2.25H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
            </svg>
        </div>
        <h3 class="font-display text-lg font-500 text-gray-900 dark:text-white">No ads match your filters.</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters or
            <button wire:click="clearFilters" class="text-cyan-600 hover:text-cyan-700 underline dark:text-cyan-400 dark:hover:text-cyan-300">clear all filters</button>.
        </p>
    </div>
@endif
