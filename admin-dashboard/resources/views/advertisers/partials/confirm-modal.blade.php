<div x-show="showConfirmModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    @click.self="showConfirmModal = false"
    @keydown.escape.window="showConfirmModal = false">

    <div class="w-full max-w-lg rounded-lg bg-white shadow-xl dark:bg-gray-800"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Apply Changes</h3>
            <button @click="showConfirmModal = false" :disabled="isApplying" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="max-h-80 overflow-y-auto px-6 py-4">
            {{-- Weight changes --}}
            <template x-if="Object.keys(dirtyWeights).length > 0">
                <div class="mb-4">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Weight Changes</h4>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <template x-for="id in Object.keys(dirtyWeights)" :key="id">
                            <li class="flex items-center gap-2">
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                <span x-text="getAdvertiserName(id)"></span>
                                <span class="text-gray-400">&rarr;</span>
                                <span class="font-medium" x-text="weights[id] || '---'"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            {{-- Rule changes --}}
            <template x-if="Object.keys(dirtyRules).length > 0">
                <div class="mb-4">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Rule Changes</h4>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <template x-for="key in Object.keys(dirtyRules)" :key="key">
                            <li class="flex items-center gap-2">
                                <span class="inline-block h-1.5 w-1.5 rounded-full"
                                    :class="{
                                        'bg-green-500': rules[key] === 'allowed',
                                        'bg-red-500': rules[key] === 'denied',
                                        'bg-amber-500': rules[key] === 'default'
                                    }"></span>
                                <span x-text="getAdvertiserName(key.split('-')[0])"></span>
                                <span class="text-gray-400">on</span>
                                <span x-text="getSiteName(key.split('-')[1])"></span>
                                <span class="text-gray-400">&rarr;</span>
                                <span class="font-medium" x-text="rules[key]"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            {{-- Reason --}}
            <div class="mt-4">
                <label for="applyReason" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Reason (optional)</label>
                <input type="text" id="applyReason" x-model="applyReason" placeholder="Why are you making these changes?"
                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-3 dark:border-gray-700">
            <button @click="showConfirmModal = false" :disabled="isApplying"
                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                Cancel
            </button>
            <button @click="applyAllChanges()" :disabled="isApplying"
                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                <svg x-show="isApplying" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="isApplying ? 'Applying...' : 'Apply'"></span>
            </button>
        </div>
    </div>
</div>
