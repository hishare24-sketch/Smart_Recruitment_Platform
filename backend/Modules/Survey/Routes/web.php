<?php

use Illuminate\Support\Facades\Route;
use Modules\Survey\Http\Controllers\Admin\AdminSurveyController;

// تُحمَّل تحت البادئة api/admin + [auth:sanctum, admin] (bootstrap/app.php).

Route::get('surveys', [AdminSurveyController::class, 'index']);
Route::post('surveys/{survey}/close', [AdminSurveyController::class, 'close']);
Route::delete('surveys/{survey}', [AdminSurveyController::class, 'destroy']);
