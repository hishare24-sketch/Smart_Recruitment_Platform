<?php

use Illuminate\Support\Facades\Route;
use Modules\PublicProfile\Http\Controllers\Api\PublicProfileController;

// تُحمَّل تحت البادئة api/v1 (bootstrap/app.php)

// المالك (مصادقة) — قبل مسار {slug} كي لا يُلتقط "me" كـ slug
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('public-profiles/me', [PublicProfileController::class, 'showMine']);
    Route::patch('public-profiles/me', [PublicProfileController::class, 'updateMine']);
});

// العام (بلا مصادقة)
Route::get('public-profiles/{slug}', [PublicProfileController::class, 'show']);
Route::post('public-profiles/{slug}/view', [PublicProfileController::class, 'view']);
Route::post('public-profiles/{slug}/follow', [PublicProfileController::class, 'follow']);
Route::post('public-profiles/{slug}/rate', [PublicProfileController::class, 'rate']);
Route::post('public-profiles/{slug}/comments', [PublicProfileController::class, 'comment']);
Route::post('public-profiles/{slug}/contact', [PublicProfileController::class, 'contact']);
Route::post('public-profiles/{slug}/schedule', [PublicProfileController::class, 'schedule']);
Route::post('public-profiles/{slug}/testimonials', [PublicProfileController::class, 'testimonial']);
Route::post('public-profiles/{slug}/proof-requests', [PublicProfileController::class, 'proofRequest']);
