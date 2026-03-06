@php
    $isDuplicate = in_array(\Illuminate\Support\Str::lower($advertiser->name), $duplicateNames);
    $isInactive = !$advertiser->is_active;
@endphp
<tr class="{{ $isInactive ? 'opacity-50' : '' }} {{ $isDuplicate ? 'bg-amber-50/50 dark:bg-amber-900/10' : '' }} border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"
    :class="{ 'bg-blue-50 dark:bg-blue-900/20': selected.includes({{ $advertiser->id }}) }">

    {{-- Checkbox --}}
    <td class="w-10 px-2 py-1">
        <input type="checkbox" value="{{ $advertiser->id }}"
            :checked="selected.includes({{ $advertiser->id }})"
            @change="toggleSelect({{ $advertiser->id }})"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
    </td>

    {{-- Advertiser name --}}
    <td class="px-2 py-1 {{ $isDuplicate && !$loop->first ? 'pl-8' : '' }}">
        <button type="button"
            @click="openDetail({{ $advertiser->id }}, {
                name: '{{ addslashes($advertiser->name) }}',
                network: '{{ $advertiser->network }}',
                website_url: '{{ addslashes($advertiser->website_url ?? '') }}',
                category: '{{ addslashes($advertiser->category ?? 'N/A') }}',
                ads_count: {{ $advertiser->ads_count }},
                total_clicks: {{ $advertiser->total_clicks }},
                total_revenue: {{ $advertiser->total_revenue }},
                epc: {{ $advertiser->epc }},
                commission_rate: '{{ addslashes($advertiser->commission_rate ?? 'N/A') }}',
                last_synced_at: '{{ $advertiser->last_synced_at ?? 'Never' }}',
                is_active: {{ $advertiser->is_active ? 'true' : 'false' }},
                country_code: '{{ $advertiser->country_code ?? '' }}',
                region_name: '{{ addslashes($advertiser->region_name ?? '') }}'
            })"
            class="text-left text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-300">
            {{ $advertiser->name }}
        </button>
        @if($isInactive)
            <span class="ml-1 inline-flex rounded-full bg-gray-100 px-1.5 py-0.5 text-[0.65rem] text-gray-500 dark:bg-gray-700 dark:text-gray-400">inactive</span>
        @endif
    </td>

    {{-- Network badge --}}
    <td class="px-2 py-1">
        <span class="inline-flex rounded-full px-1.5 py-0.5 text-[0.65rem] font-medium
            @switch($advertiser->network)
                @case('flexoffers') bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 @break
                @case('awin') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 @break
                @case('cj') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 @break
                @case('impact') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 @break
            @endswitch
        ">{{ $advertiser->network }}</span>
    </td>

    {{-- EPC --}}
    <td class="px-2 py-1 font-mono text-xs text-gray-700 dark:text-gray-300">${{ number_format($advertiser->epc, 2) }}</td>

    {{-- Commission rate --}}
    <td class="px-2 py-1 text-xs text-gray-700 dark:text-gray-300">{{ $advertiser->commission_rate ?? '---' }}</td>

    {{-- Country (inline editable) --}}
    <td class="px-2 py-1">
        <div x-data="{ editing: false, value: '{{ $advertiser->country_code ?? '' }}', saving: false }" class="inline-flex items-center gap-1">
            <template x-if="!editing">
                <button @click="editing = true; $nextTick(() => $refs.countryInput{{ $advertiser->id }}.focus())"
                    class="inline-flex rounded px-1.5 py-0.5 font-mono text-[0.7rem] font-medium transition-colors hover:bg-gray-100 dark:hover:bg-gray-700"
                    :class="value ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500'"
                    x-text="value || '---'"></button>
            </template>
            <template x-if="editing">
                <div class="flex items-center gap-1">
                    <input x-ref="countryInput{{ $advertiser->id }}" type="text" x-model="value" maxlength="2"
                        @blur="editing = false; if (value !== '{{ $advertiser->country_code ?? '' }}') { saving = true; fetch('{{ route('api.advertisers.country-code.update', $advertiser) }}', { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: JSON.stringify({ country_code: value || null }) }).then(r => r.json()).then(d => { saving = false; if (d.region_name !== undefined) { $dispatch('country-updated', { id: {{ $advertiser->id }}, region: d.region_name }); } }).catch(() => { saving = false; }); }"
                        @keydown.enter="$el.blur()"
                        @keydown.escape="value = '{{ $advertiser->country_code ?? '' }}'; editing = false"
                        class="w-10 rounded border-gray-300 px-1 py-0.5 font-mono text-[0.7rem] uppercase focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <svg x-show="saving" x-cloak class="h-3 w-3 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>
            </template>
        </div>
    </td>

    {{-- Region (read-only) --}}
    <td class="px-2 py-1"
        x-data="{ region: '{{ addslashes($advertiser->region_name ?? '') }}' }"
        @country-updated.window="if ($event.detail.id === {{ $advertiser->id }}) region = $event.detail.region || ''">
        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="region || '---'"></span>
    </td>

    {{-- Weight dropdown --}}
    <td class="px-2 py-1">
        <select
            x-model="weights[{{ $advertiser->id }}]"
            @change="markWeightDirty({{ $advertiser->id }})"
            class="rounded-md border-gray-300 py-0.5 pl-1.5 pr-6 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            :class="{ 'ring-2 ring-blue-400': dirtyWeights[{{ $advertiser->id }}] }">
            <option value="">---</option>
            @foreach([2, 4, 6, 8, 10] as $w)
                <option value="{{ $w }}">{{ $w }}</option>
            @endforeach
        </select>
    </td>

    {{-- Site rule cells --}}
    @foreach($sites as $site)
        @php
            $rule = $advertiser->rulesBySite->get($site->id);
            $ruleValue = $rule ? $rule->rule : null;
        @endphp
        <td class="px-1 py-1 text-center">
            <button type="button"
                @click="cycleRule({{ $advertiser->id }}, {{ $site->id }})"
                class="inline-flex h-7 w-7 items-center justify-center rounded-md text-sm transition-colors"
                :class="getRuleCellClass({{ $advertiser->id }}, {{ $site->id }})"
                :disabled="!isEligible({{ $advertiser->id }}, {{ $site->id }})"
                :title="!isEligible({{ $advertiser->id }}, {{ $site->id }}) ? 'Not available — this network doesn\'t serve this site' : ''">
                <template x-if="!isEligible({{ $advertiser->id }}, {{ $site->id }})">
                    <svg class="h-3 w-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </template>
                <template x-if="isEligible({{ $advertiser->id }}, {{ $site->id }}) && getRuleValue({{ $advertiser->id }}, {{ $site->id }}) === 'allowed'">
                    <svg class="h-3.5 w-3.5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="isEligible({{ $advertiser->id }}, {{ $site->id }}) && getRuleValue({{ $advertiser->id }}, {{ $site->id }}) === 'denied'">
                    <svg class="h-3.5 w-3.5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </template>
                <template x-if="isEligible({{ $advertiser->id }}, {{ $site->id }}) && getRuleValue({{ $advertiser->id }}, {{ $site->id }}) === 'default'">
                    <svg class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="isEligible({{ $advertiser->id }}, {{ $site->id }}) && !getRuleValue({{ $advertiser->id }}, {{ $site->id }})">
                    <span class="text-xs text-gray-400">---</span>
                </template>
            </button>
        </td>
    @endforeach
</tr>
