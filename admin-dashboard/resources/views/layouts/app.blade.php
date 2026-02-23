<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Affiliate Ad Sync</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    @include('components.header')
    @include('components.sidebar')

    <main class="ml-64 pt-16">
        {{-- Failed syncs banner --}}
        @if(isset($failedSyncsCount) && $failedSyncsCount > 0)
            <div class="border-b border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/20">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <span class="text-sm font-medium text-red-600 dark:text-red-400">
                        {{ $failedSyncsCount }} failed sync{{ $failedSyncsCount > 1 ? 's' : '' }} in the last 24 hours.
                        <a href="{{ route('sync-logs.index') }}?status=failed" class="underline hover:no-underline">View details</a>
                    </span>
                </div>
            </div>
        @endif

        <div class="p-6">
            @yield('content')
        </div>
    </main>
</body>
</html>
