<?php

namespace App\Services;

class ExportFilterService
{
    /**
     * Normalize validated export request input into one shared payload shape.
     */
    public static function normalize(array $validated): array
    {
        $exportType = strtolower((string) ($validated['export_type'] ?? 'banner'));
        if (! in_array($exportType, ['banner', 'text'], true)) {
            $exportType = 'banner';
        }

        $network = isset($validated['network']) ? strtolower(trim((string) $validated['network'])) : null;
        $network = $network !== '' ? $network : null;

        $dimensions = isset($validated['dimensions']) ? trim((string) $validated['dimensions']) : null;
        $dimensions = $dimensions !== '' ? $dimensions : null;

        $search = isset($validated['search']) ? trim((string) $validated['search']) : null;
        $search = $search !== '' ? $search : null;

        $advertiserId = isset($validated['advertiser_id']) ? (int) $validated['advertiser_id'] : null;

        $activeSizesOnly = true;
        if (array_key_exists('active_sizes_only', $validated)) {
            $activeSizesOnly = filter_var($validated['active_sizes_only'], FILTER_VALIDATE_BOOL);
        }

        return [
            'site_id' => (int) $validated['site_id'],
            'export_type' => $exportType,
            'filters' => [
                'network' => $network,
                'advertiser_id' => $advertiserId,
                'dimensions' => $dimensions,
                'search' => $search,
                'active_sizes_only' => $activeSizesOnly,
            ],
        ];
    }
}
