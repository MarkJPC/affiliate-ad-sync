@extends('layouts.app')

@section('title', 'Export CSV')

@section('content')
<div x-data="exportPage()" class="max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Export CSV</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Generate export CSV files and review recent export activity.
        </p>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Download CSV Card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Download CSV</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Exports are generated from the current filter contract and logged to export history.
            </p>

            <form method="POST" action="{{ route('export.download') }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label for="site_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Site</label>
                    <select id="site_id" name="site_id" required x-model="siteId"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->domain }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="export_type" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Export Type</label>
                    <select id="export_type" name="export_type" x-model="exportType"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="banner">Banner</option>
                        <option value="text">Text</option>
                    </select>
                </div>

                <div>
                    <label for="network" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Network (optional)</label>
                    <select id="network" name="network" x-model="network"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">All networks</option>
                        <option value="flexoffers">flexoffers</option>
                        <option value="awin">awin</option>
                        <option value="cj">cj</option>
                        <option value="impact">impact</option>
                    </select>
                </div>

                <div>
                    <label for="advertiser_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Advertiser (optional)</label>
                    <select id="advertiser_id" name="advertiser_id" x-model="advertiserId"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">All advertisers</option>
                        <template x-for="adv in filteredAdvertisers" :key="adv.id">
                            <option :value="adv.id" x-text="adv.name + ' (' + adv.network + ')'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label for="search" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Search (optional)</label>
                    <input id="search" name="search" type="text" x-model="search" placeholder="Search ad name or advertiser..."
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div x-show="exportType === 'banner'">
                    <label for="dimensions" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Dimensions (optional)</label>
                    <select id="dimensions" name="dimensions" x-model="dimensions"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">All dimensions</option>
                        @foreach($activeDimensions as $dim)
                            <option value="{{ $dim }}">{{ $dim }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="exportType === 'banner'" class="flex items-center gap-2">
                    <input id="active_sizes_only" name="active_sizes_only" type="checkbox" value="1" x-model="activeSizesOnly"
                        class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700">
                    <label for="active_sizes_only" class="text-sm text-gray-700 dark:text-gray-300">Active sizes only</label>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700">
                        Download CSV
                    </button>
                    <button type="button" @click="fetchPreview()"
                        :disabled="!siteId || loading"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        <template x-if="loading">
                            <svg class="mr-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        Preview
                    </button>
                </div>
            </form>
        </div>

        {{-- Preview Panel --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Preview</h2>

            {{-- Empty state --}}
            <template x-if="!preview && !loading && !error">
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Select a site and click "Preview" to see a summary before downloading.
                </p>
            </template>

            {{-- Loading --}}
            <template x-if="loading">
                <div class="mt-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Loading preview...
                </div>
            </template>

            {{-- Error --}}
            <template x-if="error">
                <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400" x-text="error"></div>
            </template>

            {{-- Preview data --}}
            <template x-if="preview && !loading">
                <div class="mt-4 space-y-4">
                    {{-- Total rows --}}
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white" x-text="preview.summary.total_rows"></span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">rows matched</span>
                    </div>

                    {{-- Network breakdown --}}
                    <div>
                        <h3 class="mb-1 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">By Network</h3>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="[net, count] in Object.entries(preview.summary.grouped_by_network)" :key="net">
                                <span class="inline-flex items-center rounded-full bg-cyan-100 px-2.5 py-0.5 text-xs font-medium text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300">
                                    <span x-text="net"></span>:&nbsp;<span x-text="count"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- Dimensions breakdown (banner only) --}}
                    <template x-if="Object.keys(preview.summary.grouped_by_dimensions || {}).length > 0">
                        <div>
                            <h3 class="mb-1 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">By Dimensions</h3>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="[dim, count] in Object.entries(preview.summary.grouped_by_dimensions)" :key="dim">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        <span x-text="dim"></span>:&nbsp;<span x-text="count"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Sample rows --}}
                    <div x-show="preview.sample_rows.length > 0">
                        <h3 class="mb-1 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                            Sample Rows (first <span x-text="preview.sample_rows.length"></span>)
                        </h3>
                        <div class="max-h-64 overflow-auto rounded border border-gray-200 dark:border-gray-600">
                            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                                <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-2 py-1">Ad Name</th>
                                        <th class="px-2 py-1">Network</th>
                                        <th class="px-2 py-1">Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, i) in preview.sample_rows" :key="i">
                                        <tr class="border-t border-gray-200 dark:border-gray-600">
                                            <td class="px-2 py-1 max-w-[200px] truncate" x-text="row.advert_name || row.advertiser_name || '—'"></td>
                                            <td class="px-2 py-1" x-text="row.network || '—'"></td>
                                            <td class="px-2 py-1" x-text="row.width && row.height ? row.width + 'x' + row.height : '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Empty result with diagnostics --}}
                    <template x-if="preview.summary.total_rows === 0">
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">No rows match the current filters.</p>
                            <template x-if="preview.diagnostics && preview.diagnostics.messages">
                                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-800 dark:bg-amber-900/20">
                                    <h4 class="mb-1 text-xs font-semibold uppercase text-amber-700 dark:text-amber-400">Diagnostics</h4>
                                    <ul class="list-inside list-disc space-y-0.5 text-sm text-amber-700 dark:text-amber-300">
                                        <template x-for="(msg, i) in preview.diagnostics.messages" :key="i">
                                            <li x-text="msg"></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Recent Export Activity --}}
    <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Export Activity</h2>
            <a href="{{ route('export.history') }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-700 dark:text-cyan-400">
                View full history
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-2">Site</th>
                        <th class="px-3 py-2">Filename</th>
                        <th class="px-3 py-2">Rows</th>
                        <th class="px-3 py-2">Exported At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentExports as $export)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-3 py-2">{{ $export->site?->name ?? 'Unknown' }}</td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $export->filename }}</td>
                            <td class="px-3 py-2">{{ $export->ads_exported }}</td>
                            <td class="px-3 py-2">{{ $export->exported_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                No exports recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportPage() {
    return {
        siteId: '',
        exportType: 'banner',
        network: '',
        advertiserId: '',
        search: '',
        dimensions: '',
        activeSizesOnly: true,
        loading: false,
        error: null,
        preview: null,
        advertisers: @json($advertisers),

        get filteredAdvertisers() {
            if (!this.network) return this.advertisers;
            return this.advertisers.filter(a => a.network === this.network);
        },

        async fetchPreview() {
            if (!this.siteId) return;
            this.loading = true;
            this.error = null;
            this.preview = null;

            const params = new URLSearchParams();
            params.set('site_id', this.siteId);
            params.set('export_type', this.exportType);
            if (this.network) params.set('network', this.network);
            if (this.advertiserId) params.set('advertiser_id', this.advertiserId);
            if (this.search) params.set('search', this.search);
            if (this.exportType === 'banner') {
                if (this.dimensions) params.set('dimensions', this.dimensions);
                params.set('active_sizes_only', this.activeSizesOnly ? '1' : '0');
            }

            try {
                const res = await fetch(`{{ route('api.export.preview') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) {
                    const body = await res.json().catch(() => null);
                    throw new Error(body?.message || `HTTP ${res.status}`);
                }
                const data = await res.json();
                this.preview = data;
            } catch (e) {
                this.error = e.message || 'Failed to load preview.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
