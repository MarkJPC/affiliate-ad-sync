<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Affiliate Ad Sync</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <style>
        @keyframes dot-bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        .dark #page-loader { background: #111827; }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    {{-- Initial page load overlay --}}
    <div id="page-loader" style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:#f9fafb;transition:opacity .4s ease;">
        <div style="display:flex;flex-direction:column;align-items:center;gap:10px;">
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="width:10px;height:10px;border-radius:50%;background:#06b6d4;animation:dot-bounce 1.4s infinite ease-in-out both;animation-delay:-0.32s;"></span>
                <span style="width:10px;height:10px;border-radius:50%;background:#06b6d4;animation:dot-bounce 1.4s infinite ease-in-out both;animation-delay:-0.16s;"></span>
                <span style="width:10px;height:10px;border-radius:50%;background:#06b6d4;animation:dot-bounce 1.4s infinite ease-in-out both;"></span>
            </div>
            <span style="font-size:13px;font-weight:500;color:#9ca3af;font-family:system-ui,sans-serif;">Loading...</span>
        </div>
    </div>

    {{-- Page transition progress bar --}}
    <div id="nav-progress" style="position:fixed;top:0;left:0;height:2px;z-index:60;opacity:0;width:0;background:linear-gradient(90deg,#06b6d4,#22d3ee,#06b6d4);background-size:200% 100%;transition:width 8s cubic-bezier(0.1,0.5,0.3,1),opacity .3s ease;"></div>
    @include('components.header')
    @include('components.sidebar')

    {{-- Unsaved changes navigation guard modal --}}
    <div id="nav-guard-modal" style="display:none;z-index:9998;" class="fixed inset-0 flex items-center justify-center bg-black/50 p-4" onclick="if(event.target===this)window.__navGuard.close()">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Unsaved Changes</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">You have unsaved changes that will be lost if you leave this page.</p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button onclick="window.__navGuard.close()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Stay
                </button>
                <button onclick="window.__navGuard.discardAndLeave()"
                    class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                    Discard &amp; Leave
                </button>
                <button id="nav-guard-save-btn" onclick="window.__navGuard.saveAndLeave()"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all disabled:opacity-50"
                    style="background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);">
                    Save &amp; Leave
                </button>
            </div>
        </div>
    </div>

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
    @livewireScripts
    <script>
        // Dismiss initial page loader
        window.addEventListener('load', function() {
            var loader = document.getElementById('page-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(function() { loader.remove(); }, 400);
            }
        });

        // ── Dirty-state navigation guard ──────────────────────
        // Pages register callbacks via window.__dirtyGuard
        window.__dirtyGuard = {
            isDirty: function() { return false; },
            save: async function() {},
            discard: function() {},
        };

        window.__navGuard = (function() {
            var modal = document.getElementById('nav-guard-modal');
            var saveBtn = document.getElementById('nav-guard-save-btn');
            var pendingUrl = null;
            var pendingLivewire = false;

            function isInternalNav(link) {
                var href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:')) return false;
                if (link.target === '_blank' || link.hasAttribute('download')) return false;
                try {
                    var url = new URL(href, window.location.origin);
                    return url.origin === window.location.origin;
                } catch(e) { return false; }
            }

            function show(url, isLivewire) {
                pendingUrl = url;
                pendingLivewire = !!isLivewire;
                saveBtn.textContent = 'Save & Leave';
                saveBtn.disabled = false;
                modal.style.display = '';
            }

            function close() {
                modal.style.display = 'none';
                pendingUrl = null;
                pendingLivewire = false;
            }

            function navigate() {
                if (pendingUrl) {
                    window.location.href = pendingUrl;
                }
            }

            return {
                show: show,
                close: close,
                isOpen: function() { return modal.style.display !== 'none'; },

                discardAndLeave: function() {
                    try { window.__dirtyGuard.discard(); } catch(e) {}
                    if (pendingLivewire) {
                        close();
                        // User can now re-click the Livewire action
                        return;
                    }
                    navigate();
                },

                saveAndLeave: async function() {
                    saveBtn.textContent = 'Saving…';
                    saveBtn.disabled = true;
                    try {
                        await window.__dirtyGuard.save();
                        if (pendingLivewire) {
                            close();
                            return;
                        }
                        // Small delay to let save complete
                        setTimeout(function() { navigate(); }, 100);
                    } catch(e) {
                        saveBtn.textContent = 'Save & Leave';
                        saveBtn.disabled = false;
                    }
                },

                // Called by Livewire commit hook
                showForLivewire: function() {
                    show(null, true);
                }
            };
        })();

        // ── Click interceptor for <a> links ──────────────────
        // Also handles progress bar
        (function() {
            var bar = document.getElementById('nav-progress');

            document.addEventListener('click', function(e) {
                var link = e.target.closest('a[href]');
                if (!link) return;
                if (e.ctrlKey || e.metaKey || e.shiftKey) return;

                var href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:')) return;
                if (link.target === '_blank' || link.hasAttribute('download')) return;

                // Only internal links
                var isInternal = false;
                try {
                    var url = new URL(href, window.location.origin);
                    isInternal = url.origin === window.location.origin;
                } catch(err) { return; }

                if (!isInternal) return;

                // Check dirty guard before allowing navigation
                if (window.__dirtyGuard.isDirty()) {
                    e.preventDefault();
                    window.__navGuard.show(href, false);
                    return;
                }

                // Start progress bar
                if (bar) {
                    bar.style.transition = 'none';
                    bar.style.width = '0';
                    bar.style.opacity = '1';
                    bar.offsetWidth;
                    bar.style.transition = 'width 8s cubic-bezier(0.1,0.5,0.3,1), opacity .3s ease';
                    bar.style.width = '90%';
                }
            });

            // beforeunload: native browser prompt for dirty state + progress bar finish
            window.addEventListener('beforeunload', function(e) {
                if (bar) {
                    bar.style.transition = 'width .2s ease, opacity .4s ease .2s';
                    bar.style.width = '100%';
                    bar.style.opacity = '0';
                }

                if (window.__dirtyGuard.isDirty()) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Close modal on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && window.__navGuard.isOpen()) {
                    window.__navGuard.close();
                }
            });
        })();
    </script>
</body>
</html>
