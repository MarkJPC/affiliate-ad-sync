@extends('layouts.app')

@section('title', 'Export History')

@section('content')
<div class="max-w-6xl space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Export History</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Audit trail of generated CSV exports.
            </p>
        </div>
        <a href="{{ route('export.index') }}"
            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Back to Export
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Site</th>
                        <th class="px-4 py-3">Filename</th>
                        <th class="px-4 py-3">Rows Exported</th>
                        <th class="px-4 py-3">Exported By</th>
                        <th class="px-4 py-3">Exported At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exports as $export)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-4 py-3">{{ $export->site?->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $export->filename }}</td>
                            <td class="px-4 py-3">{{ $export->ads_exported }}</td>
                            <td class="px-4 py-3">{{ $export->exported_by ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $export->exported_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No export history yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $exports->links() }}
    </div>
</div>
@endsection
