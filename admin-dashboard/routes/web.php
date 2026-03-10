<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AdvertiserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GeoRegionController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\SiteController;
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

    // Phase 2 — Advertiser Grid
    Route::get('/advertisers', [AdvertiserController::class, 'index'])->name('advertisers.index');

    // Phase 2 — Ad Review
    Route::get('/ads', fn () => view('ads.index'))->name('ads.index');

    // CSV Export
    Route::get('/export', [ExportController::class, 'index'])->name('export.index');
    Route::post('/export/download', [ExportController::class, 'download'])->name('export.download');
    Route::get('/export/history', [ExportController::class, 'history'])->name('export.history');

    // Sites — index page with placements grid
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');

    // Phase 3 — Placements (nested under sites)
    Route::get('/sites/{site}/placements', fn () => abort(501, 'Coming soon'))->name('sites.placements.index');
    Route::get('/sites/{site}/placements/create', fn () => abort(501, 'Coming soon'))->name('sites.placements.create');
    Route::post('/sites/{site}/placements', fn () => abort(501, 'Coming soon'))->name('sites.placements.store');
    Route::get('/sites/{site}/placements/{placement}', fn () => abort(501, 'Coming soon'))->name('sites.placements.show');
    Route::get('/sites/{site}/placements/{placement}/edit', fn () => abort(501, 'Coming soon'))->name('sites.placements.edit');
    Route::put('/sites/{site}/placements/{placement}', fn () => abort(501, 'Coming soon'))->name('sites.placements.update');
    Route::delete('/sites/{site}/placements/{placement}', fn () => abort(501, 'Coming soon'))->name('sites.placements.destroy');

    // Placement grid page (across all sites)
    Route::get('/placements-grid', [PlacementController::class, 'grid'])->name('placements.grid');

    // Geo Regions CRUD
    Route::get('/geo-regions', [GeoRegionController::class, 'index'])->name('geo-regions.index');
    Route::post('/geo-regions', [GeoRegionController::class, 'store'])->name('geo-regions.store');
    Route::put('/geo-regions/{geoRegion}', [GeoRegionController::class, 'update'])->name('geo-regions.update');
    Route::delete('/geo-regions/{geoRegion}', [GeoRegionController::class, 'destroy'])->name('geo-regions.destroy');

    // Phase 3 — Sync Logs
    Route::get('/sync-logs', fn () => abort(501, 'Coming soon'))->name('sync-logs.index');

    // AJAX endpoints (return JSON) — Phase 2
    Route::prefix('api')->group(function () {

        Route::patch('/advertisers/{advertiser}/country-code', [AdvertiserController::class, 'updateCountryCode'])->name('api.advertisers.country-code.update');
        Route::patch('/advertisers/{advertiser}/rules/{site}', [AdvertiserController::class, 'updateRule'])->name('api.advertisers.rules.update');
        Route::patch('/advertisers/{advertiser}/weight', [AdvertiserController::class, 'updateWeight'])->name('api.advertisers.weight.update');

        Route::post('/advertisers/bulk-rules', [AdvertiserController::class, 'bulkRules'])->name('api.advertisers.bulk-rules');
        Route::post('/advertisers/bulk-weight', [AdvertiserController::class, 'bulkWeight'])->name('api.advertisers.bulk-weight');

        Route::patch('/ads/{ad}/approval', [AdController::class, 'updateApproval'])->name('api.ads.approval.update');
        Route::patch('/ads/{ad}/weight', [AdController::class, 'updateWeight'])->name('api.ads.weight.update');

        Route::post('/ads/bulk-approval', [AdController::class, 'bulkApproval'])->name('api.ads.bulk-approval');
        Route::post('/ads/mark-reviewed', [AdController::class, 'markReviewed'])->name('api.ads.mark-reviewed');

        Route::get('/export/preview', fn () => abort(501))->name('api.export.preview');

        // Sites API (modal CRUD)
        Route::post('/sites', [SiteController::class, 'store'])->name('api.sites.store');
        Route::patch('/sites/{site}', [SiteController::class, 'update'])->name('api.sites.update');
        Route::delete('/sites/{site}', [SiteController::class, 'destroy'])->name('api.sites.destroy');

        // Placements API
        Route::patch('/placements/{placement}/group', [PlacementController::class, 'updateGroup'])->name('api.placements.group.update');
        Route::patch('/placements/{placement}/toggle-active', [PlacementController::class, 'toggleActive'])->name('api.placements.toggle-active');
        Route::post('/placements/add-size', [PlacementController::class, 'addSize'])->name('api.placements.add-size');
        Route::put('/placements/update-size', [PlacementController::class, 'updateSize'])->name('api.placements.update-size');
        Route::delete('/placements/delete-size', [PlacementController::class, 'deleteSize'])->name('api.placements.delete-size');
        Route::post('/placements/bulk-update', [PlacementController::class, 'bulkUpdate'])->name('api.placements.bulk-update');
    });
});