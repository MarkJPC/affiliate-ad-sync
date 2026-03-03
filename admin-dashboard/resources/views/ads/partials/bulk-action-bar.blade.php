{{-- Sticky bulk action bar --}}
<div x-show="selected.length > 0" x-cloak
    class="adv-bulk-bar sticky top-16 z-20 rounded-lg p-2 shadow-sm">
    <div class="flex flex-wrap items-center gap-2">
        {{-- Selection count --}}
        <span class="text-xs font-medium text-cyan-700 dark:text-cyan-300">
            <span x-text="selected.includes('all_matching') ? '{{ number_format($totalMatching) }}' : selected.length"></span> selected
        </span>

        {{-- Select all matching --}}
        <template x-if="!selected.includes('all_matching') && selected.length > 0 && selected.length < {{ $totalMatching }}">
            <button type="button" @click="selectAllMatching()" class="text-xs text-cyan-600 underline hover:no-underline dark:text-cyan-400">
                Select all {{ number_format($totalMatching) }} matching
            </button>
        </template>

        <div class="h-4 w-px bg-cyan-200/50 dark:bg-cyan-700/50"></div>

        {{-- Bulk Approve --}}
        <button type="button" @click="bulkApprove()"
            class="inline-flex items-center gap-1 rounded-lg border border-green-300 bg-green-50 px-3 py-1 text-xs font-medium text-green-700 transition-all hover:bg-green-100 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/40">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Approve
        </button>

        {{-- Bulk Deny --}}
        <button type="button" @click="startBulkDeny()"
            class="inline-flex items-center gap-1 rounded-lg border border-red-300 bg-red-50 px-3 py-1 text-xs font-medium text-red-700 transition-all hover:bg-red-100 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            Deny
        </button>

        {{-- Clear selection --}}
        <div class="ml-auto">
            <button type="button" @click="clearSelection()" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                Clear
            </button>
        </div>
    </div>
</div>
