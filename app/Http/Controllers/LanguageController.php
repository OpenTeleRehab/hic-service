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
        $code = $request->get('code');
        $availableLanguage = Language::where('code', $code)->count();
        if ($availableLanguage) {
            return abort(409, 'error_message.language_exists');
        }

        Language::create([
            'name' => $request->get('name'),
            'code' => $code,
        ]);

        return ['success' => true, 'message' => 'success_message.language_add'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Language $language
     *
     * @return array|void
     */
    public function update(Request $request, Language $language)
    {
        $code = $request->get('code');
        $availableLanguage = Language::where('id', '<>', $language->id)
            ->where('code', $code)
            ->count();
        if ($availableLanguage) {
            return abort(409, 'error_message.language_exists');
        }

        $language->update([
            'name' => $request->get('name'),
            'code' => $code,
        ]);

        return ['success' => true, 'message' => 'success_message.language_update'];
    }

    /**
     * @param \App\Models\Language $language
     *
     * @return array
     * @throws \Exception
     */
    public function destroy(Language $language)
    {
        if (!$language->isUsed()) {
            $language->delete();
            return ['success' => true, 'message' => 'success_message.language_delete'];
        }
        return ['success' => false, 'message' => 'error_message.language_delete'];
    }
}
