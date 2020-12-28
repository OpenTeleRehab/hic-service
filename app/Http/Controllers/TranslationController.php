<?php

namespace App\Http\Controllers;

use App\Http\Resources\TranslationResource;
use App\Models\Language;
use App\Models\Localization;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TranslationController extends Controller
{
    private const DEFAULT_LANG_CODE = 'en';

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $filterPlatform = $request->get('filter_platform', Translation::ADMIN_PORTAL);

        $languages = Language::where('code', '!=', self::DEFAULT_LANG_CODE)->get()->toArray();
        $localizationValues = [];
        foreach ($languages as $key => $language) {
            $localizationValues[$key] = '(SELECT value FROM localizations WHERE language_id = ' . $language['id'] . ' AND translation_id = translations.id) AS ' . $language['code'];
        }

        $query = Translation::select('id', 'key', 'platform', 'value AS en', DB::raw(implode(',', $localizationValues)))->where('platform', '=', $filterPlatform);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $translations */
        $translations = $query->paginate((int) $request->get('page_size'));

        $info = [
            'current_page' => $translations->currentPage(),
            'total_count' => $translations->total(),
        ];

        return [
            'success' => true,
            'data' => $translations->getCollection()->toArray(),
            'info' => $info,
        ];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $platform
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getI18n(Request $request, $platform)
    {
        $languageId = $request->get('lang');
        if (!$languageId && Auth::user()) {
            $languageId = Auth::user()->language_id;
        }

        if ($languageId) {
            $translations = Translation::select('key', DB::raw('IFNULL(localizations.value, translations.value) as value'))
                ->leftJoin('localizations', function ($join) use ($languageId) {
                    $join->on('localizations.translation_id', '=', 'translations.id');
                    $join->where('localizations.language_id', '=', $languageId);
                })
                ->where('platform', $platform)
                ->get();
        } else {
            $translations = Translation::where('platform', $platform)->get();
        }

        return TranslationResource::collection($translations);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param int $id
     *
     * @return array
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            // Update default language.
            if (isset($data[self::DEFAULT_LANG_CODE])) {
                $translation = Translation::findOrFail($id);
                $translation->fill([
                    'value' => $data[self::DEFAULT_LANG_CODE]
                ])->save();
            }

            // Update other language(s).
            $languages = Language::where('code', '!=', self::DEFAULT_LANG_CODE)->get()->toArray();
            foreach ($languages as $language) {
                if (isset($data[$language['code']])) {
                    $translationValue = $data[$language['code']];
                    $localization = Localization::where('translation_id', $id)->where('language_id', $language['id'])->first();
                    if ($localization) {
                        $localization->fill([
                            'value' => $translationValue
                        ])->save();
                    } else {
                        Localization::create([
                            'translation_id' => $id,
                            'language_id' => $language['id'],
                            'value' => $translationValue
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'error_message.localization_update', 'error_message' => $e->getMessage()];
        }

        return ['success' => true, 'message' => 'success_message.localization_update'];
    }
}
