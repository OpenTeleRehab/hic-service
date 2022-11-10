<?php

namespace App\Http\Controllers;

use App\Models\EducationMaterial;
use App\Models\Exercise;
use App\Models\Language;
use App\Models\Questionnaire;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * @return Response
     */
    public function getSitemap()
    {
        $languages = Language::all();
        $exercises = Exercise::all()->where('status', Exercise::STATUS_APPROVED)->whereNull('edit_translation');
        $questionnaires = Questionnaire::all()->where('status', Exercise::STATUS_APPROVED)->whereNull('edit_translation');
        $materials = EducationMaterial::all()->where('status', Exercise::STATUS_APPROVED)->whereNull('edit_translation');
        return response()->view('sitemaps.sitemap', compact('languages', 'exercises', 'questionnaires', 'materials'))->header('Content-Type', 'text/xml');
    }
}
