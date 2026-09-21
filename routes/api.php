<?php

use App\Http\Controllers\AiRecruitmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/evaluate-cv', [AiRecruitmentController::class, 'evaluate']); // one CV
Route::post('/rank-cvs', [AiRecruitmentController::class, 'rank']);        // many CVs -> ranked top
Route::post('/chat', [AiRecruitmentController::class, 'chat']);
