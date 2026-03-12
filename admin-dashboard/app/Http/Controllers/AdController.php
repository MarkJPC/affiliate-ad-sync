<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function updateApproval(Request $request, Ad $ad)
    {
        $validated = $request->validate([
            'approval_status' => 'required|in:approved,denied',
            'approval_reason' => 'nullable|string|max:500',
        ]);

        $data = ['approval_status' => $validated['approval_status']];

        if ($validated['approval_status'] === 'approved') {
            $data['approval_reason'] = null;
        } else {
            $data['approval_reason'] = $validated['approval_reason'] ?? null;
        }

        $ad->update($data);

        return response()->json(['success' => true, 'ad' => $ad->fresh()]);
    }

    public function updateWeight(Request $request, Ad $ad)
    {
        $validated = $request->validate([
            'weight_override' => 'nullable|in:2,4,6,8,10',
        ]);

        $ad->update(['weight_override' => $validated['weight_override']]);

        return response()->json(['success' => true, 'weight_override' => $ad->weight_override]);
    }

    public function bulkApproval(Request $request)
    {
        $validated = $request->validate([
            'ad_ids' => 'required_without:filter|array',
            'ad_ids.*' => 'integer|exists:ads,id',
            'filter' => 'nullable|array',
            'approval_status' => 'required|in:approved,denied',
            'approval_reason' => 'nullable|string|max:500',
        ]);

        $ids = $validated['ad_ids'] ?? $this->resolveFilterIds($request);

        $data = ['approval_status' => $validated['approval_status']];
        if ($validated['approval_status'] === 'approved') {
            $data['approval_reason'] = null;
        } else {
            $data['approval_reason'] = $validated['approval_reason'] ?? null;
        }

        $count = Ad::whereIn('id', $ids)->update($data);

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function markReviewed()
    {
        auth()->user()->update(['last_ad_review_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function resolveFilterIds(Request $request): array
    {
        $filter = $request->input('filter', []);
        $query = Ad::query();

        if ($search = ($filter['search'] ?? null)) {
            $query->where(function ($q) use ($search) {
                $q->where('advert_name', 'like', "%{$search}%")
                  ->orWhereHas('advertiser', fn ($aq) => $aq->where('name', 'like', "%{$search}%"));
            });
        }
        if ($network = ($filter['network'] ?? null)) {
            $query->where('network', $network);
        }
        if ($creativeType = ($filter['creative_type'] ?? null)) {
            $query->where('creative_type', $creativeType);
        }
        if ($approvalStatus = ($filter['approval_status'] ?? null)) {
            $query->where('approval_status', $approvalStatus);
        }
        if ($advertiserId = ($filter['advertiser_id'] ?? null)) {
            $query->where('advertiser_id', (int) $advertiserId);
        }
        if ($dimensions = ($filter['dimensions'] ?? null)) {
            $parts = explode('x', $dimensions);
            if (count($parts) === 2) {
                $query->where('width', (int) $parts[0])->where('height', (int) $parts[1]);
            }
        }
        if ($advertiserStatus = ($filter['advertiser_status'] ?? null)) {
            if ($advertiserStatus === 'allowed') {
                $query->whereHas('advertiser.siteRules', fn ($q) => $q->where('rule', 'allowed'));
            } elseif ($advertiserStatus === 'denied') {
                $query->whereHas('advertiser.siteRules', fn ($q) => $q->where('rule', 'denied'));
            }
        }
        // Has image
        $hasImage = $filter['has_image'] ?? '1';
        if ($hasImage === '1') {
            $query->whereNotNull('image_url')->where('image_url', '!=', '');
        }
        // Needs attention
        $user = auth()->user();
        $needsAttention = $filter['needs_attention'] ?? '1';
        if ($needsAttention === '1' && $user->last_ad_review_at) {
            $query->where('last_synced_at', '>', $user->last_ad_review_at);
        }

        return $query->pluck('id')->all();
    }
}
