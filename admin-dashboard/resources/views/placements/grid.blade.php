@extends('layouts.app')

@section('title', 'Placements Grid')

@section('content')
<div class="space-y-4">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Placements / Ad Sizes</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Rows are ad sizes, columns are sites, and cells show AdRotate group IDs.
        </p>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-3 py-2">Ad Size</th>
                    @foreach($sites as $site)
                        <th class="px-3 py-2 text-center">
                            <div class="font-medium">{{ $site->name }}</div>
                            <div class="text-[10px] normal-case text-gray-400">{{ $site->domain }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($sizes as $size)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">
                            {{ $size['label'] }}
                        </td>

                        @foreach($sites as $site)
                            @php
                                $key = $site->id . '-' . $size['width'] . 'x' . $size['height'];
                                $placement = $placementsByKey->get($key);
                            @endphp

                            <td class="px-3 py-2 text-center">
                                @if($placement)
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="inline-flex min-w-[40px] justify-center rounded bg-blue-100 px-2 py-1 font-mono text-xs text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">

                                        <input
                                            type="number"
                                            value="{{ $placement->adrotate_group_id }}"
                                            class="w-16 rounded border-gray-300 text-xs text-center dark:bg-gray-700 dark:text-white"
                                            onchange="updateGroup({{ $placement->id }}, this.value)"
                                        />

                                        <span class="text-[10px] {{ $placement->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                            {{ $placement->is_active ? 'active' : 'inactive' }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 1 + $sites->count() }}" class="px-3 py-6 text-center text-sm text-gray-500">
                            No placement data found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function updateGroup(placementId, value) {

    fetch(`/api/placements/${placementId}/group`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            adrotate_group_id: value
        })
    })
    .then(r => r.json())
    .then(data => {
        console.log('saved', data)
    })
    .catch(err => {
        console.error(err)
        alert('Failed to save')
    })

}
</script>
@endsection