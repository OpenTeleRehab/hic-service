<?php

namespace App\Http\Controllers;

use App\Http\Resources\TranslationResource;
use App\Models\Language;
use App\Models\Localization;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;

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

        $languages = Language::all()->where('code', '!=', self::DEFAULT_LANG_CODE)->toArray();
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
     * @param string $platform
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getI18n($platform)
    {
        $user = Auth::user();
        if ($user && $user->language_id) {
            $translations = Translation::select('key', DB::raw('IFNULL(localizations.value, translations.value) as value'))
                ->leftJoin('localizations', function ($join) use ($user) {
                    $join->on('localizations.translation_id', '=', 'translations.id');
                    $join->where('localizations.language_id', '=', $user->language_id);
                })
                ->where('platform', $platform)
                ->get();

        } else {
            $translations = Translation::where('platform', $platform)->get();
        }

        return TranslationResource::collection($translations);
    }

    public function update(Request $request, $id)
    {
        try {
            $languages = Language::get()->toArray();
            $translation = Translation::findOrFail($id);
            $data = $request->all();
            $defaultLang = $data['en'];

            unset($languages[0]);
            foreach ($languages as $key => $language ) {
                $localization = Localization::where('translation_id', $translation->id)
                    ->where('language_id', $language['id'])->first();

                $localization->fill([
                    'value' => $data[$language['code']]
                ]);

                $localization->save();
            }

            $translation->fill([
                'value' => $defaultLang
            ]);

            $translation->save();

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'message' => 'success_message.localization_update'];
    }
}
