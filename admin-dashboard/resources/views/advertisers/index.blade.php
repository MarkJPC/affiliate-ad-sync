@extends('layouts.app')

@section('title', 'Advertisers')

@section('content')
{{-- Google Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">

<div x-data="advertiserGrid()" x-cloak class="adv-grid font-body">

    {{-- Page header with texture --}}
    <div class="adv-header relative mb-3 overflow-hidden rounded-xl border border-gray-200/60 bg-white px-5 py-3 dark:border-gray-700/40 dark:bg-gray-800/80">
        <div class="adv-header-texture"></div>
        <div class="relative flex items-end justify-between">
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-[0.2em] text-cyan-600 dark:text-cyan-400">Affiliate Network</p>
                <h1 class="font-display text-2xl font-500 tracking-tight text-gray-900 dark:text-white">Advertiser Grid</h1>
                <p class="mt-2 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-mono text-xs tabular-nums">{{ number_format($advertisers->total()) }}</span> advertisers
                    <span class="inline-block h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    <span class="font-mono text-xs tabular-nums">{{ $sites->count() }}</span> sites
                    @if(request('rule') === 'default')
                        <span class="inline-block h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                            <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span>
                            Showing pending only
                        </span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Dirty indicator + Apply button --}}
                <div x-show="isDirty()" x-transition.opacity class="flex items-center gap-3">
                    <div class="flex items-center gap-2 rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-1.5 dark:border-cyan-800/50 dark:bg-cyan-900/20">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-cyan-500"></span>
                        </span>
                        <span class="font-mono text-xs font-medium text-cyan-700 dark:text-cyan-300" x-text="dirtyCount() + ' unsaved'"></span>
                    </div>
                    <button @click="discardChanges()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Discard
                    </button>
                    <button @click="showConfirmModal = true"
                        class="adv-btn-apply group inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-cyan-500/25 transition-all hover:shadow-cyan-500/40">
                        <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Apply Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="mb-2">
        @include('advertisers.partials.filter-bar')
    </div>

    {{-- Bulk action bar --}}
    <div class="mb-2">
        @include('advertisers.partials.bulk-action-bar')
    </div>

    {{-- Table or empty state --}}
    @if($advertisers->isEmpty())
        @include('advertisers.partials.empty-state')
    @else
        @include('advertisers.partials.table')
    @endif

    {{-- Modals --}}
    @include('advertisers.partials.detail-modal')
    @include('advertisers.partials.confirm-modal')
</div>

