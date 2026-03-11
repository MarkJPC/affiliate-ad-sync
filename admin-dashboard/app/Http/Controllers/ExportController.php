<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportRequest;
use App\Models\Advertiser;
use App\Models\ExportLog;
use App\Models\Site;
use App\Services\ExportFilterService;
use App\Services\ExportEngineService;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    public function __construct(private readonly ExportEngineService $engine)
    {
    }

    public function index()
    {
        $sites = Site::where('is_active', true)->orderBy('name')->get();
        $recentExports = ExportLog::with('site')->orderByDesc('exported_at')->limit(10)->get();
        $advertisers = Advertiser::where('is_active', true)->orderBy('name')->get(['id', 'name', 'network']);
        $activeDimensions = DB::table('placements')
            ->where('is_active', 1)
            ->select(DB::raw('DISTINCT width, height'))
            ->orderBy('width')
            ->orderBy('height')
            ->get()
            ->map(fn ($p) => $p->width . 'x' . $p->height)
            ->unique()
            ->values()
            ->all();

        return view('export.index', compact('sites', 'recentExports', 'advertisers', 'activeDimensions'));
    }

    public function history()
    {
        $exports = ExportLog::with('site')
            ->orderByDesc('exported_at')
            ->paginate(25);

        return view('export.history', compact('exports'));
    }

    public function preview(ExportRequest $request)
    {
        $payload = ExportFilterService::normalize($request->validated());
        $site = Site::findOrFail($payload['site_id']);
        $preview = $this->engine->buildPreview($payload);
        $totalRows = (int) ($preview['summary']['total_rows'] ?? 0);

        $response = [
            'status' => 'ok',
            'message' => $totalRows > 0
                ? 'Preview generated.'
                : 'No rows matched the current filters.',
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
            ],
            'contract' => $payload,
            'summary' => $preview['summary'],
            'sample_rows' => $preview['sample_rows'],
            'meta' => $preview['meta'],
        ];

        if (isset($preview['diagnostics'])) {
            $response['diagnostics'] = $preview['diagnostics'];
        }

        return response()->json($response);
    }

    public function download(ExportRequest $request)
    {
        $payload = ExportFilterService::normalize($request->validated());
        $site = Site::findOrFail($payload['site_id']);
        $download = $this->engine->buildDownloadPayload($payload, $site->domain);
        $rowCount = (int) ($download['meta']['row_count'] ?? count($download['rows']));
        $user = auth()->user();

        ExportLog::create([
            'site_id' => $site->id,
            'filename' => $download['filename'],
            'ads_exported' => $rowCount,
            'exported_at' => now(),
            'exported_by' => $user?->email ?? $user?->name ?? 'system',
        ]);

        $exportType = $payload['export_type'];

        return response()->streamDownload(function () use ($download, $exportType) {
            $out = fopen('php://output', 'w');
            if ($exportType === 'banner') {
                fputcsv($out, ['Version', 'AdRotate Professional', '5.26.2']);
            }
            fputcsv($out, $download['headers']);
            foreach ($download['rows'] as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $download['filename'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Export-Row-Count' => (string) $rowCount,
            'X-Export-Empty' => $rowCount === 0 ? '1' : '0',
        ]);
    }
}
