@extends('layouts.app')

@section('title', 'Export CSV')

@section('content')
<div class="max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Export CSV</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Export endpoints are wired. This page is the base scaffold for preview and download flows.
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
        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Download Scaffold CSV</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                This currently downloads a placeholder CSV to confirm routing and request validation.
            </p>

            <form method="POST" action="{{ route('export.download') }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label for="site_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Site</label>
                    <select id="site_id" name="site_id" required
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->domain }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="export_type" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Export Type</label>
                    <select id="export_type" name="export_type"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="banner">Banner</option>
                        <option value="text">Text</option>
                    </select>
                </div>

                <button type="submit"
                    class="inline-flex items-center rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700">
                    Download Placeholder CSV
                </button>
            </form>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Preview Endpoint</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Use this to validate preview route wiring while preview data logic is being built.
            </p>

            <div class="mt-4 rounded-md bg-gray-50 p-3 font-mono text-xs text-gray-700 dark:bg-gray-700/50 dark:text-gray-200">
                GET {{ route('api.export.preview') }}?site_id=&lt;id&gt;&amp;export_type=banner
            </div>

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Endpoint currently returns scaffold metadata and validated request context.
            </p>
        </div>
    </div>

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
@endsection
