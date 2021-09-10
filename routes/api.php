<?php

use App\Http\Controllers\ContributorController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\TermAndConditionController;
use App\Http\Controllers\EducationMaterialController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TermConditionBannerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => 'auth:api'], function () {
    Route::apiResource('admin', AdminController::class);
    Route::apiResource('translation', TranslationController::class);
    Route::apiResource('term-condition', TermAndConditionController::class);
    Route::post('term-condition/publish/{id}', [TermAndConditionController::class, 'publish']);
    Route::apiResource('privacy-policy', PrivacyPolicyController::class);
    Route::post('privacy-policy/publish/{id}', [PrivacyPolicyController::class, 'publish']);
    Route::apiResource('static-page', StaticPageController::class);
    Route::post('admin/updateStatus/{user}', [AdminController::class, 'updateStatus']);
    Route::post('admin/resend-email/{user}', [AdminController::class, 'resendEmailToUser']);
    Route::get('admin/get-reviewer/info', [AdminController::class, 'getReviewer']);

    Route::get('user/profile', [ProfileController::class, 'getUserProfile']);
    Route::put('user/update-password', [ProfileController::class, 'updatePassword']);
    Route::put('user/update-information', [ProfileController::class, 'updateUserProfile']);
    Route::put('user/update-last-access', [ProfileController::class, 'updateLastAccess']);

    Route::get('exercise/export/{type}', [ExerciseController::class, 'export']);
    Route::post('import/exercises', [ImportController::class, 'importExercises']);
    Route::post('import/diseases', [ImportController::class, 'importDiseases']);

    Route::post('exercise/cancel-editing/{exercise}', [ExerciseController::class, 'cancelEditing']);
    Route::post('exercise/continue-editing/{exercise}', [ExerciseController::class, 'continueEditing']);
    Route::post('exercise/reject/{exercise}', [ExerciseController::class, 'reject']);

    Route::post('education-material/cancel-editing/{educationMaterial}', [EducationMaterialController::class, 'cancelEditing']);
    Route::post('education-material/continue-editing/{educationMaterial}', [EducationMaterialController::class, 'continueEditing']);
    Route::post('education-material/reject/{educationMaterial}', [EducationMaterialController::class, 'reject']);

    Route::post('questionnaire/cancel-editing/{questionnaire}', [QuestionnaireController::class, 'cancelEditing']);
    Route::post('questionnaire/continue-editing/{questionnaire}', [QuestionnaireController::class, 'continueEditing']);
    Route::post('questionnaire/reject/{questionnaire}', [QuestionnaireController::class, 'reject']);

    Route::post('exercise/approve-translate/{exercise}', [ExerciseController::class, 'approveEditTranslation']);
    Route::post('education-material/approve-translate/{educationMaterial}', [EducationMaterialController::class, 'approveEditTranslation']);
    Route::post('questionnaire/approve-translate/{questionnaire}', [QuestionnaireController::class, 'approveEditTranslation']);

    Route::apiResource('term-condition-banner', TermConditionBannerController::class);
});

// Public access
Route::apiResource('language', LanguageController::class);
Route::get('language/by-id/{id}', [LanguageController::class, 'getById']);
Route::post('file/upload', [FileController::class, 'uploadFile']);
Route::apiResource('file', FileController::class)->middleware('throttle:180:1');
Route::get('page/static-page', [StaticPageController::class, 'getStaticPage']);
Route::get('page/term-condition', [TermAndConditionController::class, 'getTermAndConditionPage']);
Route::get('page/privacy', [PrivacyPolicyController::class, 'getPrivacyPage']);
Route::get('resources/get-feature-resources', [ExerciseController::class, 'getFeaturedResources']);

Route::apiResource('exercise', ExerciseController::class);
Route::get('exercise/list/by-ids', [ExerciseController::class, 'getByIds']);

Route::get('library/cofirmed', [ExerciseController::class, 'getConfirmed'])->name('library.confirmed');

Route::apiResource('education-material', EducationMaterialController::class);
Route::get('education-material/list/by-ids', [EducationMaterialController::class, 'getByIds']);
Route::post('education-material/mark-as-used/by-ids', [EducationMaterialController::class, 'markAsUsed']);

Route::apiResource('questionnaire', QuestionnaireController::class);
Route::get('questionnaire/list/by-ids', [QuestionnaireController::class, 'getByIds']);
Route::post('questionnaire/mark-as-used/by-ids', [QuestionnaireController::class, 'markAsUsed']);

Route::apiResource('category', CategoryController::class);
Route::get('category-tree', [CategoryController::class, 'getCategoryTreeData']);

Route::get('translation/i18n/{platform}', [TranslationController::class, 'getI18n']);
Route::get('user-term-condition', [TermAndConditionController::class, 'getUserTermAndCondition']);
Route::get('user-privacy-policy', [PrivacyPolicyController::class, 'getUserPrivacyPolicy']);

Route::apiResource('contributor', ContributorController::class);
Route::get('contribute/confirm-submission', [ContributorController::class, 'confirmSubmission']);
Route::post('contribute/send-notification', [ContributorController::class, 'sendNotification']);
Route::get('contributor/list/statistics', [ContributorController::class, 'getContributorStatistics']);
Route::post('contributor/update-included-status/{contributor}', [ContributorController::class, 'updateContributorIncludedStatus']);

Route::get('dashboard/statistics', [DashboardController::class, 'getStatistics']);
Route::get('termConditionBanner', [TermConditionBannerController::class, 'getTermConditionBanner']);
