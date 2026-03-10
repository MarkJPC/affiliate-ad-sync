<?php

namespace App\Http\Controllers;

use App\Models\Placement;
use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        $allSites = Site::orderBy('name')->get();

        // Placements grid data (same logic as PlacementController@grid)
        $activeSites = $allSites->where('is_active', true)->values();

        $placements = Placement::whereIn('site_id', $activeSites->pluck('id'))->get();

        $sizes = $placements
            ->map(fn ($p) => [
                'width' => $p->width,
                'height' => $p->height,
                'label' => "{$p->width}x{$p->height}",
            ])
            ->unique(fn ($size) => $size['width'] . 'x' . $size['height'])
            ->sortBy('label')
            ->values();

        $placementsByKey = $placements->keyBy(function ($placement) {
            return $placement->site_id . '-' . $placement->width . 'x' . $placement->height;
        });

        return view('sites.index', [
            'allSites' => $allSites,
            'sites' => $activeSites,
            'sizes' => $sizes,
            'placementsByKey' => $placementsByKey,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255'],
            'wordpress_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $site = Site::create($data);

        return response()->json(['ok' => true, 'site' => $site]);
    }

    public function update(Request $request, Site $site)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255'],
            'wordpress_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $site->update($data);

        return response()->json(['ok' => true, 'site' => $site->fresh()]);
    }

    public function destroy(Site $site)
    {
        $site->delete();

        return response()->json(['ok' => true]);
    }
}
