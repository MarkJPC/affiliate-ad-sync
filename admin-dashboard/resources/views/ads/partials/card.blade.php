{{-- Ad Card — 3 view sizes × 3 creative types --}}
<div data-ad-id="{{ $ad->id }}" class="group relative overflow-hidden rounded-lg border border-gray-200/60 bg-white transition-all hover:shadow-md dark:border-gray-700/40 dark:bg-gray-800/80"
    :class="{
        'ring-2 ring-cyan-500/50': selected.includes({{ $ad->id }}),
        'opacity-60': ads[{{ $ad->id }}]?.approval_status === 'denied'
    }">

    {{-- Top bar: checkbox + badges --}}
    <div class="flex items-center justify-between px-2 pt-1.5 pb-1">
        <input type="checkbox" :checked="selected.includes({{ $ad->id }})"
            x-on:change="toggleSelect({{ $ad->id }})"
            class="h-3.5 w-3.5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700">
        <div class="flex items-center gap-1">
            {{-- Broken image badge --}}
            <span x-show="brokenImages[{{ $ad->id }}]" x-cloak
                class="inline-flex items-center gap-0.5 rounded bg-amber-100 px-1 py-0.5 text-[0.55rem] font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                Broken
            </span>
            {{-- Network badge --}}
            <span class="ad-net-badge ad-net-{{ $ad->network }}">{{ $ad->network }}</span>
        </div>
    </div>

    {{-- Creative preview --}}
    <div class="relative cursor-pointer px-2" x-on:click="openDetail({{ $ad->id }})">
        <div class="flex items-center justify-center overflow-hidden rounded bg-gray-50 dark:bg-gray-700/50"
            :class="{
                'h-[140px]': viewSize === 'large',
                'h-[100px]': viewSize === 'medium',
                'h-[64px]': viewSize === 'small'
            }">
            @if($ad->creative_type === 'banner' && $ad->image_url)
                {{-- Banner image --}}
                <template x-if="!brokenImages[{{ $ad->id }}]">
                    <img src="{{ $ad->image_url }}" alt="{{ e($ad->advert_name) }}"
                        loading="lazy"
                        class="h-full w-full object-contain"
                        x-on:error="markBrokenImage({{ $ad->id }})">
                </template>
                <template x-if="brokenImages[{{ $ad->id }}]">
                    <div class="flex flex-col items-center justify-center gap-0.5 text-gray-400 dark:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 002.25-2.25V5.25a2.25 2.25 0 00-2.25-2.25H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                        <span class="text-[0.55rem]">Unavailable</span>
                    </div>
                </template>
            @elseif($ad->creative_type === 'html' && $ad->image_url)
                {{-- HTML with real image — render in sandboxed iframe --}}
                <iframe srcdoc="{{ e($ad->html_snippet ?? $ad->bannercode ?? '') }}"
                    sandbox="allow-scripts allow-same-origin"
                    class="pointer-events-none h-full w-full border-0"
                    loading="lazy"></iframe>
            @elseif($ad->image_url && $ad->bannercode && str_contains($ad->bannercode, '<img'))
                {{-- Bannercode with real image — render in sandboxed iframe --}}
                <iframe srcdoc="{{ e($ad->html_snippet ?? $ad->bannercode ?? '') }}"
                    sandbox="allow-scripts allow-same-origin"
                    class="pointer-events-none h-full w-full border-0"
                    loading="lazy"></iframe>
            @else
                {{-- Text ad — extract readable text from bannercode --}}
                @php
                    $textContent = $ad->bannercode ? strip_tags($ad->bannercode) : $ad->advert_name;
                @endphp
                <div class="flex items-center justify-center px-2">
                    <p class="text-center text-[0.7rem] leading-tight text-gray-500 dark:text-gray-400"
                        :class="viewSize === 'small' ? 'line-clamp-2' : 'line-clamp-3'">{{ $textContent }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Card body --}}
    <div class="px-2 pb-2 pt-1.5">
        {{-- Ad name --}}
        <p class="text-xs font-medium leading-tight text-gray-900 dark:text-white cursor-pointer hover:text-cyan-600 dark:hover:text-cyan-400"
            :class="viewSize === 'large' ? 'line-clamp-2' : 'line-clamp-1'"
            x-on:click="openDetail({{ $ad->id }})"
            title="{{ e($ad->advert_name) }}">{{ $ad->advert_name }}</p>

        {{-- Medium/Large only: extra metadata --}}
        <template x-if="viewSize !== 'small'">
            <div>
                <p class="mt-0.5 text-[0.65rem] text-gray-500 dark:text-gray-400 line-clamp-1 cursor-pointer hover:text-cyan-600 dark:hover:text-cyan-400"
                    x-on:click.stop="openAdvertiserPopup({{ $ad->id }})">{{ $ad->advertiser?->name ?? 'Unknown' }}</p>
                <div class="mt-1 flex items-center gap-1 text-[0.65rem] text-gray-400 dark:text-gray-500">
                    @if($ad->width && $ad->height)
                        <span class="font-mono">{{ $ad->width }}&times;{{ $ad->height }}</span>
                        <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                    @endif
                    <span class="font-mono text-cyan-600 dark:text-cyan-400">@if(($ad->epc ?? 0) == 0 && in_array($ad->network, ['impact', 'awin']))&mdash;@else${{ number_format($ad->epc ?? 0, 2) }}@endif</span>
                </div>
            </div>
        </template>

        {{-- Approve / Deny buttons --}}
        <div class="mt-1.5 flex items-center gap-1">
            <button x-on:click="approve({{ $ad->id }})"
                :disabled="savingApproval[{{ $ad->id }}]"
                class="ad-btn-approve"
                :class="ads[{{ $ad->id }}]?.approval_status === 'approved' ? 'ad-btn-active-approve' : 'ad-btn-inactive'">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span x-show="viewSize !== 'small'" class="text-[0.65rem]">Approve</span>
            </button>
            <button x-on:click="denyImmediate({{ $ad->id }})"
                :disabled="savingApproval[{{ $ad->id }}]"
                class="ad-btn-deny"
                :class="ads[{{ $ad->id }}]?.approval_status === 'denied' ? 'ad-btn-active-deny' : 'ad-btn-inactive'">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                <span x-show="viewSize !== 'small'" class="text-[0.65rem]">Deny</span>
            </button>
            <svg x-show="savingApproval[{{ $ad->id }}]" x-cloak class="ml-auto h-3 w-3 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        {{-- Denial reason (medium/large only) --}}
        <template x-if="viewSize !== 'small' && ads[{{ $ad->id }}]?.approval_status === 'denied' && ads[{{ $ad->id }}]?.approval_reason">
            <p class="mt-1 rounded bg-red-50 px-1.5 py-0.5 text-[0.6rem] text-red-600 dark:bg-red-900/20 dark:text-red-400 line-clamp-2" x-text="ads[{{ $ad->id }}].approval_reason"></p>
        </template>
    </div>
</div>
