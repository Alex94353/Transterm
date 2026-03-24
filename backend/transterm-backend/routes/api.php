<?php

use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\GlossaryController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\LanguagePairController;
use App\Http\Controllers\Api\TermController;
use App\Http\Controllers\Api\FieldGroupController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\CommentController;
use App\Http\Controllers\Api\Admin\CommentModerationController;
use App\Http\Controllers\Api\Admin\TermController as AdminTermController;
use App\Http\Controllers\Api\Admin\GlossaryController as AdminGlossaryController;
use App\Http\Controllers\Api\Admin\ReferenceController as AdminReferenceController;
use App\Http\Controllers\Api\Admin\FieldController as AdminFieldController;
use App\Http\Controllers\Api\Admin\FieldGroupController as AdminFieldGroupController;
use App\Http\Controllers\Api\Admin\LanguageController as AdminLanguageController;
use App\Http\Controllers\Api\Admin\LanguagePairController as AdminLanguagePairController;
use App\Http\Controllers\Api\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Api\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Api\Admin\UserManagementController;
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

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        Route::get('/comments', [CommentController::class, 'index']);
        Route::put('/comments/{comment}', [CommentController::class, 'update']);
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    });

    Route::post('/terms/{term}/comments', [CommentController::class, 'store']);
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/comments', [CommentModerationController::class, 'index']);
    Route::patch('/comments/{comment}/spam', [CommentModerationController::class, 'markSpam']);
    Route::patch('/comments/{comment}/unspam', [CommentModerationController::class, 'unmarkSpam']);
    Route::delete('/comments/{comment}', [CommentModerationController::class, 'destroy']);

    Route::get('/terms', [AdminTermController::class, 'index']);
    Route::get('/terms/{term}', [AdminTermController::class, 'show']);
    Route::post('/terms', [AdminTermController::class, 'store']);
    Route::put('/terms/{term}', [AdminTermController::class, 'update']);
    Route::delete('/terms/{term}', [AdminTermController::class, 'destroy']);

    Route::get('/glossaries', [AdminGlossaryController::class, 'index']);
    Route::get('/glossaries/{glossary}', [AdminGlossaryController::class, 'show']);
    Route::post('/glossaries', [AdminGlossaryController::class, 'store']);
    Route::put('/glossaries/{glossary}', [AdminGlossaryController::class, 'update']);
    Route::delete('/glossaries/{glossary}', [AdminGlossaryController::class, 'destroy']);

    Route::get('/users', [UserManagementController::class, 'index']);
    Route::get('/users/{user}', [UserManagementController::class, 'show']);
    Route::put('/users/{user}', [UserManagementController::class, 'update']);
    Route::patch('/users/{user}/ban', [UserManagementController::class, 'ban']);
    Route::patch('/users/{user}/unban', [UserManagementController::class, 'unban']);

    Route::get('/references', [AdminReferenceController::class, 'index']);
    Route::get('/references/{reference}', [AdminReferenceController::class, 'show']);
    Route::post('/references', [AdminReferenceController::class, 'store']);
    Route::put('/references/{reference}', [AdminReferenceController::class, 'update']);
    Route::delete('/references/{reference}', [AdminReferenceController::class, 'destroy']);

    Route::get('/fields', [AdminFieldController::class, 'index']);
    Route::get('/fields/{field}', [AdminFieldController::class, 'show']);
    Route::post('/fields', [AdminFieldController::class, 'store']);
    Route::put('/fields/{field}', [AdminFieldController::class, 'update']);
    Route::delete('/fields/{field}', [AdminFieldController::class, 'destroy']);

    Route::get('/field-groups', [AdminFieldGroupController::class, 'index']);
    Route::get('/field-groups/{fieldGroup}', [AdminFieldGroupController::class, 'show']);
    Route::post('/field-groups', [AdminFieldGroupController::class, 'store']);
    Route::put('/field-groups/{fieldGroup}', [AdminFieldGroupController::class, 'update']);
    Route::delete('/field-groups/{fieldGroup}', [AdminFieldGroupController::class, 'destroy']);

    Route::get('/language-pairs', [AdminLanguagePairController::class, 'index']);
    Route::get('/language-pairs/{languagePair}', [AdminLanguagePairController::class, 'show']);
    Route::post('/language-pairs', [AdminLanguagePairController::class, 'store']);
    Route::put('/language-pairs/{languagePair}', [AdminLanguagePairController::class, 'update']);
    Route::delete('/language-pairs/{languagePair}', [AdminLanguagePairController::class, 'destroy']);

    Route::get('/languages', [AdminLanguageController::class, 'index']);
    Route::get('/languages/{language}', [AdminLanguageController::class, 'show']);
    Route::post('/languages', [AdminLanguageController::class, 'store']);
    Route::put('/languages/{language}', [AdminLanguageController::class, 'update']);
    Route::delete('/languages/{language}', [AdminLanguageController::class, 'destroy']);

    Route::get('/roles', [AdminRoleController::class, 'index']);
    Route::get('/roles/{role}', [AdminRoleController::class, 'show']);
    Route::post('/roles', [AdminRoleController::class, 'store']);
    Route::put('/roles/{role}', [AdminRoleController::class, 'update']);
    Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy']);

    Route::get('/permissions', [AdminPermissionController::class, 'index']);
    Route::get('/permissions/{permission}', [AdminPermissionController::class, 'show']);
    Route::post('/permissions', [AdminPermissionController::class, 'store']);
    Route::put('/permissions/{permission}', [AdminPermissionController::class, 'update']);
    Route::delete('/permissions/{permission}', [AdminPermissionController::class, 'destroy']);

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

Route::get('/countries', [CountryController::class, 'index']);
Route::get('/countries/{country}', [CountryController::class, 'show']);

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
});


