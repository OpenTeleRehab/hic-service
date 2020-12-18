<?php

namespace App\Http\Controllers;

use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $languages = Language::all();

        return ['success' => true, 'data' => LanguageResource::collection($languages)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array|void
     */
    public function store(Request $request)
    {
        $key = $request->get('code');
        $availableLanguage = Language::where('code', $key)->count();
        if ($availableLanguage) {
            return abort(409, 'error_message.language_exists');
        }

        Language::create([
            'name' => $request->get('name'),
            'code' => $request->get('code')
        ]);

        return ['success' => true, 'message' => 'success_message.language_add'];
    }
}
