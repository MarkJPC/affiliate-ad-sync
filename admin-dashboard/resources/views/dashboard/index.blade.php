@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Overview of your affiliate ad system</p>
</div>

{{-- Stat cards --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Advertisers</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_advertisers']) }}</p>
            </div>
            <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/30">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
        </div>
        @if($stats['pending_rules'] > 0)
            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">{{ $stats['pending_rules'] }} pending rules</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Ads</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_ads']) }}</p>
            </div>
            <div class="rounded-lg bg-green-100 p-3 dark:bg-green-900/30">
                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        </div>
        @if($stats['denied_ads'] > 0)
            <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $stats['denied_ads'] }} denied</p>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ads by Network</p>
                <div class="mt-1 flex flex-wrap gap-2">
                    @forelse($adsByNetwork as $network => $count)
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            @switch($network)
                                @case('flexoffers') bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 @break
                                @case('awin') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 @break
                                @case('cj') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 @break
                                @case('impact') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 @break
                            @endswitch
                        ">{{ $network }}: {{ number_format($count) }}</span>
                    @empty
                        <span class="text-sm text-gray-400">No ads yet</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed Syncs (24h)</p>
                <p class="mt-1 text-2xl font-bold {{ $stats['failed_syncs_24h'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $stats['failed_syncs_24h'] }}</p>
            </div>
            <div class="rounded-lg {{ $stats['failed_syncs_24h'] > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-700' }} p-3">
                <svg class="h-6 w-6 {{ $stats['failed_syncs_24h'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
        </div>
    </div>
</div>

{{-- Quick actions --}}
<div class="mb-6 flex flex-wrap gap-3">
    <a href="{{ route('advertisers.index') }}?rule=default" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700">
        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Review Pending Advertisers
    </a>
    <a href="{{ route('export.index') }}" class="inline-flex items-center rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-700">
        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Export Ads
    </a>
    @if($stats['failed_syncs_24h'] > 0)
        <a href="{{ route('sync-logs.index') }}?status=failed" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            View Failed Syncs
        </a>
    @endif
</div>

{{-- Recent activity --}}
<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    {{-- Recent syncs --}}
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Syncs</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Network</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Ads</th>
                        <th class="px-4 py-3">Started</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSyncs as $sync)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $sync->network }}</td>
                            <td class="px-4 py-3">
                                @switch($sync->status)
                                    @case('success')
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">Success</span>
                                        @break
                                    @case('failed')
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">Failed</span>
                                        @break
                                    @case('running')
                                        <span class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">Running</span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-4 py-3">{{ $sync->ads_synced }}</td>
                            <td class="px-4 py-3">{{ $sync->started_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">No syncs yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent exports --}}
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Exports</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Site</th>
                        <th class="px-4 py-3">Filename</th>
                        <th class="px-4 py-3">Ads</th>
                        <th class="px-4 py-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentExports as $export)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $export->site?->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3">{{ $export->filename }}</td>
                            <td class="px-4 py-3">{{ $export->ads_exported }}</td>
                            <td class="px-4 py-3">{{ $export->exported_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">No exports yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
