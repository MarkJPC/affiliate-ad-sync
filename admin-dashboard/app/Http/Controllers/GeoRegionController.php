<?php

namespace App\Http\Controllers;

use App\Models\GeoRegion;
use Illuminate\Http\Request;

class GeoRegionController extends Controller
{
    public function index()
    {
        $regions = GeoRegion::orderBy('priority')->get();

        return view('geo-regions.index', compact('regions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:geo_regions,name',
            'priority' => 'required|integer|min:0',
            'country_codes' => ['required', 'string', 'regex:/^[A-Z]{2}(,\s*[A-Z]{2})*$/i'],
            'adrotate_value' => 'required|string',
        ]);

        $validated['country_codes'] = strtoupper($validated['country_codes']);

        GeoRegion::create($validated);

        return redirect()->route('geo-regions.index')->with('success', 'Region created.');
    }

    public function update(Request $request, GeoRegion $geoRegion)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:geo_regions,name,' . $geoRegion->id,
            'priority' => 'required|integer|min:0',
            'country_codes' => ['required', 'string', 'regex:/^[A-Z]{2}(,\s*[A-Z]{2})*$/i'],
            'adrotate_value' => 'required|string',
        ]);

        $validated['country_codes'] = strtoupper($validated['country_codes']);

        $geoRegion->update($validated);

        return redirect()->route('geo-regions.index')->with('success', 'Region updated.');
    }

    public function destroy(GeoRegion $geoRegion)
    {
        $geoRegion->delete();

        return redirect()->route('geo-regions.index')->with('success', 'Region deleted.');
    }
}
