<?php

use App\Http\Controllers\ClinicController;
use App\Http\Controllers\CountryController;
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
Route::apiResource('file', FileController::class);
Route::apiResource('translation', TranslationController::class);
Route::get('translation/i18n/{platform}', [TranslationController::class, 'getI18n']);

Route::get('getLanguage', [SettingController::class, 'getLanguage']);
Route::get('getDefaultLimitedPatient', [SettingController::class, 'getDefaultLimitedPatient']);
Route::get('user/profile/{username}', [AdminController::class, 'getUserProfile']);
Route::put('user/update-password/{username}', [ProfileController::class, 'updatePassword']);
Route::put('user/update-information/{id}', [ProfileController::class, 'updateUserProfile']);
