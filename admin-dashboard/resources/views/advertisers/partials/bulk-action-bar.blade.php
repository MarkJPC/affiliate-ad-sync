<div x-show="selected.length > 0" x-cloak
    class="sticky top-16 z-20 rounded-lg border border-blue-200 bg-blue-50 p-2 shadow-sm dark:border-blue-800 dark:bg-blue-900/30">
    <div class="flex flex-wrap items-center gap-2">
        {{-- Selection count --}}
        <span class="text-xs font-medium text-blue-700 dark:text-blue-300">
            <span x-text="selected.length"></span> selected
        </span>

        <template x-if="selected.length < {{ $totalMatching }} && selected.length > 0">
            <button type="button" @click="selectAllMatching()" class="text-xs text-blue-600 underline hover:no-underline dark:text-blue-400">
                Select all {{ number_format($totalMatching) }} matching
            </button>
        </template>

        <div class="h-4 w-px bg-blue-200 dark:bg-blue-700"></div>

        {{-- Bulk weight --}}
        <div class="flex items-center gap-1.5">
            <select x-model="bulkWeight" class="rounded-md border-gray-300 py-0.5 pl-2 pr-6 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">Weight...</option>
                <option value="clear">--- (clear)</option>
                @foreach([2, 4, 6, 8, 10] as $w)
                    <option value="{{ $w }}">{{ $w }}</option>
                @endforeach
            </select>
            <button type="button" @click="applyBulkWeight()" :disabled="!bulkWeight"
                class="rounded-md bg-blue-600 px-2.5 py-0.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Apply Weight
            </button>
        </div>

        <div class="h-4 w-px bg-blue-200 dark:bg-blue-700"></div>

        {{-- Bulk rule --}}
        <div class="flex items-center gap-1.5">
            <select x-model="bulkRuleSite" class="rounded-md border-gray-300 py-0.5 pl-2 pr-6 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">Site...</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                @endforeach
            </select>
            <select x-model="bulkRuleValue" class="rounded-md border-gray-300 py-0.5 pl-2 pr-6 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">Rule...</option>
                <option value="allowed">Allowed</option>
                <option value="denied">Denied</option>
                <option value="default">Pending</option>
            </select>
            <button type="button" @click="applyBulkRule()" :disabled="!bulkRuleSite || !bulkRuleValue"
                class="rounded-md bg-blue-600 px-2.5 py-0.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Apply Rule
            </button>
        </div>

        <div class="ml-auto">
            <button type="button" @click="clearSelection()" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                Clear
            </button>
        </div>
    </div>
</div>
