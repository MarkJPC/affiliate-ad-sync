<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportRequest;
use App\Models\ExportLog;
use App\Models\Site;
use App\Services\ExportFilterService;
use App\Services\ExportEngineService;

class ExportController extends Controller
{
    public function __construct(private readonly ExportEngineService $engine)
    {
    }

    public function index()
    {
        $sites = Site::where('is_active', true)->orderBy('name')->get();
        $recentExports = ExportLog::with('site')->orderByDesc('exported_at')->limit(10)->get();

        return view('export.index', compact('sites', 'recentExports'));
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

        return response()->json([
            'status' => 'ok',
            'message' => 'Preview generated.',
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
            ],
            'contract' => $payload,
            'summary' => $preview['summary'],
            'sample_rows' => $preview['sample_rows'],
            'meta' => $preview['meta'],
        ]);
    }

    public function download(ExportRequest $request)
    {
        $payload = ExportFilterService::normalize($request->validated());
        $site = Site::findOrFail($payload['site_id']);
        $download = $this->engine->buildDownloadPayload($payload, $site->domain);
        $user = auth()->user();

        ExportLog::create([
            'site_id' => $site->id,
            'filename' => $download['filename'],
            'ads_exported' => $download['meta']['row_count'] ?? count($download['rows']),
            'exported_at' => now(),
            'exported_by' => $user?->email ?? $user?->name ?? 'system',
        ]);

        return response()->streamDownload(function () use ($download) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $download['headers']);
            foreach ($download['rows'] as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $download['filename'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
