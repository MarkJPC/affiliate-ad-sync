{{-- Advertiser Info Popup — Alpine.js modal triggered from ad card/detail --}}
<div x-show="showAdvertiserPopup" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="adv-modal-backdrop fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
    @click.self="showAdvertiserPopup = false"
    @keydown.escape.window="if (showAdvertiserPopup) { showAdvertiserPopup = false; $event.stopPropagation(); }">

    <div class="w-full max-w-sm overflow-hidden rounded-xl bg-white shadow-xl dark:bg-gray-800"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        {{-- Header with logo --}}
        <div class="border-b border-gray-200/60 px-5 py-4 dark:border-gray-700/40">
            <div class="flex items-start gap-3">
                {{-- Logo --}}
                <template x-if="advertiserDetail?.logo_url">
                    <img :src="advertiserDetail.logo_url" :alt="advertiserDetail.name"
                        class="h-12 w-12 shrink-0 rounded-lg border border-gray-200 object-contain bg-white dark:border-gray-600"
                        x-on:error="$el.style.display='none'">
                </template>
                <template x-if="!advertiserDetail?.logo_url">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21"/></svg>
                    </div>
                </template>
                <div class="min-w-0 flex-1">
                    <h3 class="font-display text-base font-500 text-gray-900 dark:text-white truncate" x-text="advertiserDetail?.name"></h3>
                    <div class="mt-0.5 flex items-center gap-2">
                        <span class="adv-badge"
                            :class="{
                                'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': advertiserDetail?.network === 'flexoffers',
                                'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': advertiserDetail?.network === 'awin',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': advertiserDetail?.network === 'cj',
                                'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': advertiserDetail?.network === 'impact',
                            }"
                            x-text="advertiserDetail?.network"></span>
                        <template x-if="advertiserDetail?.network_rank">
                            <span class="inline-flex items-center gap-0.5 text-[0.65rem] text-gray-500 dark:text-gray-400">
                                <svg class="h-3 w-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <span class="font-mono" x-text="Number(advertiserDetail.network_rank).toFixed(1)"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Description --}}
        <template x-if="advertiserDetail?.description">
            <div class="border-b border-gray-200/60 px-5 py-3 dark:border-gray-700/40">
                <p class="text-xs leading-relaxed text-gray-600 dark:text-gray-300 line-clamp-6" x-text="advertiserDetail.description"></p>
            </div>
        </template>

        {{-- Metadata --}}
        <div class="space-y-0 divide-y divide-gray-100 px-5 dark:divide-gray-700/30">
            <template x-if="advertiserDetail?.category">
                <div class="flex items-center justify-between py-2.5">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Category</span>
                    <span class="text-xs text-gray-900 dark:text-white truncate max-w-[180px]" x-text="advertiserDetail.category"></span>
                </div>
            </template>
            <template x-if="advertiserDetail?.website_url">
                <div class="flex items-center justify-between py-2.5">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Website</span>
                    <a :href="advertiserDetail.website_url" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-xs text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 dark:hover:text-cyan-300 truncate max-w-[180px]">
                        <span x-text="advertiserDetail.website_url.replace(/^https?:\/\//, '').replace(/\/$/, '')"></span>
                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </template>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Region</span>
                <span class="text-xs text-gray-900 dark:text-white" x-text="advertiserDetail?.geo_region || '(none)'"></span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-end border-t border-gray-200/60 px-5 py-3 dark:border-gray-700/40">
            <button @click="showAdvertiserPopup = false"
                class="rounded-lg px-4 py-1.5 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                Close
            </button>
        </div>
    </div>
</div>
