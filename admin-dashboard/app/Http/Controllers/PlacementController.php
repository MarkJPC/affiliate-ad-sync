<?php

namespace App\Http\Controllers;

use App\Models\Placement;
use App\Models\Site;
use Illuminate\Http\Request;

class PlacementController extends Controller
{
    private function ensurePlacementBelongsToSite(Site $site, Placement $placement): void
    {
        abort_unless($placement->site_id === $site->id, 404);
    }

    public function grid()
    {
        $sites = Site::where('is_active', true)
            ->orderBy('name')
            ->get();

        $placements = Placement::whereIn('site_id', $sites->pluck('id'))
            ->get();

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

        return view('placements.grid', compact('sites', 'sizes', 'placementsByKey'));
    }

    public function index(Site $site)
    {
        $placements = $site->placements()->orderBy('id', 'desc')->get();
        return view('placements.index', compact('site', 'placements'));
    }

    public function create(Site $site)
    {
        return view('placements.create', compact('site'));
    }

    public function store(Request $request, Site $site)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'width' => ['nullable','integer','min:0'],
            'height' => ['nullable','integer','min:0'],
            'adrotate_group_id' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $site->placements()->create($data);

        return redirect()->route('sites.placements.index', $site)->with('success', 'Placement created.');
    }

    public function show(Site $site, Placement $placement)
    {
        $this->ensurePlacementBelongsToSite($site, $placement);
        return view('placements.show', compact('site', 'placement'));
    }

    public function edit(Site $site, Placement $placement)
    {
        $this->ensurePlacementBelongsToSite($site, $placement);
        return view('placements.edit', compact('site', 'placement'));
    }

    public function update(Request $request, Site $site, Placement $placement)
    {
        $this->ensurePlacementBelongsToSite($site, $placement);

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'width' => ['nullable','integer','min:0'],
            'height' => ['nullable','integer','min:0'],
            'adrotate_group_id' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $placement->update($data);

        return redirect()->route('sites.placements.index', $site)->with('success', 'Placement updated.');
    }

    public function destroy(Site $site, Placement $placement)
    {
        $this->ensurePlacementBelongsToSite($site, $placement);

        $placement->delete();

        return redirect()->route('sites.placements.index', $site)->with('success', 'Placement deleted.');
    }
}