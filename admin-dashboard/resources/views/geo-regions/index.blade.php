@extends('layouts.app')

@section('title', 'Geo Regions')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">

<div x-data="{ editingId: null, showAddForm: false, confirmDelete: null }" class="font-body max-w-4xl">

    {{-- Page header --}}
    <div class="relative mb-4 overflow-hidden rounded-xl border border-gray-200/60 bg-white px-5 py-3 dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="flex items-end justify-between">
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-[0.2em] text-cyan-600 dark:text-cyan-400">Settings</p>
                <h1 class="font-display text-2xl font-500 tracking-tight text-gray-900 dark:text-white" style="font-family: 'Newsreader', Georgia, serif;">Geo Regions</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage geographic regions for AdRotate geo-targeting. Each region maps country codes to an AdRotate PHP-serialized value.
                </p>
            </div>
            <button @click="showAddForm = !showAddForm"
                class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all"
                style="background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);"
                x-text="showAddForm ? 'Cancel' : 'Add Region'">
            </button>
        </div>
    </div>

    {{-- Success flash --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/20">
            <ul class="list-inside list-disc text-sm text-red-600 dark:text-red-400">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Add form (collapsible) --}}
    <div x-show="showAddForm" x-cloak x-transition class="mb-4 rounded-xl border border-cyan-200/60 bg-cyan-50/30 p-4 dark:border-cyan-800/40 dark:bg-cyan-900/10">
        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">New Region</h3>
        <form method="POST" action="{{ route('geo-regions.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    placeholder="e.g. North America"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Priority</label>
                <input type="number" name="priority" value="{{ old('priority', 0) }}" required min="0"
                    placeholder="0 = highest"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Country Codes (CSV)</label>
                <input type="text" name="country_codes" value="{{ old('country_codes') }}" required
                    placeholder="US,CA"
                    class="w-full rounded-lg border-gray-300 font-mono text-sm uppercase focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">AdRotate Value</label>
                <input type="text" name="adrotate_value" value="{{ old('adrotate_value') }}" required
                    placeholder='a:2:{i:0;s:2:"US";i:1;s:2:"CA";}'
                    class="w-full rounded-lg border-gray-300 font-mono text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="sm:col-span-2">
                <button type="submit"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all"
                    style="background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);">
                    Create Region
                </button>
            </div>
        </form>
    </div>

    {{-- Regions table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200/60 bg-white dark:border-gray-700/40 dark:bg-gray-800/80">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-600 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-2.5">Name</th>
                    <th class="px-4 py-2.5 w-20">Priority</th>
                    <th class="px-4 py-2.5">Country Codes</th>
                    <th class="px-4 py-2.5">AdRotate Value</th>
                    <th class="px-4 py-2.5 w-28 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @forelse($regions as $region)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        {{-- View mode --}}
                        <template x-if="editingId !== {{ $region->id }}">
                            <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">{{ $region->name }}</td>
                        </template>
                        <template x-if="editingId !== {{ $region->id }}">
                            <td class="px-4 py-2.5 font-mono text-gray-600 dark:text-gray-400">{{ $region->priority }}</td>
                        </template>
                        <template x-if="editingId !== {{ $region->id }}">
                            <td class="px-4 py-2.5">
                                <div class="flex flex-wrap gap-1">
                                    @foreach(explode(',', $region->country_codes) as $code)
                                        <span class="inline-flex rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ trim($code) }}</span>
                                    @endforeach
                                </div>
                            </td>
                        </template>
                        <template x-if="editingId !== {{ $region->id }}">
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-500 dark:text-gray-400">
                                <span title="{{ $region->adrotate_value }}">{{ Str::limit($region->adrotate_value, 40) }}</span>
                            </td>
                        </template>
                        <template x-if="editingId !== {{ $region->id }}">
                            <td class="px-4 py-2.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="editingId = {{ $region->id }}"
                                        class="text-xs font-medium text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 dark:hover:text-cyan-300">
                                        Edit
                                    </button>
                                    <button @click="confirmDelete = {{ $region->id }}"
                                        class="text-xs font-medium text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </template>

                        {{-- Edit mode --}}
                        <template x-if="editingId === {{ $region->id }}">
                            <td colspan="5" class="px-4 py-3">
                                <form method="POST" action="{{ route('geo-regions.update', $region) }}" class="grid grid-cols-1 gap-2 sm:grid-cols-5 sm:items-end">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="mb-1 block text-[0.65rem] font-medium text-gray-500">Name</label>
                                        <input type="text" name="name" value="{{ $region->name }}" required
                                            class="w-full rounded border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[0.65rem] font-medium text-gray-500">Priority</label>
                                        <input type="number" name="priority" value="{{ $region->priority }}" required min="0"
                                            class="w-full rounded border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[0.65rem] font-medium text-gray-500">Country Codes</label>
                                        <input type="text" name="country_codes" value="{{ $region->country_codes }}" required
                                            class="w-full rounded border-gray-300 font-mono text-sm uppercase focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[0.65rem] font-medium text-gray-500">AdRotate Value</label>
                                        <input type="text" name="adrotate_value" value="{{ $region->adrotate_value }}" required
                                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <button type="submit"
                                            class="rounded px-3 py-1.5 text-xs font-semibold text-white"
                                            style="background: linear-gradient(135deg, #0891b2, #06b6d4);">
                                            Save
                                        </button>
                                        <button type="button" @click="editingId = null"
                                            class="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </template>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            No geo regions configured. Click "Add Region" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Delete confirmation modal --}}
    <div x-show="confirmDelete" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="backdrop-filter: blur(4px);"
        @click.self="confirmDelete = null"
        @keydown.escape.window="confirmDelete = null">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Region?</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This will permanently remove this geo region. Existing ads will keep their current geo_countries values until the next sync.</p>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="confirmDelete = null"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </button>
                @foreach($regions as $region)
                    <form x-show="confirmDelete === {{ $region->id }}" method="POST" action="{{ route('geo-regions.destroy', $region) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Delete
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
    .font-display { font-family: 'Newsreader', Georgia, serif; }
    .font-body { font-family: 'DM Sans', system-ui, sans-serif; }
    .font-mono { font-family: 'JetBrains Mono', 'Fira Code', monospace; }
    [x-cloak] { display: none !important; }
</style>
@endsection
