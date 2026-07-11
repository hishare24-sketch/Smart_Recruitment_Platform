<?php

use Illuminate\Support\Facades\Route;
use Modules\Ai\Http\Controllers\Admin\AdminAiController;

// تُحمَّل تحت البادئة api/admin + [auth:sanctum, admin] (bootstrap/app.php).

Route::get('ai', [AdminAiController::class, 'config']);
Route::get('ai/stats', [AdminAiController::class, 'stats']);
Route::put('ai/settings', [AdminAiController::class, 'updateSettings']);
Route::put('ai/quotas', [AdminAiController::class, 'updateQuotas']);
Route::post('ai/capabilities/{capability}/toggle', [AdminAiController::class, 'toggleCapability']);
Route::post('ai/knowledge', [AdminAiController::class, 'storeKnowledge']);
Route::put('ai/knowledge/{knowledge}', [AdminAiController::class, 'updateKnowledge']);
Route::delete('ai/knowledge/{knowledge}', [AdminAiController::class, 'destroyKnowledge']);