<script>
function advertiserGrid() {
    const initialWeights = @json($advertisers->getCollection()->pluck('default_weight', 'id')->map(fn ($v) => $v === null ? '' : (string) $v));
    const initialRules = @json(
        $advertisers->getCollection()->flatMap(function ($adv) use ($sites) {
            $map = [];
            foreach ($sites as $site) {
                $rule = $adv->rulesBySite->get($site->id);
                $key = $adv->id . '-' . $site->id;
                $map[$key] = $rule ? $rule->rule : null;
            }
            return $map;
        })
    );
    const advertiserNames = @json($advertisers->getCollection()->pluck('name', 'id'));
    const siteNames = @json($sites->pluck('name', 'id'));
    const pageIds = @json($advertisers->getCollection()->pluck('id'));

    return {
        selected: [],
        weights: { ...initialWeights },
        originalWeights: { ...initialWeights },
        dirtyWeights: {},
        rules: { ...initialRules },
        originalRules: { ...initialRules },
        dirtyRules: {},
        showDetailModal: false,
        showConfirmModal: false,
        detailData: {},
        bulkWeight: '',
        bulkRuleSite: '',
        bulkRuleValue: '',
        isApplying: false,
        applyReason: '',

        getAdvertiserName(id) { return advertiserNames[id] || `#${id}`; },
        getSiteName(id) { return siteNames[id] || `#${id}`; },

        toggleSelect(id) {
            const idx = this.selected.indexOf(id);
            idx > -1 ? this.selected.splice(idx, 1) : this.selected.push(id);
        },
        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selected = [...new Set([...this.selected, ...pageIds])];
            } else {
                this.selected = this.selected.filter(id => !pageIds.includes(id));
            }
        },
        allOnPageSelected() {
            return pageIds.length > 0 && pageIds.every(id => this.selected.includes(id));
        },
        selectAllMatching() { this.selected = ['all_matching']; },
        clearSelection() {
            this.selected = [];
            this.bulkWeight = '';
            this.bulkRuleSite = '';
            this.bulkRuleValue = '';
        },

        markWeightDirty(id) {
            const current = this.weights[id];
            const original = this.originalWeights[id];
            current !== original ? (this.dirtyWeights[id] = true) : delete this.dirtyWeights[id];
        },

        getRuleValue(advId, siteId) { return this.rules[`${advId}-${siteId}`]; },
        getRuleCellClass(advId, siteId) {
            const key = `${advId}-${siteId}`;
            const val = this.rules[key];
            const isDirty = this.dirtyRules[key];
            let c = 'adv-rule-cell ';
            switch (val) {
                case 'allowed': c += 'adv-rule-allowed'; break;
                case 'denied': c += 'adv-rule-denied'; break;
                case 'default': c += 'adv-rule-pending'; break;
                default: c += 'adv-rule-none'; break;
            }
            if (isDirty) c += ' adv-rule-dirty';
            return c;
        },
        cycleRule(advId, siteId) {
            const key = `${advId}-${siteId}`;
            const current = this.rules[key];
            let next;
            switch (current) {
                case null: case 'default': next = 'allowed'; break;
                case 'allowed': next = 'denied'; break;
                case 'denied': next = 'allowed'; break;
                default: next = 'allowed'; break;
            }
            this.rules[key] = next;
            next !== this.originalRules[key] ? (this.dirtyRules[key] = true) : delete this.dirtyRules[key];
        },

        isDirty() { return Object.keys(this.dirtyWeights).length > 0 || Object.keys(this.dirtyRules).length > 0; },
        dirtyCount() { return Object.keys(this.dirtyWeights).length + Object.keys(this.dirtyRules).length; },
        discardChanges() {
            this.weights = { ...this.originalWeights };
            this.rules = { ...this.originalRules };
            this.dirtyWeights = {};
            this.dirtyRules = {};
        },

        applyBulkWeight() {
            if (!this.bulkWeight) return;
            const val = this.bulkWeight === 'clear' ? '' : this.bulkWeight;
            const ids = this.selected.includes('all_matching') ? Object.keys(this.weights) : this.selected;
            ids.forEach(id => { this.weights[id] = val; this.markWeightDirty(id); });
            this.bulkWeight = '';
        },
        applyBulkRule() {
            if (!this.bulkRuleSite || !this.bulkRuleValue) return;
            const ids = this.selected.includes('all_matching') ? [...new Set(Object.keys(this.rules).map(k => k.split('-')[0]))] : this.selected;
            ids.forEach(id => {
                const key = `${id}-${this.bulkRuleSite}`;
                this.rules[key] = this.bulkRuleValue;
                this.bulkRuleValue !== this.originalRules[key] ? (this.dirtyRules[key] = true) : delete this.dirtyRules[key];
            });
            this.bulkRuleSite = '';
            this.bulkRuleValue = '';
        },

        openDetail(id, data) { this.detailData = data; this.showDetailModal = true; },

        async applyAllChanges() {
            this.isApplying = true;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' };
            try {
                const promises = [];
                const weightsByValue = {};
                for (const id of Object.keys(this.dirtyWeights)) {
                    const val = this.weights[id] || null;
                    const key = val === null ? 'null' : val;
                    if (!weightsByValue[key]) weightsByValue[key] = [];
                    weightsByValue[key].push(parseInt(id));
                }
                for (const [val, ids] of Object.entries(weightsByValue)) {
                    promises.push(fetch('{{ route("api.advertisers.bulk-weight") }}', {
                        method: 'POST', headers,
                        body: JSON.stringify({ advertiser_ids: ids, default_weight: val === 'null' ? null : val }),
                    }));
                }
                const ruleGroups = {};
                for (const key of Object.keys(this.dirtyRules)) {
                    const [advId, siteId] = key.split('-');
                    const rule = this.rules[key];
                    const groupKey = `${siteId}-${rule}`;
                    if (!ruleGroups[groupKey]) ruleGroups[groupKey] = { site_id: parseInt(siteId), rule, ids: [] };
                    ruleGroups[groupKey].ids.push(parseInt(advId));
                }
                for (const group of Object.values(ruleGroups)) {
                    promises.push(fetch('{{ route("api.advertisers.bulk-rules") }}', {
                        method: 'POST', headers,
                        body: JSON.stringify({ advertiser_ids: group.ids, site_id: group.site_id, rule: group.rule, reason: this.applyReason || null }),
                    }));
                }
                const results = await Promise.all(promises);
                if (results.every(r => r.ok)) {
                    window.location.reload();
                } else {
                    alert('Some changes failed to save. Please try again.');
                    this.isApplying = false;
                }
            } catch (error) {
                alert('An error occurred while saving changes.');
                this.isApplying = false;
            }
        },
    };
}
</script>

