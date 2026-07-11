<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\Admin\AdminChatController;

// تُحمَّل تحت البادئة api/admin + [auth:sanctum, admin] (bootstrap/app.php).

Route::get('chat', [AdminChatController::class, 'config']);
Route::put('chat/settings', [AdminChatController::class, 'updateSettings']);
Route::get('chat/stats', [AdminChatController::class, 'stats']);
Route::get('chat/threads', [AdminChatController::class, 'threads']);
Route::get('chat/threads/{key}', [AdminChatController::class, 'thread']);
Route::post('chat/assistant-preview', [AdminChatController::class, 'assistantPreview']);
