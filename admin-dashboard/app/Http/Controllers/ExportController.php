<?php

namespace App\Http\Controllers;

use App\Models\ExportLog;
use App\Models\Site;
use Illuminate\Http\Request;

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

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'export_type' => ['nullable', 'in:banner,text'],
        ]);

        $site = Site::findOrFail((int) $validated['site_id']);
        $exportType = $validated['export_type'] ?? 'banner';

        return response()->json([
            'status' => 'ok',
            'message' => 'Preview endpoint scaffold is ready.',
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
            ],
            'export_type' => $exportType,
            'summary' => [
                'rows' => 0,
                'note' => 'Data preview will be added in the next implementation step.',
            ],
        ]);
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'export_type' => ['nullable', 'in:banner,text'],
        ]);

        $site = Site::findOrFail((int) $validated['site_id']);
        $exportType = $validated['export_type'] ?? 'banner';
        $filename = "{$site->domain}-" . now()->format('Y-m-d-His') . "-{$exportType}.csv";

        $headers = ['status', 'message', 'site', 'export_type'];
        $rows = [[
            'scaffold',
            'Download endpoint is wired. Dataset mapping is next.',
            $site->domain,
            $exportType,
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