<style>
    [x-cloak] { display: none !important; }

    /* ── Typography ─────────────────────────────── */
    .font-display { font-family: 'Newsreader', Georgia, serif; }
    .font-body { font-family: 'DM Sans', system-ui, sans-serif; }
    .font-mono { font-family: 'JetBrains Mono', 'Fira Code', monospace; }

    /* ── Header texture ─────────────────────────── */
    .adv-header { position: relative; }
    .adv-header-texture {
        position: absolute; inset: 0; opacity: 0.03; pointer-events: none;
        background-image:
            radial-gradient(circle at 1px 1px, currentColor 0.5px, transparent 0.5px);
        background-size: 16px 16px;
    }
    .dark .adv-header-texture { opacity: 0.06; }

    /* ── Apply button gradient ──────────────────── */
    .adv-btn-apply {
        background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);
        transition: all 0.2s ease;
    }
    .adv-btn-apply:hover {
        background: linear-gradient(135deg, #0e7490, #0891b2, #06b6d4);
        transform: translateY(-1px);
    }

    /* ── Rule toggle cells ──────────────────────── */
    .adv-rule-cell {
        width: 1.75rem; height: 1.75rem;
        border-radius: 0.5rem;
        display: inline-flex; align-items: center; justify-content: center;
        transition: all 0.15s ease;
        cursor: pointer;
        position: relative;
    }
    .adv-rule-cell:active { transform: scale(0.92); }

    .adv-rule-allowed {
        background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        box-shadow: 0 0 0 1px rgba(34,197,94,0.2), inset 0 1px 0 rgba(255,255,255,0.5);
    }
    .dark .adv-rule-allowed {
        background: linear-gradient(135deg, rgba(34,197,94,0.15), rgba(34,197,94,0.25));
        box-shadow: 0 0 0 1px rgba(34,197,94,0.3), 0 0 12px rgba(34,197,94,0.1);
    }

    .adv-rule-denied {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        box-shadow: 0 0 0 1px rgba(239,68,68,0.2), inset 0 1px 0 rgba(255,255,255,0.5);
    }
    .dark .adv-rule-denied {
        background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(239,68,68,0.25));
        box-shadow: 0 0 0 1px rgba(239,68,68,0.3), 0 0 12px rgba(239,68,68,0.1);
    }

    .adv-rule-pending {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        box-shadow: 0 0 0 1px rgba(245,158,11,0.2), inset 0 1px 0 rgba(255,255,255,0.5);
    }
    .dark .adv-rule-pending {
        background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(245,158,11,0.25));
        box-shadow: 0 0 0 1px rgba(245,158,11,0.3), 0 0 12px rgba(245,158,11,0.08);
    }

    .adv-rule-none {
        background: transparent;
        box-shadow: 0 0 0 1px rgba(156,163,175,0.2);
    }
    .adv-rule-none:hover {
        background: rgba(156,163,175,0.08);
        box-shadow: 0 0 0 1px rgba(156,163,175,0.4);
    }
    .dark .adv-rule-none { box-shadow: 0 0 0 1px rgba(75,85,99,0.4); }
    .dark .adv-rule-none:hover { background: rgba(75,85,99,0.3); }

    .adv-rule-dirty {
        outline: 2px solid #22d3ee;
        outline-offset: 1px;
        animation: adv-dirty-pulse 2s ease-in-out infinite;
    }
    @keyframes adv-dirty-pulse {
        0%, 100% { outline-color: rgba(34,211,238,0.6); }
        50% { outline-color: rgba(34,211,238,1); }
    }

    /* ── Table styling ──────────────────────────── */
    .adv-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    .adv-table thead th {
        position: sticky; top: 0; z-index: 10;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .adv-table tbody tr {
        transition: background-color 0.1s ease;
    }

    /* ── Weight dropdown ────────────────────────── */
    .adv-weight-select {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.8rem;
        padding: 0.25rem 1.5rem 0.25rem 0.5rem;
        border-radius: 0.375rem;
        transition: all 0.15s ease;
    }
    .adv-weight-dirty {
        outline: 2px solid #22d3ee;
        outline-offset: 1px;
    }

    /* ── Network badges ─────────────────────────── */
    .adv-badge {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.65rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: 0.25rem;
    }

    /* ── Filter bar ─────────────────────────────── */
    .adv-filter-input {
        font-family: 'DM Sans', system-ui, sans-serif;
        font-size: 0.8125rem;
        border-radius: 0.5rem;
        transition: all 0.15s ease;
    }
    .adv-filter-input:focus {
        box-shadow: 0 0 0 2px rgba(6,182,212,0.2);
    }

    /* ── Duplicate group row ────────────────────── */
    .adv-duplicate-header {
        background: repeating-linear-gradient(
            -45deg,
            transparent,
            transparent 4px,
            rgba(245,158,11,0.04) 4px,
            rgba(245,158,11,0.04) 8px
        );
    }
    .dark .adv-duplicate-header {
        background: repeating-linear-gradient(
            -45deg,
            transparent,
            transparent 4px,
            rgba(245,158,11,0.06) 4px,
            rgba(245,158,11,0.06) 8px
        );
    }

    /* ── Glass modal backdrop ───────────────────── */
    .adv-modal-backdrop {
        backdrop-filter: blur(4px) saturate(0.8);
        -webkit-backdrop-filter: blur(4px) saturate(0.8);
    }
    .adv-modal-panel {
        box-shadow:
            0 25px 50px rgba(0,0,0,0.15),
            0 0 0 1px rgba(255,255,255,0.05),
            inset 0 1px 0 rgba(255,255,255,0.1);
    }
    .dark .adv-modal-panel {
        box-shadow:
            0 25px 50px rgba(0,0,0,0.5),
            0 0 0 1px rgba(255,255,255,0.05),
            inset 0 1px 0 rgba(255,255,255,0.05);
    }
    .adv-detail-hero {
        background: linear-gradient(135deg, rgba(6,182,212,0.03) 0%, transparent 60%);
    }
    .dark .adv-detail-hero {
        background: linear-gradient(135deg, rgba(6,182,212,0.06) 0%, transparent 60%);
    }

    /* ── Scrollbar ──────────────────────────────── */
    .adv-grid ::-webkit-scrollbar { height: 6px; width: 6px; }
    .adv-grid ::-webkit-scrollbar-track { background: transparent; }
    .adv-grid ::-webkit-scrollbar-thumb { background: rgba(156,163,175,0.3); border-radius: 3px; }
    .adv-grid ::-webkit-scrollbar-thumb:hover { background: rgba(156,163,175,0.5); }

    /* ── Bulk bar ───────────────────────────────── */
    .adv-bulk-bar {
        background: linear-gradient(135deg, rgba(8,145,178,0.08), rgba(6,182,212,0.04));
        border: 1px solid rgba(6,182,212,0.2);
    }
    .dark .adv-bulk-bar {
        background: linear-gradient(135deg, rgba(8,145,178,0.15), rgba(6,182,212,0.08));
        border: 1px solid rgba(6,182,212,0.25);
    }
</style>
@endsection
