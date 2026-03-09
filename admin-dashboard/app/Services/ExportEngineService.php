<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ExportEngineService
{
    public function buildPreview(array $contract): array
    {
        $rows = $this->fetchRows($contract);
        $groupByNetwork = [];
        $groupByDimensions = [];

        foreach ($rows as $row) {
            $network = (string) ($row['network'] ?? 'unknown');
            $groupByNetwork[$network] = ($groupByNetwork[$network] ?? 0) + 1;

            $width = (int) ($row['width'] ?? 0);
            $height = (int) ($row['height'] ?? 0);
            if ($width > 0 && $height > 0) {
                $dim = "{$width}x{$height}";
                $groupByDimensions[$dim] = ($groupByDimensions[$dim] ?? 0) + 1;
            }
        }

        ksort($groupByNetwork);
        ksort($groupByDimensions);

        return [
            'summary' => [
                'total_rows' => count($rows),
                'grouped_by_network' => $groupByNetwork,
                'grouped_by_dimensions' => $groupByDimensions,
            ],
            'sample_rows' => array_slice($rows, 0, 10),
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'export_type' => $contract['export_type'],
            ],
        ];
    }

    public function buildDownloadPayload(array $contract, string $siteDomain): array
    {
        $rows = $this->fetchRows($contract);
        $headers = $this->headersForType($contract['export_type']);
        $csvRows = [];

        foreach ($rows as $row) {
            $csvRows[] = $this->rowForType($contract['export_type'], $row);
        }

        return [
            'filename' => $this->buildFilename($siteDomain, $contract['export_type']),
            'headers' => $headers,
            'rows' => $csvRows,
            'meta' => [
                'row_count' => count($csvRows),
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    private function fetchRows(array $contract): array
    {
        return $contract['export_type'] === 'text'
            ? $this->fetchTextRows($contract)
            : $this->fetchBannerRows($contract);
    }

    private function fetchBannerRows(array $contract): array
    {
        $siteId = (int) $contract['site_id'];
        $query = DB::table('v_exportable_ads')
            ->where('site_id', $siteId)
            ->select([
                'ad_id',
                'advertiser_name',
                'network',
                'site_domain',
                'advert_name',
                'bannercode',
                'image_url',
                'width',
                'height',
                'final_weight',
            ]);

        $filters = $contract['filters'];
        if (! empty($filters['network'])) {
            $query->where('network', $filters['network']);
        }

        if (! empty($filters['advertiser_id'])) {
            $query->where('advertiser_id', $filters['advertiser_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('advert_name', 'like', "%{$search}%")
                    ->orWhere('advertiser_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['dimensions'])) {
            [$width, $height] = $this->parseDimensions($filters['dimensions']);
            $query->where('width', $width)->where('height', $height);
        }

        // Phase 2 Part B: banner exports are always placement-aware.
        $query->whereExists(function ($q) use ($siteId) {
            $q->select(DB::raw('1'))
                ->from('placements as p')
                ->whereColumn('p.width', 'v_exportable_ads.width')
                ->whereColumn('p.height', 'v_exportable_ads.height')
                ->where('p.site_id', $siteId)
                ->where('p.is_active', 1);
        });

        return $query->orderBy('ad_id')->get()->map(fn ($r) => (array) $r)->all();
    }

    private function fetchTextRows(array $contract): array
    {
        $query = DB::table('ads as a')
            ->join('advertisers as adv', 'a.advertiser_id', '=', 'adv.id')
            ->join('site_advertiser_rules as sar', 'sar.advertiser_id', '=', 'adv.id')
            ->join('sites as s', 'sar.site_id', '=', 's.id')
            ->where('sar.site_id', $contract['site_id'])
            ->where('sar.rule', 'allowed')
            ->where('a.creative_type', 'text')
            ->where('a.status', 'active')
            ->where('a.approval_status', 'approved')
            ->where('adv.is_active', 1)
            ->select([
                'a.id as ad_id',
                'adv.name as advertiser_name',
                'a.network',
                's.domain as site_domain',
                'a.tracking_url',
                'a.bannercode',
                DB::raw('COALESCE(a.weight_override, adv.default_weight, 2) as final_weight'),
            ]);

        $filters = $contract['filters'];
        if (! empty($filters['network'])) {
            $query->where('a.network', $filters['network']);
        }

        if (! empty($filters['advertiser_id'])) {
            $query->where('a.advertiser_id', $filters['advertiser_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('adv.name', 'like', "%{$search}%")
                    ->orWhere('a.bannercode', 'like', "%{$search}%")
                    ->orWhere('a.tracking_url', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('a.id')->get()->map(function ($r) {
            $row = (array) $r;
            $row['anchor_text'] = $this->extractAnchorText((string) ($row['bannercode'] ?? ''));
            $row['affiliate_link'] = (string) ($row['tracking_url'] ?? '');
            return $row;
        })->all();
    }

    private function headersForType(string $type): array
    {
        if ($type === 'text') {
            return ['ad_id', 'advertiser_name', 'anchor_text', 'affiliate_link', 'site_domain', 'network', 'final_weight'];
        }

        // AdRotate-compatible banner CSV headers (ordered).
        return [
            'advert_name',
            'bannercode',
            'imagetype',
            'image_url',
            'width',
            'height',
            'campaign_name',
            'enable_stats',
            'show_everyone',
            'show_desktop',
            'show_mobile',
            'show_tablet',
            'show_ios',
            'show_android',
            'weight',
            'autodelete',
            'autodisable',
            'budget',
            'click_rate',
            'impression_rate',
            'state_required',
            'geo_cities',
            'geo_states',
            'geo_countries',
            'schedule_start',
            'schedule_end',
        ];
    }

    private function rowForType(string $type, array $row): array
    {
        if ($type === 'text') {
            return [
                $row['ad_id'] ?? '',
                $row['advertiser_name'] ?? '',
                $row['anchor_text'] ?? '',
                $row['affiliate_link'] ?? '',
                $row['site_domain'] ?? '',
                $row['network'] ?? '',
                $row['final_weight'] ?? '',
            ];
        }

        return [
            $row['advert_name'] ?? '',
            $row['bannercode'] ?? '',
            $row['imagetype'] ?? '',
            $row['image_url'] ?? '',
            $row['width'] ?? '',
            $row['height'] ?? '',
            $row['campaign_name'] ?? 'General Promotion',
            $row['enable_stats'] ?? 'Y',
            $row['show_everyone'] ?? 'Y',
            $row['show_desktop'] ?? 'Y',
            $row['show_mobile'] ?? 'Y',
            $row['show_tablet'] ?? 'Y',
            $row['show_ios'] ?? 'Y',
            $row['show_android'] ?? 'Y',
            $row['final_weight'] ?? 2,
            $row['autodelete'] ?? 'Y',
            $row['autodisable'] ?? 'N',
            $row['budget'] ?? 0,
            $row['click_rate'] ?? 0,
            $row['impression_rate'] ?? 0,
            $row['state_required'] ?? 'N',
            $row['geo_cities'] ?? 'a:0:{}',
            $row['geo_states'] ?? 'a:0:{}',
            $row['geo_countries'] ?? 'a:0:{}',
            $row['schedule_start'] ?? 0,
            $row['schedule_end'] ?? 2650941780,
        ];
    }

    private function parseDimensions(string $dimensions): array
    {
        $parts = explode('x', strtolower(trim($dimensions)));
        return [(int) $parts[0], (int) $parts[1]];
    }

    private function extractAnchorText(string $html): string
    {
        $text = trim(strip_tags($html));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_replace('/\s+/', ' ', $text) ?: '';
    }

    private function buildFilename(string $siteDomain, string $exportType): string
    {
        return "{$siteDomain}-" . now()->format('Y-m-d-His') . "-{$exportType}.csv";
    }
}
