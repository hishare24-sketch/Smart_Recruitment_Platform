<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\Admin\AdminTicketController;

// تُحمَّل تحت البادئة api/admin + [auth:sanctum, admin] (bootstrap/app.php).

Route::get('tickets/stats', [AdminTicketController::class, 'stats']);
Route::get('tickets', [AdminTicketController::class, 'index']);
Route::get('tickets/{ticket}', [AdminTicketController::class, 'show']);
Route::post('tickets/{ticket}/reply', [AdminTicketController::class, 'reply']);
Route::put('tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus']);
Route::post('tickets/{ticket}/assign', [AdminTicketController::class, 'assign']);
