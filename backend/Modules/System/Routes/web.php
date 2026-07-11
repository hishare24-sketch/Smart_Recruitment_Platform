<?php

use Illuminate\Support\Facades\Route;
use Modules\System\Http\Controllers\Admin\AdminSystemController;

// تُحمَّل تحت البادئة api/admin + [auth:sanctum, admin] (bootstrap/app.php).

Route::get('system/health', [AdminSystemController::class, 'health']);
