<?php

namespace App\Http\Controllers;

use App\Models\EducationMaterial;
use App\Models\Exercise;
use App\Models\Language;
use App\Models\Questionnaire;

class SitemapController extends Controller
{
    /**
     * @return \Illuminate\Http\Response
     */
    public function getSitemap()
    {
        $languages = Language::all();
        $exercises = Exercise::all();
        $questionnaires = Questionnaire::all();
        $materials = EducationMaterial::all();
        return response()->view('sitemaps.sitemap', compact('languages', 'exercises', 'questionnaires', 'materials'))->header('Content-Type', 'text/xml');
    }
}
