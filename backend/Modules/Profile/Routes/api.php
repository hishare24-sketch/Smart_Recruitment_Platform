<?php

use Illuminate\Support\Facades\Route;
use Modules\Profile\Http\Controllers\Api\ProfileController;

// تُحمَّل تحت البادئة api/v1 (bootstrap/app.php)
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::patch('profile', [ProfileController::class, 'update']);
    Route::post('profile/skills', [ProfileController::class, 'addSkill']);
    Route::delete('profile/skills/{skillId}', [ProfileController::class, 'removeSkill']);
    Route::post('profile/skills/{skillId}/proofs', [ProfileController::class, 'addProof']);
    Route::get('profile/proof-requests', [ProfileController::class, 'proofRequests']);
    Route::post('profile/proof-requests/{id}/resolve', [ProfileController::class, 'resolveProofRequest']);
});
