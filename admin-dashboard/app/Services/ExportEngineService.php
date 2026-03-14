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

        $result = [
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

        if (count($rows) === 0) {
            $result['diagnostics'] = $this->diagnoseMissingRows($contract);
        }

        return $result;
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
                'imagetype',
                'enable_stats',
                'show_everyone',
                'show_desktop',
                'show_mobile',
                'show_tablet',
                'show_ios',
                'show_android',
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

        if ($filters['active_sizes_only'] ?? true) {
            $query->whereExists(function ($q) use ($siteId) {
                $q->select(DB::raw('1'))
                    ->from('placements as p')
                    ->whereColumn('p.width', 'v_exportable_ads.width')
                    ->whereColumn('p.height', 'v_exportable_ads.height')
                    ->where('p.site_id', $siteId)
                    ->where('p.is_active', 1);
            });
        }

        return $query->orderBy('ad_id')
            ->get()
            ->map(function ($r) {
                return $this->normalizeBannerRow((array) $r);
            })
            ->filter()
            ->values()
            ->all();
    }

    private function fetchTextRows(array $contract): array
    {
        $baseColumns = [
            'a.id as ad_id',
            'a.advertiser_id',
            'adv.name as advertiser_name',
            'a.network',
            's.domain as site_domain',
            'a.tracking_url',
            'a.bannercode',
        ];

        // ad_content may not exist on older SQLite dev databases
        if ($this->columnExists('ads', 'ad_content')) {
            $baseColumns[] = 'a.ad_content';
        }

        $query = DB::table('ads as a')
            ->join('advertisers as adv', 'a.advertiser_id', '=', 'adv.id')
            ->join('site_advertiser_rules as sar', 'sar.advertiser_id', '=', 'adv.id')
            ->join('sites as s', 'sar.site_id', '=', 's.id')
            ->where('sar.site_id', $contract['site_id'])
            ->where('sar.rule', 'allowed')
            ->where('a.creative_type', 'text')
            ->where('a.status', 'active')
            ->where('a.approval_status', '!=', 'denied')
            ->where('adv.is_active', 1)
            ->distinct()
            ->select(array_merge($baseColumns, [
                DB::raw('COALESCE(a.weight_override, adv.default_weight, 2) as final_weight'),
                DB::raw("(
                    SELECT GROUP_CONCAT(s2.domain, ', ')
                    FROM site_advertiser_rules sar2
                    JOIN sites s2 ON s2.id = sar2.site_id
                    WHERE sar2.advertiser_id = adv.id
                      AND sar2.rule = 'allowed'
                ) as approved_sites"),
            ]));

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

        return $query->orderBy('a.id')
            ->get()
            ->map(function ($r) {
                $row = (array) $r;
                // Prefer ad_content (CJ ad-content, FlexOffers linkDescription, etc.)
                // Fall back to stripping HTML from bannercode
                $adContent = trim((string) ($row['ad_content'] ?? ''));
                $row['anchor_text'] = $adContent !== ''
                    ? $adContent
                    : $this->extractAnchorText((string) ($row['bannercode'] ?? ''));
                $row['affiliate_link'] = trim((string) ($row['tracking_url'] ?? ''));
                return $this->normalizeTextRow($row);
            })
            ->filter()
            ->values()
            ->all();
    }

    private function headersForType(string $type): array
    {
        if ($type === 'text') {
            return [
                'advertiser_name',
                'anchor_text',
                'affiliate_link',
                'approved_sites',
                'network',
                'weight',
            ];
        }

        // AdRotate-compatible banner CSV headers (24 columns, matches real AdRotate export).
        return [
            'id',
            'advert_name',
            'bannercode',
            'imagetype',
            'image_url',
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
                $row['advertiser_name'] ?? '',
                $row['anchor_text'] ?? '',
                $row['affiliate_link'] ?? '',
                $row['approved_sites'] ?? '',
                $row['network'] ?? '',
                $row['final_weight'] ?? 2,
            ];
        }

        return [
            '',  // id — empty for new ads (no AdRotate ID yet)
            $row['advert_name'] ?? '',
            $row['bannercode'] ?? '',
            $row['imagetype'] ?? '',
            $row['image_url'] ?? '',
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

    private function normalizeBannerRow(array $row): ?array
    {
        $width = (int) ($row['width'] ?? 0);
        $height = (int) ($row['height'] ?? 0);
        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $bannercode = trim((string) ($row['bannercode'] ?? ''));
        $imageUrl = trim((string) ($row['image_url'] ?? ''));
        if ($bannercode === '' && $imageUrl === '') {
            return null;
        }

        $finalWeight = (int) ($row['final_weight'] ?? 2);
        if ($finalWeight <= 0) {
            $finalWeight = 2;
        }

        $scheduleStart = (int) ($row['schedule_start'] ?? 0);
        $scheduleEnd = (int) ($row['schedule_end'] ?? 2650941780);
        if ($scheduleEnd <= $scheduleStart) {
            $scheduleEnd = 2650941780;
        }

        $advertName = trim((string) ($row['advert_name'] ?? ''));
        if ($advertName === '') {
            $advertName = 'Ad ' . (string) ($row['ad_id'] ?? '');
        }

        // AdRotate dropdown asset mode: when image_url is present, set imagetype to
        // 'dropdown' and replace <img src="..."> with <img src="%asset%"> so all three
        // fields (imagetype, image_url, bannercode) stay in sync per AdRotate validation.
        if ($imageUrl !== '') {
            $imagetype = 'dropdown';
            $bannercode = $this->replaceImgSrcWithAsset($bannercode);
        } else {
            $imagetype = '';
        }

        return array_merge($row, [
            'advert_name' => $advertName,
            'bannercode' => $this->normalizeBannercode($bannercode),
            'image_url' => $imageUrl,
            'width' => $width,
            'height' => $height,
            'final_weight' => $finalWeight,
            'imagetype' => $imagetype,
            'enable_stats' => $this->normalizeYn($row['enable_stats'] ?? null, 'Y'),
            'show_everyone' => $this->normalizeYn($row['show_everyone'] ?? null, 'Y'),
            'show_desktop' => $this->normalizeYn($row['show_desktop'] ?? null, 'Y'),
            'show_mobile' => $this->normalizeYn($row['show_mobile'] ?? null, 'Y'),
            'show_tablet' => $this->normalizeYn($row['show_tablet'] ?? null, 'Y'),
            'show_ios' => $this->normalizeYn($row['show_ios'] ?? null, 'Y'),
            'show_android' => $this->normalizeYn($row['show_android'] ?? null, 'Y'),
            'autodelete' => $this->normalizeYn($row['autodelete'] ?? null, 'Y'),
            'autodisable' => $this->normalizeYn($row['autodisable'] ?? null, 'N'),
            'budget' => is_numeric($row['budget'] ?? null) ? (float) $row['budget'] : 0.0,
            'click_rate' => is_numeric($row['click_rate'] ?? null) ? (float) $row['click_rate'] : 0.0,
            'impression_rate' => is_numeric($row['impression_rate'] ?? null) ? (float) $row['impression_rate'] : 0.0,
            'state_required' => $this->normalizeYn($row['state_required'] ?? null, 'N'),
            'geo_cities' => trim((string) ($row['geo_cities'] ?? '')) ?: 'a:0:{}',
            'geo_states' => trim((string) ($row['geo_states'] ?? '')) ?: 'a:0:{}',
            'geo_countries' => trim((string) ($row['geo_countries'] ?? '')) ?: 'a:0:{}',
            'schedule_start' => $scheduleStart,
            'schedule_end' => $scheduleEnd,
        ]);
    }

    private function normalizeBannercode(string $html): string
    {
        // Strip alt="..." and title="..." from <a> tags (invalid HTML on anchors)
        $html = preg_replace('/\s+(alt|title)="[^"]*"/', '', $html);

        // Ensure rel="sponsored" on <a> tags
        if (str_contains($html, '<a ') && !str_contains($html, 'rel="sponsored"')) {
            $html = preg_replace('/<a\s/', '<a rel="sponsored" ', $html);
        }

        // Collapse whitespace/newlines to single spaces (AdRotate can't handle multi-line CSV fields)
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);

        // HTML-encode the entire string for AdRotate import
        $html = htmlspecialchars($html, ENT_QUOTES, 'UTF-8');

        return $html;
    }

    /**
     * Replace the src attribute of <img> tags with %asset% for AdRotate dropdown mode.
     *
     * AdRotate renders %asset% via: str_replace('%asset%', $image, $bannercode)
     * so <img src="%asset%" /> becomes <img src="http://full-url/image.jpg" />
     */
    private function replaceImgSrcWithAsset(string $html): string
    {
        return preg_replace(
            '/<img\b([^>]*)\bsrc="[^"]*"([^>]*)\/?>/i',
            '<img$1src="%asset%"$2/>',
            $html
        );
    }

    private function normalizeYn(mixed $value, string $default): string
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = strtoupper(trim((string) $value));
        return $normalized === 'Y' ? 'Y' : 'N';
    }

    private function normalizeTextRow(array $row): ?array
    {
        $affiliateLink = trim((string) ($row['affiliate_link'] ?? $row['tracking_url'] ?? ''));
        if ($affiliateLink === '') {
            return null;
        }

        $advertiserName = trim((string) ($row['advertiser_name'] ?? ''));
        if ($advertiserName === '') {
            $advertiserName = 'Advertiser ' . (string) ($row['advertiser_id'] ?? '');
        }

        $anchorText = trim((string) ($row['anchor_text'] ?? ''));
        if ($anchorText === '') {
            $anchorText = $advertiserName;
        }

        $network = strtolower(trim((string) ($row['network'] ?? '')));
        if ($network === '') {
            $network = 'unknown';
        }

        $finalWeight = (int) ($row['final_weight'] ?? 2);
        if ($finalWeight <= 0) {
            $finalWeight = 2;
        }

        $approvedSites = $this->normalizeApprovedSites((string) ($row['approved_sites'] ?? ''));
        if ($approvedSites === '') {
            $approvedSites = trim((string) ($row['site_domain'] ?? ''));
        }

        return array_merge($row, [
            'advertiser_name' => $advertiserName,
            'anchor_text' => $anchorText,
            'affiliate_link' => $affiliateLink,
            'approved_sites' => $approvedSites,
            'network' => $network,
            'final_weight' => $finalWeight,
        ]);
    }

    private function normalizeApprovedSites(string $approvedSites): string
    {
        $parts = array_map('trim', explode(',', $approvedSites));
        $parts = array_values(array_filter($parts, fn ($part) => $part !== ''));
        $parts = array_values(array_unique($parts));
        sort($parts, SORT_NATURAL | SORT_FLAG_CASE);

        return implode(', ', $parts);
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

    private function columnExists(string $table, string $column): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $columns = DB::select("PRAGMA table_info({$table})");
            return collect($columns)->contains('name', $column);
        }

        // MySQL / MariaDB
        $db = DB::getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$db, $table, $column]
        );

        return ((int) ($result[0]->cnt ?? 0)) > 0;
    }

    private function diagnoseMissingRows(array $contract): array
    {
        $siteId = (int) $contract['site_id'];
        $exportType = $contract['export_type'];
        $messages = [];

        // 1. Any approved advertisers for this site?
        $allowedCount = (int) DB::table('site_advertiser_rules')
            ->where('site_id', $siteId)
            ->where('rule', 'allowed')
            ->count();

        if ($allowedCount === 0) {
            $messages[] = 'No advertisers are approved (allowed) for this site. Approve advertisers on the Advertisers page first.';
            return ['messages' => $messages];
        }

        $messages[] = "{$allowedCount} advertiser(s) approved for this site.";

        // 2. How many active, non-denied ads exist for those advertisers?
        $baseQuery = DB::table('ads as a')
            ->join('advertisers as adv', 'a.advertiser_id', '=', 'adv.id')
            ->join('site_advertiser_rules as sar', 'sar.advertiser_id', '=', 'adv.id')
            ->where('sar.site_id', $siteId)
            ->where('sar.rule', 'allowed')
            ->where('a.status', 'active')
            ->where('a.approval_status', '!=', 'denied')
            ->where('adv.is_active', 1);

        $totalAds = (int) (clone $baseQuery)->count(DB::raw('DISTINCT a.id'));
        $bannerCount = (int) (clone $baseQuery)->where('a.creative_type', 'banner')->count(DB::raw('DISTINCT a.id'));
        $textCount = (int) (clone $baseQuery)->where('a.creative_type', 'text')->count(DB::raw('DISTINCT a.id'));

        $messages[] = "{$totalAds} eligible ad(s) total: {$bannerCount} banner, {$textCount} text.";

        if ($exportType === 'banner' && $bannerCount === 0 && $textCount > 0) {
            $messages[] = "No banner ads found. Try switching export type to 'Text'.";
        } elseif ($exportType === 'text' && $textCount === 0 && $bannerCount > 0) {
            $messages[] = "No text ads found. Try switching export type to 'Banner'.";
        }

        // 3. For banner exports, check placement/dimension match
        if ($exportType === 'banner' && $bannerCount > 0) {
            $filters = $contract['filters'] ?? [];
            if (! empty($filters['dimensions'])) {
                [$w, $h] = $this->parseDimensions($filters['dimensions']);
                $dimCount = (int) (clone $baseQuery)
                    ->where('a.creative_type', 'banner')
                    ->where('a.width', $w)
                    ->where('a.height', $h)
                    ->count(DB::raw('DISTINCT a.id'));
                $messages[] = "{$dimCount} banner ad(s) match dimensions {$w}x{$h}.";
            }

            if ($filters['active_sizes_only'] ?? true) {
                $activeSizes = DB::table('placements')
                    ->where('site_id', $siteId)
                    ->where('is_active', 1)
                    ->get(['width', 'height'])
                    ->map(fn ($p) => "{$p->width}x{$p->height}")
                    ->implode(', ');
                $messages[] = "Active placement sizes for this site: {$activeSizes}.";
            }
        }

        return ['messages' => $messages];
    }
}
