<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\Api\MarketplaceController;
use Modules\Marketplace\Http\Controllers\Api\MatchController;

// تُحمَّل تحت البادئة api/v1 (bootstrap/app.php)
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('opportunities', [MarketplaceController::class, 'opportunities']);
    Route::post('opportunities', [MarketplaceController::class, 'storeOpportunity']);
    Route::post('opportunities/why-match', [MatchController::class, 'whyMatch']);
    Route::post('opportunities/{id}/apply', [MarketplaceController::class, 'apply']);
    Route::get('requests/mine', [MarketplaceController::class, 'myRequests']);
    Route::get('requests', [MarketplaceController::class, 'requests']);
});
