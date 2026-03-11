<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class SyncLogController extends Controller
{
    public function index()
    {
        return view('sync-logs.index');
    }

    public function trigger(): JsonResponse
    {
        $token = config('services.github.token');
        $repo = config('services.github.repo');

        if (! $token) {
            return response()->json(['error' => 'GitHub token not configured'], 422);
        }

        $response = Http::withToken($token)
            ->post("https://api.github.com/repos/{$repo}/actions/workflows/sync.yml/dispatches", [
                'ref' => 'main',
            ]);

        if ($response->status() === 204) {
            return response()->json(['message' => 'Sync workflow dispatched successfully'], 200);
        }

        return response()->json([
            'error' => 'Failed to trigger sync',
            'status' => $response->status(),
            'body' => $response->json(),
        ], $response->status());
    }
}
