<?php

use App\Http\Controllers\ClinicController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExerciseController;
use \App\Http\Controllers\FileController;

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

Route::apiResource('admin', AdminController::class);
Route::apiResource('country', CountryController::class);
Route::apiResource('clinic', ClinicController::class);
Route::apiResource('profession', ProfessionController::class);
Route::apiResource('exercise', ExerciseController::class);
Route::get('exercise/list/by-ids', [ExerciseController::class, 'getByIds']);

Route::apiResource('file', FileController::class);
Route::get('translation/i18n/{platform}', [TranslationController::class, 'getI18n']);

Route::get('getDefaultLimitedPatient', [SettingController::class, 'getDefaultLimitedPatient']);

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('user/profile', [ProfileController::class, 'getUserProfile']);
    Route::put('user/update-password', [ProfileController::class, 'updatePassword']);
    Route::put('user/update-information', [ProfileController::class, 'updateUserProfile']);
    Route::apiResource('translation', TranslationController::class);
    Route::apiResource('language', LanguageController::class);
});
