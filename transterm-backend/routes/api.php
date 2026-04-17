<?php

use App\Http\Controllers\Api\Public\FieldController;
use App\Http\Controllers\Api\Public\GlossaryController;
use App\Http\Controllers\Api\Public\LanguageController;
use App\Http\Controllers\Api\Public\LanguagePairController;
use App\Http\Controllers\Api\Public\TermController;
use App\Http\Controllers\Api\Public\FieldGroupController;
use App\Http\Controllers\Api\Public\ReferenceController;
use App\Http\Controllers\Api\Public\CountryController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\CommentController;
use App\Http\Controllers\Api\User\EditorRoleRequestController as UserEditorRoleRequestController;
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
use App\Http\Controllers\Api\Admin\EditorRoleRequestController as AdminEditorRoleRequestController;
use App\Http\Controllers\Api\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Api\Teacher\EditorAssignmentController as TeacherEditorAssignmentController;
use App\Http\Controllers\Api\Teacher\GlossaryApprovalController as TeacherGlossaryApprovalController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::middleware(['auth:sanctum', 'active.user', 'not.banned'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware(['auth:sanctum', 'active.user', 'not.banned'])->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        Route::get('/comments', [CommentController::class, 'index']);
        Route::put('/comments/{comment}', [CommentController::class, 'update']);
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

        Route::get('/editor-role-requests/latest', [UserEditorRoleRequestController::class, 'latest']);
        Route::post('/editor-role-requests', [UserEditorRoleRequestController::class, 'store']);
    });

    Route::post('/terms/{term}/comments', [CommentController::class, 'store']);
});

Route::middleware([
    'auth:sanctum',
    'active.user',
    'not.banned',
    'permission:glossary.approve|admin.access',
    'throttle:30,1',
])->prefix('teacher')->group(function () {
    Route::get('/glossaries', [TeacherGlossaryApprovalController::class, 'index']);
    Route::get('/glossaries/{glossary}', [TeacherGlossaryApprovalController::class, 'show']);
    Route::patch('/glossaries/{glossary}/approve', [TeacherGlossaryApprovalController::class, 'approve']);
});

Route::middleware([
    'auth:sanctum',
    'active.user',
    'not.banned',
    'permission:editor.assign|admin.access',
    'throttle:30,1',
])->prefix('teacher')->group(function () {
    Route::get('/students', [TeacherEditorAssignmentController::class, 'index']);
    Route::patch('/students/{user}/assign-editor', [TeacherEditorAssignmentController::class, 'grant']);
    Route::patch('/users/{user}/grant-editor', [TeacherEditorAssignmentController::class, 'grant']);
});

Route::middleware(['auth:sanctum', 'active.user', 'not.banned', 'permission:editor.access|admin.access'])->prefix('editor')->group(function () {
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

    // Read-only lookup endpoints needed by glossary/term forms.
    Route::get('/fields', [AdminFieldController::class, 'index']);
    Route::get('/fields/{field}', [AdminFieldController::class, 'show']);
    Route::get('/language-pairs', [AdminLanguagePairController::class, 'index']);
    Route::get('/language-pairs/{languagePair}', [AdminLanguagePairController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'active.user', 'not.banned', 'permission:admin.access'])->prefix('admin')->group(function () {
    Route::get('/comments', [CommentModerationController::class, 'index']);
    Route::patch('/comments/{comment}/spam', [CommentModerationController::class, 'markSpam']);
    Route::patch('/comments/{comment}/unspam', [CommentModerationController::class, 'unmarkSpam']);
    Route::delete('/comments/{comment}', [CommentModerationController::class, 'destroy']);

    Route::get('/users', [UserManagementController::class, 'index']);
    Route::get('/users/{user}', [UserManagementController::class, 'show']);
    Route::put('/users/{user}', [UserManagementController::class, 'update']);
    Route::patch('/users/{user}/base-role', [UserManagementController::class, 'setBaseRole']);
    Route::patch('/users/{user}/editor/grant', [UserManagementController::class, 'grantEditor']);
    Route::patch('/users/{user}/editor/revoke', [UserManagementController::class, 'revokeEditor']);
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
    Route::patch('/users/{user}/ban', [UserManagementController::class, 'ban']);
    Route::patch('/users/{user}/unban', [UserManagementController::class, 'unban']);

    Route::get('/editor-role-requests', [AdminEditorRoleRequestController::class, 'index']);
    Route::patch('/editor-role-requests/{editorRoleRequest}/approve', [AdminEditorRoleRequestController::class, 'approve']);
    Route::patch('/editor-role-requests/{editorRoleRequest}/reject', [AdminEditorRoleRequestController::class, 'reject']);
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);

    Route::get('/references', [AdminReferenceController::class, 'index']);
    Route::get('/references/{reference}', [AdminReferenceController::class, 'show']);
    Route::post('/references', [AdminReferenceController::class, 'store']);
    Route::put('/references/{reference}', [AdminReferenceController::class, 'update']);
    Route::delete('/references/{reference}', [AdminReferenceController::class, 'destroy']);

    Route::post('/fields', [AdminFieldController::class, 'store']);
    Route::put('/fields/{field}', [AdminFieldController::class, 'update']);
    Route::delete('/fields/{field}', [AdminFieldController::class, 'destroy']);

    Route::get('/field-groups', [AdminFieldGroupController::class, 'index']);
    Route::get('/field-groups/{fieldGroup}', [AdminFieldGroupController::class, 'show']);
    Route::post('/field-groups', [AdminFieldGroupController::class, 'store']);
    Route::put('/field-groups/{fieldGroup}', [AdminFieldGroupController::class, 'update']);
    Route::delete('/field-groups/{fieldGroup}', [AdminFieldGroupController::class, 'destroy']);

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
