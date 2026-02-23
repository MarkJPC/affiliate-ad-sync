@php
    $sortUrl = function ($column) use ($sort, $dir) {
        $newDir = ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $column, 'dir' => $newDir]);
    };
    $sortIcon = function ($column) use ($sort, $dir) {
        if ($sort !== $column) return '';
        return $dir === 'asc' ? '&#9650;' : '&#9660;';
    };
@endphp

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
        <thead class="bg-gray-50 text-[0.65rem] uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th class="w-10 px-2 py-1.5">
                    <input type="checkbox"
                        @change="toggleSelectAll($event)"
                        :checked="allOnPageSelected()"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                </th>
                <th class="px-2 py-1.5">
                    <a href="{{ $sortUrl('name') }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                        Name {!! $sortIcon('name') !!}
                    </a>
                </th>
                <th class="px-2 py-1.5">
                    <a href="{{ $sortUrl('network') }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                        Network {!! $sortIcon('network') !!}
                    </a>
                </th>
                <th class="px-2 py-1.5">
                    <a href="{{ $sortUrl('epc') }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                        EPC {!! $sortIcon('epc') !!}
                    </a>
                </th>
                <th class="px-2 py-1.5">
                    <a href="{{ $sortUrl('commission_rate') }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                        Commission {!! $sortIcon('commission_rate') !!}
                    </a>
                </th>
                <th class="px-2 py-1.5">
                    <a href="{{ $sortUrl('default_weight') }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">
                        Weight {!! $sortIcon('default_weight') !!}
                    </a>
                </th>
                @foreach($sites as $site)
                    <th class="px-1 py-1.5 text-center" title="{{ $site->domain }}">
                        <span class="block max-w-[60px] truncate">{{ $site->name }}</span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $lastDuplicateGroup = null; @endphp
            @foreach($advertisers as $advertiser)
                @php
                    $lowerName = \Illuminate\Support\Str::lower($advertiser->name);
                    $isDuplicate = in_array($lowerName, $duplicateNames);
                @endphp

                {{-- Duplicate group header --}}
                @if($isDuplicate && $lastDuplicateGroup !== $lowerName)
                    @php
                        $groupCount = $advertisers->getCollection()->filter(fn ($a) => \Illuminate\Support\Str::lower($a->name) === $lowerName)->count();
                        $lastDuplicateGroup = $lowerName;
                    @endphp
                    <tr class="border-b bg-amber-50 dark:border-gray-700 dark:bg-amber-900/20">
                        <td colspan="{{ 6 + $sites->count() }}" class="px-2 py-1">
                            <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">
                                Duplicate: {{ $advertiser->name }} ({{ $groupCount }} networks)
                            </span>
                        </td>
                    </tr>
                @endif

                @include('advertisers.partials.table-row', ['advertiser' => $advertiser, 'sites' => $sites, 'duplicateNames' => $duplicateNames])
            @endforeach
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="mt-4">
    {{ $advertisers->links() }}
</div>
