@extends('layouts.app')

@section('title', 'Sites & Placements')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">

<div x-data="sitesManager()" class="font-body space-y-0">

    {{-- ===== Section A: Sites Management ===== --}}
    <div class="max-w-4xl">
        <div class="relative mb-4 overflow-hidden rounded-xl border border-gray-200/60 bg-white px-5 py-3 dark:border-gray-700/40 dark:bg-gray-800/80">
            <div class="flex items-end justify-between">
                <div>
                    <p class="mb-1 text-xs font-medium uppercase tracking-[0.2em] text-cyan-600 dark:text-cyan-400">Manage</p>
                    <h1 class="font-display text-2xl font-500 tracking-tight text-gray-900 dark:text-white" style="font-family: 'Newsreader', Georgia, serif;">Sites</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        WordPress sites that receive ad placements. Active sites appear as columns in the grid below.
                    </p>
                </div>
                <button @click="openAdd()"
                    class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all"
                    style="background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);">
                    Add Site
                </button>
            </div>
        </div>

        {{-- Sites list --}}
        <div class="overflow-hidden rounded-xl border border-gray-200/60 bg-white dark:border-gray-700/40 dark:bg-gray-800/80">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-600 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2.5">Name</th>
                        <th class="px-4 py-2.5">Domain</th>
                        <th class="px-4 py-2.5">WordPress URL</th>
                        <th class="px-4 py-2.5 w-20">Status</th>
                        <th class="px-4 py-2.5 w-28 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse($allSites as $site)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">{{ $site->name }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-600 dark:text-gray-400">{{ $site->domain }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $site->wordpress_url ?: '—' }}</td>
                            <td class="px-4 py-2.5">
                                @if($site->is_active)
                                    <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEdit({{ $site->id }}, {{ Js::from($site->only(['name', 'domain', 'wordpress_url', 'is_active'])) }})"
                                        class="text-xs font-medium text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 dark:hover:text-cyan-300">
                                        Edit
                                    </button>
                                    <button @click="confirmDelete = {{ $site->id }}"
                                        class="text-xs font-medium text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No sites configured. Click "Add Site" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Section Divider ===== --}}
    <div class="relative my-8">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="bg-gray-50 px-3 text-xs font-medium uppercase tracking-widest text-gray-400 dark:bg-gray-900 dark:text-gray-500">Placements</span>
        </div>
    </div>

    {{-- ===== Section B: Placements Grid ===== --}}
    <div>
        <div class="relative mb-4 overflow-hidden rounded-xl border border-gray-200/60 bg-white px-5 py-3 dark:border-gray-700/40 dark:bg-gray-800/80">
            <div class="flex items-end justify-between">
                <div>
                    <p class="mb-1 text-xs font-medium uppercase tracking-[0.2em] text-cyan-600 dark:text-cyan-400">Overview</p>
                    <h2 class="font-display text-2xl font-500 tracking-tight text-gray-900 dark:text-white" style="font-family: 'Newsreader', Georgia, serif;">Placements Grid</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Rows are ad sizes, columns are active sites. Cells show AdRotate group IDs (editable inline).
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Dirty indicator + Apply/Discard buttons --}}
                    <template x-if="isPlacementDirty()">
                        <div class="flex items-center gap-2" x-transition.opacity>
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-75"></span>
                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-cyan-500"></span>
                            </span>
                            <span class="text-xs font-medium text-cyan-600 dark:text-cyan-400"
                                x-text="placementDirtyCount() + ' unsaved'"></span>

                            <button @click="discardPlacementChanges()"
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                Discard
                            </button>
                            <button @click="showPlacementConfirmModal = true"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white shadow-sm"
                                style="background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);">
                                Apply Changes
                            </button>
                        </div>
                    </template>

                    <button @click="showSizeModal = true; sizeForm = { width: '', height: '' }; sizeError = '';"
                        class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all"
                        style="background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);">
                        Add Ad Size
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200/60 bg-white dark:border-gray-700/40 dark:bg-gray-800/80">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-600 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
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
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse($sizes as $size)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $size['label'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <button @click="openEditSize({{ $size['width'] }}, {{ $size['height'] }})"
                                            class="text-xs font-medium text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 dark:hover:text-cyan-300">
                                            Edit
                                        </button>
                                        <button @click="confirmDeleteSize = { width: {{ $size['width'] }}, height: {{ $size['height'] }}, label: '{{ $size['label'] }}' }"
                                            class="text-xs font-medium text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>

                            @foreach($sites as $site)
                                @php
                                    $key = $site->id . '-' . $size['width'] . 'x' . $size['height'];
                                    $placement = $placementsByKey->get($key);
                                @endphp

                                <td class="px-3 py-2 text-center">
                                    @if($placement)
                                            <input
                                                type="number"
                                                placeholder="—"
                                                :value="placements[{{ $placement->id }}]?.adrotate_group_id"
                                                @change="updatePlacementGroup({{ $placement->id }}, $event.target.value)"
                                                :class="[
                                                    dirtyPlacements[{{ $placement->id }}] ? 'ring-2 ring-cyan-400' : '',
                                                    placements[{{ $placement->id }}]?.adrotate_group_id
                                                        ? 'border-solid border-green-400 bg-green-50 dark:bg-green-900/20 dark:border-green-600'
                                                        : 'border-dashed border-gray-300 bg-gray-50/80 dark:border-gray-600 dark:bg-gray-700/50'
                                                ]"
                                                class="w-16 rounded border text-xs text-center transition-colors focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 focus:bg-white dark:text-white dark:focus:bg-gray-800"
                                            />
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

    {{-- ===== Placement Confirm Modal ===== --}}
    <div x-show="showPlacementConfirmModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="backdrop-filter: blur(4px);"
        @click.self="showPlacementConfirmModal = false"
        @keydown.escape.window="showPlacementConfirmModal = false">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Apply Placement Changes?</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                The following <span x-text="placementDirtyCount()" class="font-semibold text-cyan-600 dark:text-cyan-400"></span> placement(s) will be updated:
            </p>

            <div class="mt-3 max-h-60 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
                <ul class="divide-y divide-gray-200 text-sm dark:divide-gray-600">
                    <template x-for="id in Object.keys(dirtyPlacements)" :key="id">
                        <li class="px-3 py-2">
                            <div class="font-medium text-gray-900 dark:text-white" x-text="placementMeta[id] || ('Placement #' + id)"></div>
                            <div class="mt-0.5 flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <template x-if="String(originalPlacements[id]?.adrotate_group_id ?? '') !== String(placements[id]?.adrotate_group_id ?? '')">
                                    <span>
                                        Group: <span class="line-through" x-text="originalPlacements[id]?.adrotate_group_id ?? '—'"></span>
                                        &rarr; <span class="font-medium text-cyan-600 dark:text-cyan-400" x-text="placements[id]?.adrotate_group_id || '—'"></span>
                                    </span>
                                </template>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button @click="showPlacementConfirmModal = false"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button @click="applyPlacementChanges()" :disabled="isApplyingPlacements"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all disabled:opacity-50"
                    style="background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);">
                    <span x-text="isApplyingPlacements ? 'Applying…' : 'Apply'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Add/Edit Site Modal ===== --}}
    <div x-show="showModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="backdrop-filter: blur(4px);"
        @click.self="showModal = false"
        @keydown.escape.window="showModal = false">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                x-text="editId ? 'Edit Site' : 'Add Site'"></h3>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" x-model="form.name" required
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="e.g. The Part Shops">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Domain</label>
                    <input type="text" x-model="form.domain" required
                        class="w-full rounded-lg border-gray-300 font-mono text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="e.g. thepartshops.com">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">WordPress URL</label>
                    <input type="text" x-model="form.wordpress_url"
                        class="w-full rounded-lg border-gray-300 font-mono text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="e.g. https://thepartshops.com/wp-admin">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" x-model="form.is_active" id="site-active"
                        class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700">
                    <label for="site-active" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
                </div>
            </div>

            <div x-show="error" x-cloak class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400" x-text="error"></div>

            <div class="mt-5 flex justify-end gap-2">
                <button @click="showModal = false"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button @click="save()" :disabled="saving"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all disabled:opacity-50"
                    style="background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);">
                    <span x-text="saving ? 'Saving…' : (editId ? 'Update' : 'Create')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Delete Confirmation Modal ===== --}}
    <div x-show="confirmDelete" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="backdrop-filter: blur(4px);"
        @click.self="confirmDelete = null"
        @keydown.escape.window="confirmDelete = null">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Site?</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This will permanently remove this site and all its placements. This action cannot be undone.</p>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="confirmDelete = null"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button @click="deleteSite()" :disabled="saving"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">
                    Delete
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Edit Ad Size Modal ===== --}}
    <div x-show="showEditSizeModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="backdrop-filter: blur(4px);"
        @click.self="showEditSizeModal = false"
        @keydown.escape.window="showEditSizeModal = false">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Ad Size</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Current size: <span class="font-medium text-gray-700 dark:text-gray-300" x-text="editSizeForm.old_width + 'x' + editSizeForm.old_height"></span>
            </p>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">New Width (px)</label>
                    <input type="number" x-model="editSizeForm.new_width" min="1" required
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">New Height (px)</label>
                    <input type="number" x-model="editSizeForm.new_height" min="1" required
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div x-show="editSizeError" x-cloak class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400" x-text="editSizeError"></div>

            <div class="mt-5 flex justify-end gap-2">
                <button @click="showEditSizeModal = false"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button @click="saveEditSize()"
                    :disabled="saving || (editSizeForm.new_width == editSizeForm.old_width && editSizeForm.new_height == editSizeForm.old_height)"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all disabled:opacity-50"
                    style="background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);">
                    <span x-text="saving ? 'Saving…' : 'Update'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Delete Ad Size Confirmation Modal ===== --}}
    <div x-show="confirmDeleteSize" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="backdrop-filter: blur(4px);"
        @click.self="confirmDeleteSize = null"
        @keydown.escape.window="confirmDeleteSize = null">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Ad Size?</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                This will permanently delete all placements for size
                <span class="font-semibold text-gray-700 dark:text-gray-300" x-text="confirmDeleteSize?.label"></span>
                across all sites. This action cannot be undone.
            </p>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="confirmDeleteSize = null"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button @click="deleteSize()" :disabled="saving"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">
                    Delete
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Add Ad Size Modal ===== --}}
    <div x-show="showSizeModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        style="backdrop-filter: blur(4px);"
        @click.self="showSizeModal = false"
        @keydown.escape.window="showSizeModal = false">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Ad Size</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Creates a placement for every active site at this dimension.</p>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Width (px)</label>
                    <input type="number" x-model="sizeForm.width" min="1" required
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="e.g. 300">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Height (px)</label>
                    <input type="number" x-model="sizeForm.height" min="1" required
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="e.g. 250">
                </div>
            </div>

            <div x-show="sizeError" x-cloak class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400" x-text="sizeError"></div>

            <div class="mt-5 flex justify-end gap-2">
                <button @click="showSizeModal = false"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button @click="saveSize()" :disabled="saving"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all disabled:opacity-50"
                    style="background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);">
                    <span x-text="saving ? 'Creating…' : 'Create'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function sitesManager() {
    const placementData = @json($placementsByKey->mapWithKeys(fn ($p) => [$p->id => ['is_active' => (bool) $p->is_active, 'adrotate_group_id' => $p->adrotate_group_id]]));
    const metaData = @json($placementsByKey->mapWithKeys(fn ($p) => [$p->id => $p->name . ' (' . $p->width . 'x' . $p->height . ')']));

    return {
        // Section A: Sites Management
        showModal: false,
        editId: null,
        confirmDelete: null,
        saving: false,
        error: '',
        form: { name: '', domain: '', wordpress_url: '', is_active: true },
        showSizeModal: false,
        sizeForm: { width: '', height: '' },
        sizeError: '',
        showEditSizeModal: false,
        editSizeForm: { old_width: '', old_height: '', new_width: '', new_height: '' },
        editSizeError: '',
        confirmDeleteSize: null,

        // Section B: Placements Grid — batch state
        placements: JSON.parse(JSON.stringify(placementData)),
        originalPlacements: JSON.parse(JSON.stringify(placementData)),
        placementMeta: metaData,
        dirtyPlacements: {},
        showPlacementConfirmModal: false,
        isApplyingPlacements: false,

        // --- Sites ---
        openAdd() {
            this.editId = null;
            this.form = { name: '', domain: '', wordpress_url: '', is_active: true };
            this.error = '';
            this.showModal = true;
        },

        openEdit(id, data) {
            this.editId = id;
            this.form = { ...data, wordpress_url: data.wordpress_url || '' };
            this.error = '';
            this.showModal = true;
        },

        async save() {
            this.saving = true;
            this.error = '';
            try {
                const url = this.editId ? `/api/sites/${this.editId}` : '/api/sites';
                const method = this.editId ? 'PATCH' : 'POST';
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (!res.ok) {
                    const msgs = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Failed to save');
                    this.error = msgs;
                    return;
                }
                window.location.reload();
            } catch (e) {
                this.error = 'Network error. Please try again.';
            } finally {
                this.saving = false;
            }
        },

        async saveSize() {
            this.saving = true;
            this.sizeError = '';
            try {
                const res = await fetch('/api/placements/add-size', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        width: parseInt(this.sizeForm.width),
                        height: parseInt(this.sizeForm.height)
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    const msgs = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Failed to create');
                    this.sizeError = msgs;
                    return;
                }
                window.location.reload();
            } catch (e) {
                this.sizeError = 'Network error. Please try again.';
            } finally {
                this.saving = false;
            }
        },

        openEditSize(width, height) {
            this.editSizeForm = { old_width: width, old_height: height, new_width: width, new_height: height };
            this.editSizeError = '';
            this.showEditSizeModal = true;
        },

        async saveEditSize() {
            this.saving = true;
            this.editSizeError = '';
            try {
                const res = await fetch('/api/placements/update-size', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        old_width: parseInt(this.editSizeForm.old_width),
                        old_height: parseInt(this.editSizeForm.old_height),
                        new_width: parseInt(this.editSizeForm.new_width),
                        new_height: parseInt(this.editSizeForm.new_height)
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    const msgs = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Failed to update');
                    this.editSizeError = msgs;
                    return;
                }
                window.location.reload();
            } catch (e) {
                this.editSizeError = 'Network error. Please try again.';
            } finally {
                this.saving = false;
            }
        },

        async deleteSize() {
            this.saving = true;
            try {
                const res = await fetch('/api/placements/delete-size', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        width: this.confirmDeleteSize.width,
                        height: this.confirmDeleteSize.height
                    })
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    alert('Failed to delete size');
                }
            } catch (e) {
                alert('Network error');
            } finally {
                this.saving = false;
            }
        },

        async deleteSite() {
            this.saving = true;
            try {
                const res = await fetch(`/api/sites/${this.confirmDelete}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) { alert('Failed to delete'); return; }
                window.location.reload();
            } catch (e) {
                alert('Network error');
            } finally {
                this.saving = false;
            }
        },

        // --- Placements: dirty tracking ---
        updatePlacementGroup(id, value) {
            const parsed = value === '' ? null : parseInt(value);
            this.placements[id].adrotate_group_id = parsed;
            this.placements[id].is_active = !!parsed;
            this._checkPlacementDirty(id);
        },

        _checkPlacementDirty(id) {
            const cur = this.placements[id];
            const orig = this.originalPlacements[id];
            const isDirty = cur.is_active !== orig.is_active ||
                String(cur.adrotate_group_id ?? '') !== String(orig.adrotate_group_id ?? '');
            if (isDirty) {
                this.dirtyPlacements[id] = true;
            } else {
                delete this.dirtyPlacements[id];
            }
        },

        isPlacementDirty() {
            return Object.keys(this.dirtyPlacements).length > 0;
        },

        placementDirtyCount() {
            return Object.keys(this.dirtyPlacements).length;
        },

        discardPlacementChanges() {
            this.placements = JSON.parse(JSON.stringify(this.originalPlacements));
            this.dirtyPlacements = {};
        },

        async applyPlacementChanges() {
            this.isApplyingPlacements = true;
            const changes = Object.keys(this.dirtyPlacements).map(id => ({
                id: parseInt(id),
                is_active: this.placements[id].is_active,
                adrotate_group_id: this.placements[id].adrotate_group_id,
            }));
            try {
                const res = await fetch('/api/placements/bulk-update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ changes })
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    alert('Failed to apply changes');
                    this.isApplyingPlacements = false;
                    this.showPlacementConfirmModal = false;
                }
            } catch (e) {
                alert('Network error');
                this.isApplyingPlacements = false;
                this.showPlacementConfirmModal = false;
            }
        }
    };
}
</script>

<style>
    .font-display { font-family: 'Newsreader', Georgia, serif; }
    .font-body { font-family: 'DM Sans', system-ui, sans-serif; }
    .font-mono { font-family: 'JetBrains Mono', 'Fira Code', monospace; }
    [x-cloak] { display: none !important; }

</style>
@endsection
