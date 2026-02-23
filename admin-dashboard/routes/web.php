<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Guest-only
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Phase 2 — Advertiser Grid (stub routes for sidebar links)
    Route::get('/advertisers', fn () => abort(501, 'Coming soon'))->name('advertisers.index');

    // Phase 2 — Ad Review
    Route::get('/ads', fn () => abort(501, 'Coming soon'))->name('ads.index');

    // Phase 2 — CSV Export
    Route::get('/export', fn () => abort(501, 'Coming soon'))->name('export.index');
    Route::post('/export/download', fn () => abort(501, 'Coming soon'))->name('export.download');
    Route::get('/export/history', fn () => abort(501, 'Coming soon'))->name('export.history');

    // Phase 3 — Sites CRUD
    Route::get('/sites', fn () => abort(501, 'Coming soon'))->name('sites.index');
    Route::get('/sites/create', fn () => abort(501, 'Coming soon'))->name('sites.create');
    Route::post('/sites', fn () => abort(501, 'Coming soon'))->name('sites.store');
    Route::get('/sites/{site}', fn () => abort(501, 'Coming soon'))->name('sites.show');
    Route::get('/sites/{site}/edit', fn () => abort(501, 'Coming soon'))->name('sites.edit');
    Route::put('/sites/{site}', fn () => abort(501, 'Coming soon'))->name('sites.update');
    Route::delete('/sites/{site}', fn () => abort(501, 'Coming soon'))->name('sites.destroy');

    // Phase 3 — Placements (nested under sites)
    Route::get('/sites/{site}/placements', fn () => abort(501, 'Coming soon'))->name('sites.placements.index');
    Route::get('/sites/{site}/placements/create', fn () => abort(501, 'Coming soon'))->name('sites.placements.create');
    Route::post('/sites/{site}/placements', fn () => abort(501, 'Coming soon'))->name('sites.placements.store');
    Route::get('/sites/{site}/placements/{placement}', fn () => abort(501, 'Coming soon'))->name('sites.placements.show');
    Route::get('/sites/{site}/placements/{placement}/edit', fn () => abort(501, 'Coming soon'))->name('sites.placements.edit');
    Route::put('/sites/{site}/placements/{placement}', fn () => abort(501, 'Coming soon'))->name('sites.placements.update');
    Route::delete('/sites/{site}/placements/{placement}', fn () => abort(501, 'Coming soon'))->name('sites.placements.destroy');

    // Phase 3 — Sync Logs
    Route::get('/sync-logs', fn () => abort(501, 'Coming soon'))->name('sync-logs.index');

    // AJAX endpoints (return JSON) — Phase 2
    Route::prefix('api')->group(function () {
        Route::patch('/advertisers/{advertiser}/rules/{site}', fn () => abort(501))->name('api.advertisers.rules.update');
        Route::patch('/advertisers/{advertiser}/weight', fn () => abort(501))->name('api.advertisers.weight.update');
        Route::post('/advertisers/bulk-rules', fn () => abort(501))->name('api.advertisers.bulk-rules');
        Route::post('/advertisers/bulk-weight', fn () => abort(501))->name('api.advertisers.bulk-weight');
        Route::patch('/ads/{ad}/approval', fn () => abort(501))->name('api.ads.approval.update');
        Route::post('/ads/bulk-approval', fn () => abort(501))->name('api.ads.bulk-approval');
        Route::get('/export/preview', fn () => abort(501))->name('api.export.preview');
    });
});
