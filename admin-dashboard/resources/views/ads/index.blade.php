@extends('layouts.app')

@section('title', 'Ad Review')

@section('content')
{{-- Google Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">

@livewire('ad-grid')

<style>
    [x-cloak] { display: none !important; }

    /* -- Typography -------------------------------- */
    .font-display { font-family: 'Newsreader', Georgia, serif; }
    .font-body { font-family: 'DM Sans', system-ui, sans-serif; }
    .font-mono { font-family: 'JetBrains Mono', 'Fira Code', monospace; }

    /* -- Header texture ---------------------------- */
    .adv-header { position: relative; }
    .adv-header-texture {
        position: absolute; inset: 0; opacity: 0.03; pointer-events: none;
        background-image: radial-gradient(circle at 1px 1px, currentColor 0.5px, transparent 0.5px);
        background-size: 16px 16px;
    }
    .dark .adv-header-texture { opacity: 0.06; }

    /* -- Apply/CTA button -------------------------- */
    .adv-btn-apply {
        background: linear-gradient(135deg, #0891b2, #06b6d4, #22d3ee);
        transition: all 0.15s ease;
    }
    .adv-btn-apply:hover {
        background: linear-gradient(135deg, #0e7490, #0891b2, #06b6d4);
    }

    /* -- Network badges (card) --------------------- */
    .ad-net-badge {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.55rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-weight: 600;
        padding: 0.1rem 0.4rem;
        border-radius: 0.2rem;
        line-height: 1.4;
    }
    .ad-net-flexoffers { background: #f3e8ff; color: #7e22ce; }
    .ad-net-awin { background: #dbeafe; color: #1d4ed8; }
    .ad-net-cj { background: #d1fae5; color: #059669; }
    .ad-net-impact { background: #ffedd5; color: #c2410c; }
    .dark .ad-net-flexoffers { background: rgba(126,34,206,0.15); color: #c084fc; }
    .dark .ad-net-awin { background: rgba(29,78,216,0.15); color: #93c5fd; }
    .dark .ad-net-cj { background: rgba(5,150,105,0.15); color: #6ee7b7; }
    .dark .ad-net-impact { background: rgba(194,65,12,0.15); color: #fdba74; }

    /* -- Filter bar badges (larger) ---------------- */
    .adv-badge {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.6rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        font-weight: 600;
        padding: 0.15rem 0.5rem;
        border-radius: 0.2rem;
    }

    /* -- Filter inputs ----------------------------- */
    .adv-filter-input {
        font-family: 'DM Sans', system-ui, sans-serif;
        font-size: 0.75rem;
        border-radius: 0.375rem;
        transition: all 0.15s ease;
    }
    .adv-filter-input:focus {
        box-shadow: 0 0 0 2px rgba(6,182,212,0.2);
    }

    /* -- Card approve/deny buttons ----------------- */
    .ad-btn-approve, .ad-btn-deny {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        padding: 0.2rem 0.5rem;
        border-radius: 0.3rem;
        border: 1px solid;
        font-weight: 500;
        transition: all 0.12s ease;
        cursor: pointer;
        line-height: 1;
    }
    .ad-btn-approve:disabled, .ad-btn-deny:disabled { opacity: 0.5; cursor: not-allowed; }
    .ad-btn-inactive {
        border-color: #e5e7eb; background: white; color: #9ca3af;
    }
    .ad-btn-inactive:hover { border-color: #d1d5db; color: #6b7280; }
    .dark .ad-btn-inactive { border-color: #4b5563; background: #374151; color: #9ca3af; }
    .dark .ad-btn-inactive:hover { border-color: #6b7280; color: #d1d5db; }

    .ad-btn-active-approve {
        border-color: #86efac; background: #f0fdf4; color: #15803d;
    }
    .dark .ad-btn-active-approve { border-color: #166534; background: rgba(22,101,52,0.15); color: #4ade80; }

    .ad-btn-active-deny {
        border-color: #fca5a5; background: #fef2f2; color: #b91c1c;
    }
    .dark .ad-btn-active-deny { border-color: #991b1b; background: rgba(153,27,27,0.15); color: #f87171; }

    /* -- Weight dropdown --------------------------- */
    .adv-weight-select {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.75rem;
        padding: 0.2rem 1.25rem 0.2rem 0.4rem;
        border-radius: 0.3rem;
    }

    /* -- Modal ------------------------------------- */
    .adv-modal-backdrop {
        backdrop-filter: blur(4px) saturate(0.8);
        -webkit-backdrop-filter: blur(4px) saturate(0.8);
    }
    .adv-modal-panel {
        box-shadow: 0 20px 40px rgba(0,0,0,0.12), 0 0 0 1px rgba(255,255,255,0.05);
    }
    .dark .adv-modal-panel {
        box-shadow: 0 20px 40px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
    }
    .adv-detail-hero {
        background: linear-gradient(135deg, rgba(6,182,212,0.03) 0%, transparent 60%);
    }
    .dark .adv-detail-hero {
        background: linear-gradient(135deg, rgba(6,182,212,0.06) 0%, transparent 60%);
    }

    /* -- Bulk bar ---------------------------------- */
    .adv-bulk-bar {
        background: linear-gradient(135deg, rgba(8,145,178,0.06), rgba(6,182,212,0.03));
        border: 1px solid rgba(6,182,212,0.15);
    }
    .dark .adv-bulk-bar {
        background: linear-gradient(135deg, rgba(8,145,178,0.12), rgba(6,182,212,0.06));
        border: 1px solid rgba(6,182,212,0.2);
    }

    /* -- Scrollbar --------------------------------- */
    .ad-review ::-webkit-scrollbar { height: 5px; width: 5px; }
    .ad-review ::-webkit-scrollbar-track { background: transparent; }
    .ad-review ::-webkit-scrollbar-thumb { background: rgba(156,163,175,0.25); border-radius: 3px; }

    /* -- Card stagger animation -------------------- */
    @keyframes card-enter {
        from { opacity: 0; transform: translateY(8px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .ad-card-enter {
        animation: card-enter 0.25s ease-out both;
    }

    /* -- Approval micro-interaction flash ---------- */
    @keyframes approval-flash {
        0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
        50% { box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    @keyframes deny-flash {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    /* -- Improved hover states on cards ------------ */
    .ad-review .group:hover {
        border-color: rgba(6, 182, 212, 0.25);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06), 0 0 0 1px rgba(6, 182, 212, 0.08);
    }
    .dark .ad-review .group:hover {
        border-color: rgba(6, 182, 212, 0.2);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(6, 182, 212, 0.1);
    }
</style>

<script>
function advertiserSelect() {
    const allAdvertisers = @json(\App\Models\Advertiser::select('id', 'name', 'network')->orderBy('name')->get());

    return {
        open: false,
        searchText: '',
        selectedId: '',

        init() {
            // Sync with Livewire's advertiserId
            const wireId = this.$wire?.advertiserId || '';
            if (wireId) {
                const adv = allAdvertisers.find(a => String(a.id) === String(wireId));
                if (adv) {
                    this.searchText = adv.name;
                    this.selectedId = String(wireId);
                }
            }
        },

        filtered() {
            if (!this.searchText) return allAdvertisers.slice(0, 50);
            const term = this.searchText.toLowerCase();
            return allAdvertisers.filter(a => a.name.toLowerCase().includes(term)).slice(0, 50);
        },
        select(adv) {
            this.selectedId = String(adv.id);
            this.searchText = adv.name;
            this.open = false;
            this.$wire.set('advertiserId', String(adv.id));
        },
        clear() {
            this.selectedId = '';
            this.searchText = '';
            this.$wire.set('advertiserId', '');
        }
    };
}

function adReviewGrid() {
    return {
        selected: [],
        viewSize: '{{ request("view_size", "large") }}',
        savingApproval: {},
        showDenyModal: false,
        denyTarget: null,
        denyReason: '',
        showDetailModal: false,
        detailAd: null,
        savingWeight: false,
        brokenImages: {},
        ads: {},
        pageIds: [],
        toast: { show: false, message: '', type: 'success' },
        showAdvertiserPopup: false,
        advertiserDetail: null,

        init() {
            // Listen for Livewire ads-updated event
            Livewire.on('ads-updated', ({ adsJson, pageIds }) => {
                this.ads = { ...adsJson };
                this.pageIds = [...pageIds];
                this.selected = [];
            });
        },

        // Selection
        toggleSelect(id) {
            const idx = this.selected.indexOf(id);
            idx > -1 ? this.selected.splice(idx, 1) : this.selected.push(id);
        },
        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selected = [...new Set([...this.selected, ...this.pageIds])];
            } else {
                this.selected = this.selected.filter(id => !this.pageIds.includes(id));
            }
        },
        allOnPageSelected() {
            return this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(id));
        },
        selectAllMatching() { this.selected = ['all_matching']; },
        clearSelection() { this.selected = []; },

        // Approval -- immediate AJAX
        async approve(id) {
            if (!id) return;
            this.savingApproval = { ...this.savingApproval, [id]: true };
            try {
                const res = await fetch(`/api/ads/${id}/approval`, {
                    method: 'PATCH',
                    headers: this._headers(),
                    body: JSON.stringify({ approval_status: 'approved' }),
                });
                if (res.ok) {
                    await res.json();
                    this.ads[id] = { ...this.ads[id], approval_status: 'approved', approval_reason: null };
                    if (this.detailAd?.id === id) {
                        this.detailAd = this.ads[id];
                    }
                    // Flash animation
                    const card = document.querySelector(`[data-ad-id="${id}"]`);
                    if (card) {
                        card.style.animation = 'approval-flash 0.5s ease-out';
                        setTimeout(() => card.style.animation = '', 500);
                    }
                }
            } catch (e) { console.error('Approve failed:', e); }
            this.savingApproval = { ...this.savingApproval, [id]: false };
        },

        async denyImmediate(id) {
            if (!id) return;
            this.savingApproval = { ...this.savingApproval, [id]: true };
            try {
                const res = await fetch(`/api/ads/${id}/approval`, {
                    method: 'PATCH',
                    headers: this._headers(),
                    body: JSON.stringify({ approval_status: 'denied' }),
                });
                if (res.ok) {
                    this.ads[id] = { ...this.ads[id], approval_status: 'denied', approval_reason: null };
                    if (this.detailAd?.id === id) {
                        this.detailAd = this.ads[id];
                    }
                    const card = document.querySelector(`[data-ad-id="${id}"]`);
                    if (card) {
                        card.style.animation = 'deny-flash 0.5s ease-out';
                        setTimeout(() => card.style.animation = '', 500);
                    }
                }
            } catch (e) { console.error('Deny failed:', e); }
            this.savingApproval = { ...this.savingApproval, [id]: false };
        },

        startDeny(id) {
            this.denyTarget = { type: 'single', adId: id };
            this.denyReason = '';
            this.showDenyModal = true;
        },

        startBulkDeny() {
            this.denyTarget = { type: 'bulk' };
            this.denyReason = '';
            this.showDenyModal = true;
        },

        async confirmDeny() {
            const reason = this.denyReason || null;
            this.showDenyModal = false;

            if (this.denyTarget?.type === 'single') {
                const id = this.denyTarget.adId;
                this.savingApproval = { ...this.savingApproval, [id]: true };
                try {
                    const res = await fetch(`/api/ads/${id}/approval`, {
                        method: 'PATCH',
                        headers: this._headers(),
                        body: JSON.stringify({ approval_status: 'denied', approval_reason: reason }),
                    });
                    if (res.ok) {
                        this.ads[id] = { ...this.ads[id], approval_status: 'denied', approval_reason: reason };
                        if (this.detailAd?.id === id) {
                            this.detailAd = this.ads[id];
                        }
                        // Flash animation
                        const card = document.querySelector(`[data-ad-id="${id}"]`);
                        if (card) {
                            card.style.animation = 'deny-flash 0.5s ease-out';
                            setTimeout(() => card.style.animation = '', 500);
                        }
                    }
                } catch (e) { console.error('Deny failed:', e); }
                this.savingApproval = { ...this.savingApproval, [id]: false };
            } else if (this.denyTarget?.type === 'bulk') {
                await this._bulkAction('denied', reason);
            }

            this.denyTarget = null;
            this.denyReason = '';
        },

        async bulkApprove() {
            await this._bulkAction('approved', null);
        },

        async _bulkAction(status, reason) {
            const body = { approval_status: status, approval_reason: reason };

            if (this.selected.includes('all_matching')) {
                body.filter = this._currentFilters();
            } else {
                body.ad_ids = this.selected;
            }

            try {
                const res = await fetch('/api/ads/bulk-approval', {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify(body),
                });
                if (res.ok) {
                    const data = await res.json();
                    const ids = this.selected.includes('all_matching') ? Object.keys(this.ads).map(Number) : this.selected;
                    ids.forEach(id => {
                        if (this.ads[id]) {
                            this.ads[id] = {
                                ...this.ads[id],
                                approval_status: status,
                                approval_reason: status === 'approved' ? null : reason
                            };
                        }
                    });
                    const count = data.count || ids.length;
                    this.showToast(`${count} ad${count !== 1 ? 's' : ''} ${status}`, 'success');
                    this.clearSelection();
                    // Refresh Livewire to update pagination counts
                    const component = Livewire.first();
                    if (component) component.$refresh();
                }
            } catch (e) { console.error(e); }
        },

        // Toast
        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },

        // Detail modal
        openDetail(id) {
            this.detailAd = this.ads[id];
            this.showDetailModal = true;
        },

        // Advertiser popup
        openAdvertiserPopup(adId) {
            const ad = this.ads[adId];
            if (!ad) return;
            this.advertiserDetail = {
                name: ad.advertiser_name,
                network: ad.network,
                description: ad.advertiser_description,
                logo_url: ad.advertiser_logo_url,
                network_rank: ad.advertiser_network_rank,
                website_url: ad.advertiser_website_url,
                category: ad.advertiser_category,
                geo_region: ad.geo_region,
            };
            this.showAdvertiserPopup = true;
        },

        async saveWeight(id, weight) {
            this.savingWeight = true;
            const val = weight === '' ? null : weight;
            try {
                const res = await fetch(`/api/ads/${id}/weight`, {
                    method: 'PATCH',
                    headers: this._headers(),
                    body: JSON.stringify({ weight_override: val }),
                });
                if (res.ok) {
                    this.ads[id] = { ...this.ads[id], weight_override: val };
                }
            } catch (e) { console.error(e); }
            this.savingWeight = false;
        },

        // Broken image
        markBrokenImage(id) { this.brokenImages[id] = true; },

        // View size
        setViewSize(size) {
            this.viewSize = size;
            const url = new URL(window.location);
            url.searchParams.set('view_size', size);
            history.replaceState(null, '', url);
        },

        getGridClasses() {
            switch (this.viewSize) {
                case 'large': return 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4';
                case 'medium': return 'grid-cols-3 md:grid-cols-4 lg:grid-cols-5';
                case 'small': return 'grid-cols-4 md:grid-cols-5 lg:grid-cols-7';
                default: return 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4';
            }
        },

        _headers() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            };
        },

        _currentFilters() {
            const params = new URLSearchParams(window.location.search);
            const filter = {};
            ['search', 'network', 'creative_type', 'approval_status', 'advertiser_id', 'dimensions', 'advertiser_status', 'has_image', 'needs_attention'].forEach(key => {
                if (params.has(key)) filter[key] = params.get(key);
            });
            return filter;
        },
    };
}
</script>
@endsection
