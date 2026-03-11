{{-- Detail modal — full metadata + weight editing --}}
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

    <div class="adv-modal-panel w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-xl bg-white dark:bg-gray-800"
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
                {{-- Network badge + approval pill --}}
                <div class="mb-2.5 flex items-center gap-2">
                    <span class="adv-badge"
                        :class="{
                            'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': detailAd?.network === 'flexoffers',
                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': detailAd?.network === 'awin',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': detailAd?.network === 'cj',
                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': detailAd?.network === 'impact',
                        }"
                        x-text="detailAd?.network"></span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[0.65rem] font-medium"
                        :class="ads[detailAd?.id]?.approval_status === 'denied'
                            ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                            : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'">
                        <span class="inline-block h-1.5 w-1.5 rounded-full"
                            :class="ads[detailAd?.id]?.approval_status === 'denied' ? 'bg-red-500' : 'bg-green-500'"></span>
                        <span x-text="ads[detailAd?.id]?.approval_status === 'denied' ? 'Denied' : 'Approved'"></span>
                    </span>
                </div>
                <h3 class="font-display text-xl font-500 tracking-tight text-gray-900 dark:text-white" x-text="detailAd?.advert_name"></h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-cyan-600 dark:hover:text-cyan-400"
                    x-text="detailAd?.advertiser_name"
                    @click="if (detailAd?.id) openAdvertiserPopup(detailAd.id)"></p>
            </div>
        </div>

        {{-- Preview --}}
        <div class="border-b border-gray-200/60 px-5 py-4 dark:border-gray-700/40">
            <div class="flex items-center justify-center overflow-hidden rounded-lg bg-gray-50 dark:bg-gray-700/50" style="min-height: 120px; max-height: 300px;">
                <template x-if="detailAd?.creative_type === 'banner' && detailAd?.image_url">
                    <img :src="detailAd.image_url" :alt="detailAd.advert_name" class="max-h-[300px] w-full object-contain">
                </template>
                <template x-if="detailAd?.creative_type === 'html'">
                    <iframe :srcdoc="detailAd?.html_snippet || ''" sandbox="allow-scripts allow-same-origin" class="pointer-events-none h-[250px] w-full border-0"></iframe>
                </template>
                <template x-if="detailAd?.creative_type === 'text'">
                    <p class="px-4 py-6 text-sm text-gray-700 dark:text-gray-300" x-text="detailAd?.advert_name"></p>
                </template>
            </div>
        </div>

        {{-- KPI row --}}
        <div class="grid grid-cols-3 divide-x divide-gray-200/60 border-b border-gray-200/60 dark:divide-gray-700/40 dark:border-gray-700/40">
            <div class="px-4 py-3 text-center">
                <p class="text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">EPC</p>
                <p class="font-mono text-lg tabular-nums text-cyan-600 dark:text-cyan-400" x-text="'$' + Number(detailAd?.epc || 0).toFixed(2)"></p>
            </div>
            <div class="px-4 py-3 text-center">
                <p class="text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Clicks</p>
                <p class="font-mono text-lg tabular-nums text-gray-900 dark:text-white" x-text="Number(detailAd?.clicks || 0).toLocaleString()"></p>
            </div>
            <div class="px-4 py-3 text-center">
                <p class="text-[0.6rem] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Revenue</p>
                <p class="font-mono text-lg tabular-nums text-gray-900 dark:text-white" x-text="'$' + Number(detailAd?.revenue || 0).toFixed(2)"></p>
            </div>
        </div>

        {{-- Metadata --}}
        <div class="space-y-0 divide-y divide-gray-100 px-5 dark:divide-gray-700/30">
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Network</span>
                <span class="text-xs text-gray-900 dark:text-white" x-text="detailAd?.network"></span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Advertiser</span>
                <span class="text-xs text-cyan-600 dark:text-cyan-400 cursor-pointer hover:underline"
                    x-text="detailAd?.advertiser_name"
                    @click="if (detailAd?.id) openAdvertiserPopup(detailAd.id)"></span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Creative Type</span>
                <span class="text-xs text-gray-900 dark:text-white capitalize" x-text="detailAd?.creative_type"></span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Dimensions</span>
                <span class="font-mono text-xs text-gray-900 dark:text-white" x-text="(detailAd?.width && detailAd?.height) ? detailAd.width + ' x ' + detailAd.height : 'N/A'"></span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Geo Region</span>
                <span class="text-xs text-gray-900 dark:text-white" x-text="detailAd?.geo_region || '(none)'"></span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Status</span>
                <span class="text-xs capitalize" :class="ads[detailAd?.id]?.approval_status === 'denied' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                    x-text="ads[detailAd?.id]?.approval_status"></span>
            </div>
            <div class="flex items-center justify-between py-2.5">
                <span class="text-xs text-gray-500 dark:text-gray-400">Last synced</span>
                <span class="font-mono text-[0.7rem] text-gray-500 dark:text-gray-400" x-text="detailAd?.last_synced_at"></span>
            </div>
        </div>

        {{-- URLs --}}
        <div class="space-y-0 divide-y divide-gray-100 border-t border-gray-200/60 px-5 dark:divide-gray-700/30 dark:border-gray-700/40">
            <template x-if="detailAd?.destination_url">
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">Destination</span>
                    <a :href="detailAd.destination_url" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-right text-xs text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 dark:hover:text-cyan-300 break-all">
                        <span x-text="detailAd.destination_url.length > 50 ? detailAd.destination_url.substring(0, 50) + '...' : detailAd.destination_url"></span>
                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </template>
            <template x-if="detailAd?.tracking_url">
                <div class="flex items-start justify-between gap-3 py-2.5">
                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">Tracking</span>
                    <span class="font-mono text-[0.6rem] text-gray-400 dark:text-gray-500 text-right break-all" x-text="detailAd.tracking_url.length > 60 ? detailAd.tracking_url.substring(0, 60) + '...' : detailAd.tracking_url"></span>
                </div>
            </template>
        </div>

        {{-- Weight override --}}
        <div class="border-t border-gray-200/60 px-5 py-3 dark:border-gray-700/40">
            <div class="flex items-center justify-between">
                <div>
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Weight Override</label>
                    <p class="text-[0.6rem] text-gray-400 dark:text-gray-500">
                        Inherits from advertiser: <span class="font-mono" x-text="detailAd?.advertiser_weight || '---'"></span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <select @change="saveWeight(detailAd.id, $event.target.value)"
                        :value="ads[detailAd?.id]?.weight_override || ''"
                        class="adv-weight-select border-gray-300 bg-gray-50 text-gray-900 focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Inherit</option>
                        <option value="2">2</option>
                        <option value="4">4</option>
                        <option value="6">6</option>
                        <option value="8">8</option>
                        <option value="10">10</option>
                    </select>
                    <svg x-show="savingWeight" x-cloak class="h-4 w-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Approve/Deny + Denial reason --}}
        <div class="border-t border-gray-200/60 px-5 py-3 dark:border-gray-700/40">
            <div class="flex items-center gap-2">
                <button @click="if (detailAd?.id) approve(detailAd.id)"
                    :disabled="!detailAd?.id || savingApproval[detailAd?.id]"
                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-medium transition-all"
                    :class="ads[detailAd?.id]?.approval_status === 'approved'
                        ? 'border-green-300 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400'
                        : 'border-gray-200 bg-white text-gray-500 hover:border-green-300 hover:bg-green-50 hover:text-green-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:border-green-700 dark:hover:bg-green-900/20 dark:hover:text-green-400'">
                    <svg x-show="!savingApproval[detailAd?.id]" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="savingApproval[detailAd?.id]" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Approve
                </button>
                <button @click="if (detailAd?.id) startDeny(detailAd.id)"
                    :disabled="!detailAd?.id || savingApproval[detailAd?.id]"
                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-medium transition-all"
                    :class="ads[detailAd?.id]?.approval_status === 'denied'
                        ? 'border-red-300 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400'
                        : 'border-gray-200 bg-white text-gray-500 hover:border-red-300 hover:bg-red-50 hover:text-red-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:border-red-700 dark:hover:bg-red-900/20 dark:hover:text-red-400'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Deny
                </button>
            </div>
            <template x-if="ads[detailAd?.id]?.approval_status === 'denied' && ads[detailAd?.id]?.approval_reason">
                <p class="mt-2 rounded bg-red-50 px-3 py-2 text-xs text-red-600 dark:bg-red-900/20 dark:text-red-400" x-text="ads[detailAd.id].approval_reason"></p>
            </template>
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
