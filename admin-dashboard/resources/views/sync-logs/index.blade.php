@extends('layouts.app')

@section('title', 'Sync Logs')

@section('content')
{{-- Google Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">

<div x-data="syncLogPage()" x-cloak class="sync-logs font-body">
    @livewire('sync-log-grid')

    {{-- Error detail modal --}}
    <div x-show="errorModal.open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sl-modal-backdrop bg-black/40"
        @keydown.escape.window="closeError()">
        <div x-show="errorModal.open" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            @click.outside="closeError()"
            class="sl-modal-panel w-full max-w-2xl rounded-xl bg-white dark:bg-gray-800 shadow-2xl overflow-hidden">
            {{-- Modal header --}}
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                <h3 class="font-display text-lg font-500 text-gray-900 dark:text-white">Error Details</h3>
                <div class="flex items-center gap-2">
                    <button @click="copyError()" class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        <span x-text="errorModal.copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                    <button @click="closeError()" class="rounded-md p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            {{-- Modal body --}}
            <div class="max-h-[60vh] overflow-auto p-5">
                <pre class="whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-4 font-mono text-sm text-gray-800 dark:bg-gray-900 dark:text-gray-200" x-text="errorModal.text"></pre>
            </div>
        </div>
    </div>

    {{-- Toast notification --}}
    <div x-show="toast.show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-4 opacity-0"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 rounded-lg px-4 py-2.5 text-sm font-medium shadow-lg"
        :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        x-text="toast.message">
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    /* -- Typography -------------------------------- */
    .font-display { font-family: 'Newsreader', Georgia, serif; }
    .font-body { font-family: 'DM Sans', system-ui, sans-serif; }
    .font-mono { font-family: 'JetBrains Mono', 'Fira Code', monospace; }

    /* -- Header texture ---------------------------- */
    .sl-header { position: relative; }
    .sl-header-texture {
        position: absolute; inset: 0; opacity: 0.03; pointer-events: none;
        background-image: radial-gradient(circle at 1px 1px, currentColor 0.5px, transparent 0.5px);
        background-size: 16px 16px;
    }
    .dark .sl-header-texture { opacity: 0.06; }

    /* -- CTA button -------------------------------- */
    .sl-btn-primary {
        background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);
        transition: all 0.15s ease;
    }
    .sl-btn-primary:hover {
        background: linear-gradient(135deg, #0e7490, #0891b2, #06b6d4);
    }

    /* -- Network badges ---------------------------- */
    .sl-net-flexoffers { background: #f3e8ff; color: #7e22ce; }
    .sl-net-awin { background: #dbeafe; color: #1d4ed8; }
    .sl-net-cj { background: #d1fae5; color: #059669; }
    .sl-net-impact { background: #ffedd5; color: #c2410c; }
    .dark .sl-net-flexoffers { background: rgba(126,34,206,0.15); color: #c084fc; }
    .dark .sl-net-awin { background: rgba(29,78,216,0.15); color: #93c5fd; }
    .dark .sl-net-cj { background: rgba(5,150,105,0.15); color: #6ee7b7; }
    .dark .sl-net-impact { background: rgba(194,65,12,0.15); color: #fdba74; }

    /* -- Filter inputs ----------------------------- */
    .sl-filter-input {
        font-family: 'DM Sans', system-ui, sans-serif;
        font-size: 0.75rem;
        border-radius: 0.375rem;
        transition: all 0.15s ease;
    }
    .sl-filter-input:focus {
        box-shadow: 0 0 0 2px rgba(6,182,212,0.2);
    }

    /* -- Modal ------------------------------------- */
    .sl-modal-backdrop {
        backdrop-filter: blur(4px) saturate(0.8);
        -webkit-backdrop-filter: blur(4px) saturate(0.8);
    }
    .sl-modal-panel {
        box-shadow: 0 20px 40px rgba(0,0,0,0.12), 0 0 0 1px rgba(255,255,255,0.05);
    }
    .dark .sl-modal-panel {
        box-shadow: 0 20px 40px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
    }

    /* -- Table row flash animation ----------------- */
    @keyframes row-enter {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .sl-row-enter { animation: row-enter 0.2s ease-out both; }

    /* -- Scrollbar --------------------------------- */
    .sync-logs ::-webkit-scrollbar { height: 5px; width: 5px; }
    .sync-logs ::-webkit-scrollbar-track { background: transparent; }
    .sync-logs ::-webkit-scrollbar-thumb { background: rgba(156,163,175,0.25); border-radius: 3px; }
</style>

<script>
function syncLogPage() {
    return {
        errorModal: { open: false, text: '', copied: false },
        toast: { show: false, message: '', type: 'success' },
        syncing: false,

        openError(text) {
            this.errorModal = { open: true, text: text || 'No error details available.', copied: false };
        },
        closeError() {
            this.errorModal.open = false;
        },
        async copyError() {
            try {
                await navigator.clipboard.writeText(this.errorModal.text);
                this.errorModal.copied = true;
                setTimeout(() => { this.errorModal.copied = false; }, 2000);
            } catch (e) {
                console.error('Copy failed:', e);
            }
        },

        async triggerSync() {
            if (this.syncing) return;
            this.syncing = true;
            try {
                const res = await fetch('/api/sync/trigger', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (res.ok) {
                    this.showToast(data.message || 'Sync triggered!', 'success');
                } else {
                    this.showToast(data.error || 'Failed to trigger sync', 'error');
                }
            } catch (e) {
                this.showToast('Network error — could not trigger sync', 'error');
            }
            this.syncing = false;
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },
    };
}
</script>
@endsection
