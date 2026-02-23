<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Advertiser;
use App\Models\ExportLog;
use App\Models\SiteAdvertiserRule;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'active_advertisers' => Advertiser::where('is_active', true)->count(),
            'pending_rules' => SiteAdvertiserRule::where('rule', 'default')->count(),
            'active_ads' => Ad::where('status', 'active')->where('approval_status', 'approved')->count(),
            'denied_ads' => Ad::where('approval_status', 'denied')->count(),
            'failed_syncs_24h' => SyncLog::where('status', 'failed')
                ->where('started_at', '>=', now()->subDay())
                ->count(),
        ];

        $adsByNetwork = Ad::where('status', 'active')
            ->select('network', DB::raw('count(*) as count'))
            ->groupBy('network')
            ->pluck('count', 'network')
            ->toArray();

        $recentSyncs = SyncLog::orderByDesc('started_at')->limit(5)->get();
        $recentExports = ExportLog::with('site')->orderByDesc('exported_at')->limit(5)->get();

        return view('dashboard.index', compact('stats', 'adsByNetwork', 'recentSyncs', 'recentExports'));
    }
}
