<?php

use Illuminate\Support\Facades\Route;
use Modules\Broadcast\Http\Controllers\Admin\AdminBroadcastController;

// تُحمَّل تحت البادئة api/admin + [auth:sanctum, admin] (bootstrap/app.php).

Route::get('broadcasts/stats', [AdminBroadcastController::class, 'stats']);
Route::get('broadcasts/audience', [AdminBroadcastController::class, 'audienceCount']);
Route::get('broadcasts', [AdminBroadcastController::class, 'index']);
Route::post('broadcasts', [AdminBroadcastController::class, 'store']);
