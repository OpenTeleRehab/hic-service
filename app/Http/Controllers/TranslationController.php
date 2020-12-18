<?php

namespace App\Http\Controllers;

use App\Http\Resources\TranslationResource;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TranslationController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $translations = Translation::all();

        return ['success' => true, 'data' => TranslationResource::collection($translations)];
    }

    /**
     * @param string $platform
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getI18n($platform)
    {
        // Todo: apply sys_lang.
        $translations = Translation::where('platform', $platform)->get();

        return TranslationResource::collection($translations);
    }
}
