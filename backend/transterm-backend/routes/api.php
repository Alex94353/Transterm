<?php

use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\GlossaryController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\LanguagePairController;
use App\Http\Controllers\Api\TermController;
use App\Http\Controllers\Api\FieldGroupController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::get('/glossaries', [GlossaryController::class, 'index']);
Route::get('/glossaries/{glossary}', [GlossaryController::class, 'show']);

Route::get('/terms', [TermController::class, 'index']);
Route::get('/terms/{term}', [TermController::class, 'show']);

Route::get('/fields', [FieldController::class, 'index']);
Route::get('/fields/{field}', [FieldController::class, 'show']);

Route::get('/languages', [LanguageController::class, 'index']);
Route::get('/languages/{language}', [LanguageController::class, 'show']);

Route::get('/language-pairs', [LanguagePairController::class, 'index']);
Route::get('/language-pairs/{languagePair}', [LanguagePairController::class, 'show']);

Route::get('/field-groups', [FieldGroupController::class, 'index']);
Route::get('/field-groups/{fieldGroup}', [FieldGroupController::class, 'show']);

Route::get('/references', [ReferenceController::class, 'index']);
Route::get('/references/{reference}', [ReferenceController::class, 'show']);
