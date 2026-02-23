<div x-show="showDetailModal" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="adv-modal-backdrop fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    @click.self="showDetailModal = false"
    @keydown.escape.window="showDetailModal = false">

    <div class="adv-modal-panel w-full max-w-md overflow-hidden rounded-xl bg-white dark:bg-gray-800"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        {{-- Hero section --}}
        <div class="adv-detail-hero relative overflow-hidden border-b border-gray-200/60 px-5 py-4 dark:border-gray-700/40">
            <div class="adv-header-texture"></div>
            <div class="relative">
                {{-- Network badge + Status pill --}}
                <div class="mb-2.5 flex items-center gap-2">
                    <span class="adv-badge"
                        :class="{
                            'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': detailData.network === 'flexoffers',
                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': detailData.network === 'awin',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': detailData.network === 'cj',
                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': detailData.network === 'impact',
                        }"
                        x-text="detailData.network"></span>
                    <span x-show="detailData.is_active"
                        class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2 py-0.5 text-[0.65rem] font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500"></span>
                        Active
                    </span>
                    <span x-show="!detailData.is_active"
                        class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-[0.65rem] font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                        Inactive
                    </span>
                </div>

                {{-- Advertiser name --}}
                <h3 class="font-display text-xl font-500 tracking-tight text-gray-900 dark:text-white" x-text="detailData.name"></h3>

                {{-- Website link --}}
                <template x-if="detailData.website_url">
                    <a :href="detailData.website_url" target="_blank" rel="noopener noreferrer"
                        class="mt-1.5 inline-flex items-center gap-1 text-xs text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 dark:hover:text-cyan-300">
                        <span>Visit website</span>
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </template>
            </div>
        </div>

        {{-- KPI row --}}
        <div class="grid grid-cols-4 divide-x divide-gray-200/60 border-b border-gray-200/60 dark:divide-gray-700/40 dark:border-gray-700/40">
            <div class="px-4 py-3 text-center">
                <p class="text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Revenue</p>
                <p class="font-mono text-lg tabular-nums text-gray-900 dark:text-white" x-text="'$' + Number(detailData.total_revenue).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></p>
            </div>
            <div class="px-4 py-3 text-center">
                <p class="text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">EPC</p>
                <p class="font-mono text-lg tabular-nums text-cyan-600 dark:text-cyan-400" x-text="'$' + Number(detailData.epc).toFixed(2)"></p>
            </div>
            <div class="px-4 py-3 text-center">
                <p class="text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Clicks</p>
                <p class="font-mono text-lg tabular-nums text-gray-900 dark:text-white" x-text="Number(detailData.total_clicks).toLocaleString()"></p>
            </div>
            <div class="px-4 py-3 text-center">
                <p class="text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Ads</p>
                <p class="font-mono text-lg tabular-nums text-gray-900 dark:text-white" x-text="Number(detailData.ads_count).toLocaleString()"></p>
            </div>
        </div>

        {{-- Secondary details --}}
        <div class="space-y-0 divide-y divide-gray-100 px-5 dark:divide-gray-700/30">
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Category</span>
                <span class="text-xs text-gray-900 dark:text-white" x-text="detailData.category"></span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Commission</span>
                <span class="font-mono text-xs text-gray-900 dark:text-white" x-text="detailData.commission_rate"></span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Last synced</span>
                <span class="font-mono text-[0.7rem] text-gray-500 dark:text-gray-400" x-text="detailData.last_synced_at"></span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end border-t border-gray-200/60 px-5 py-3 dark:border-gray-700/40">
            <button @click="showDetailModal = false"
                class="rounded-lg px-4 py-1.5 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                Close
            </button>
        </div>
    </div>
</div>
