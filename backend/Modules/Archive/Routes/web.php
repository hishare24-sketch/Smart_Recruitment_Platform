<?php

use Illuminate\Support\Facades\Route;
use Modules\Archive\Http\Controllers\Admin\AdminArchiveController;

// تُحمَّل تحت البادئة api/admin + [auth:sanctum, admin] (bootstrap/app.php).

Route::get('archive', [AdminArchiveController::class, 'index']);
Route::get('archive/stats', [AdminArchiveController::class, 'stats']);
Route::post('archive/restore', [AdminArchiveController::class, 'restore']);
Route::post('archive/purge', [AdminArchiveController::class, 'purge']);
