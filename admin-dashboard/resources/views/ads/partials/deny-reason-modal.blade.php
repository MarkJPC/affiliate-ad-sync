{{-- Deny reason modal (shared for single + bulk) --}}
<div x-show="showDenyModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="adv-modal-backdrop fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
    @click.self="showDenyModal = false"
    @keydown.escape.window="showDenyModal = false">

    <div class="adv-modal-panel w-full max-w-md overflow-hidden rounded-xl bg-white dark:bg-gray-800"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        {{-- Header --}}
        <div class="border-b border-gray-200/60 px-5 py-4 dark:border-gray-700/40">
            <h3 class="font-display text-lg font-500 text-gray-900 dark:text-white">
                <span x-text="denyTarget?.type === 'bulk' ? 'Deny Selected Ads' : 'Deny Ad'"></span>
            </h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <template x-if="denyTarget?.type === 'bulk'">
                    <span x-text="(selected.includes('all_matching') ? '{{ $totalMatching }}' : selected.length) + ' ad(s) will be denied.'"></span>
                </template>
                <template x-if="denyTarget?.type === 'single'">
                    <span>This ad will be marked as denied.</span>
                </template>
            </p>
        </div>

        {{-- Body --}}
        <div class="px-5 py-4">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Reason (optional)</label>
            <textarea x-model="denyReason" rows="3" placeholder="Why is this ad being denied?"
                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"></textarea>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 border-t border-gray-200/60 px-5 py-3 dark:border-gray-700/40">
            <button @click="showDenyModal = false; denyReason = ''"
                class="rounded-lg px-4 py-2 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                Cancel
            </button>
            <button @click="confirmDeny()"
                class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-all hover:bg-red-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Deny
            </button>
        </div>
    </div>
</div>
