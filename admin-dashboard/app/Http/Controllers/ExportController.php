<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportRequest;
use App\Models\ExportLog;
use App\Models\Site;
use App\Services\ExportFilterService;

class ExportController extends Controller
{
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

        return response()->json([
            'status' => 'ok',
            'message' => 'Preview endpoint scaffold is ready.',
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
            ],
            'contract' => $payload,
            'summary' => [
                'rows' => 0,
                'note' => 'Data preview will be added in the next implementation step.',
            ],
        ]);
    }

    public function download(ExportRequest $request)
    {
        $payload = ExportFilterService::normalize($request->validated());
        $site = Site::findOrFail($payload['site_id']);
        $filename = "{$site->domain}-" . now()->format('Y-m-d-His') . "-{$payload['export_type']}.csv";

        $headers = ['status', 'message', 'site', 'export_type', 'network', 'dimensions', 'active_sizes_only'];
        $rows = [[
            'scaffold',
            'Download endpoint is wired with shared contract. Dataset mapping is next.',
            $site->domain,
            $payload['export_type'],
            $payload['filters']['network'] ?? '',
            $payload['filters']['dimensions'] ?? '',
            $payload['filters']['active_sizes_only'] ? '1' : '0',
        ]];

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
