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

    public function addSize(Request $request)
    {
        $data = $request->validate([
            'width' => ['required', 'integer', 'min:1'],
            'height' => ['required', 'integer', 'min:1'],
        ]);

        $sites = Site::where('is_active', true)->get();
        $name = "{$data['width']}x{$data['height']}";
        $created = 0;

        foreach ($sites as $site) {
            $exists = Placement::where('site_id', $site->id)
                ->where('width', $data['width'])
                ->where('height', $data['height'])
                ->exists();

            if (!$exists) {
                Placement::create([
                    'site_id' => $site->id,
                    'name' => $name,
                    'width' => $data['width'],
                    'height' => $data['height'],
                    'is_active' => true,
                ]);
                $created++;
            }
        }

        return response()->json([
            'ok' => true,
            'created' => $created,
            'message' => "Created {$created} placement(s) for size {$name}.",
        ]);
    }

    public function toggleActive(Placement $placement)
    {
        $placement->update(['is_active' => !$placement->is_active]);
        return response()->json(['ok' => true, 'placement' => $placement]);
    }

    public function updateGroup(Request $request, Placement $placement)
    {
        $data = $request->validate([
            'adrotate_group_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = !empty($data['adrotate_group_id']);
        $placement->update($data);

        return response()->json(['ok' => true, 'placement' => $placement]);
    }

    public function updateSize(Request $request)
    {
        $data = $request->validate([
            'old_width' => ['required', 'integer', 'min:1'],
            'old_height' => ['required', 'integer', 'min:1'],
            'new_width' => ['required', 'integer', 'min:1'],
            'new_height' => ['required', 'integer', 'min:1'],
        ]);

        // No-op if dimensions unchanged
        if ($data['old_width'] == $data['new_width'] && $data['old_height'] == $data['new_height']) {
            return response()->json(['ok' => true, 'updated' => 0]);
        }

        // Conflict check
        if (Placement::where('width', $data['new_width'])->where('height', $data['new_height'])->exists()) {
            return response()->json([
                'message' => "Size {$data['new_width']}x{$data['new_height']} already exists.",
            ], 422);
        }

        $name = "{$data['new_width']}x{$data['new_height']}";
        $updated = Placement::where('width', $data['old_width'])
            ->where('height', $data['old_height'])
            ->update(['width' => $data['new_width'], 'height' => $data['new_height'], 'name' => $name]);

        return response()->json(['ok' => true, 'updated' => $updated]);
    }

    public function deleteSize(Request $request)
    {
        $data = $request->validate([
            'width' => ['required', 'integer', 'min:1'],
            'height' => ['required', 'integer', 'min:1'],
        ]);

        $deleted = Placement::where('width', $data['width'])->where('height', $data['height'])->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Size not found.'], 404);
        }

        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'changes' => ['required', 'array', 'min:1'],
            'changes.*.id' => ['required', 'integer', 'exists:placements,id'],
            'changes.*.is_active' => ['sometimes', 'boolean'],
            'changes.*.adrotate_group_id' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $count = 0;
        foreach ($request->input('changes') as $change) {
            $placement = Placement::findOrFail($change['id']);
            $update = [];
            if (array_key_exists('is_active', $change)) {
                $update['is_active'] = $change['is_active'];
            }
            if (array_key_exists('adrotate_group_id', $change)) {
                $update['adrotate_group_id'] = $change['adrotate_group_id'];
                $update['is_active'] = !empty($change['adrotate_group_id']);
            }
            if (!empty($update)) {
                $placement->update($update);
                $count++;
            }
        }

        return response()->json(['ok' => true, 'count' => $count]);
    }
}