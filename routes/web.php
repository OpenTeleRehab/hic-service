<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/clear-contribute-resources', function () {
    \App\Models\Exercise::truncate();
    \App\Models\AdditionalField::truncate();
    \App\Models\ExerciseCategory::truncate();

    \App\Models\EducationMaterial::truncate();
    \App\Models\EducationMaterialCategory::truncate();

    \App\Models\Questionnaire::truncate();
    \App\Models\QuestionnaireCategory::truncate();
    \App\Models\Question::truncate();
    \App\Models\Answer::truncate();

    \App\Models\File::truncate();

    return 'Clear Contribute Resources';
});
