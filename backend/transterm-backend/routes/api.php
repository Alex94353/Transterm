<?php

use App\Http\Controllers\Api\GlossaryController;
use App\Http\Controllers\Api\TermController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/glossaries', [GlossaryController::class, 'index']);
Route::get('/glossaries/{glossary}', [GlossaryController::class, 'show']);

Route::get('/terms', [TermController::class, 'index']);
Route::get('/terms/{term}', [TermController::class, 'show']);
