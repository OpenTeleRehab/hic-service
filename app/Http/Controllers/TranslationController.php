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
        $data = $request->all();
        $query = Translation::where(function ($query) use ($data) {
            $query->where('key', 'like', '%' . $data['search_value'] . '%')
                ->orWhere('value', 'like', '%' . $data['search_value'] . '%');
        });

        if (isset($data['filters'])) {
            $filters = $request->get('filters');
            $query->where(function ($query) use ($filters) {
                foreach ($filters as $filter) {
                    $filterObj = json_decode($filter);
                    $query->where($filterObj->columnName, 'like', '%' .  $filterObj->value . '%');
                }
            });
        }

        if (isset($data['filter_value'])) {
            $query->where('platform', 'like', '%' . $data['filter_value'] . '%');
        }

        $translations = $query->paginate($request->get('page_size'));

        $info = [
            'current_page' => $translations->currentPage(),
            'total_count' => $translations->total(),
        ];

        return [
            'success' => true,
            'data' => TranslationResource::collection($translations),
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
        // Todo: apply sys_lang.
        $sysLang = 2;
        $translations = Translation::select('key', DB::raw('IFNULL(localizations.value, translations.value) as value'))
            ->leftJoin('localizations', function ($join) use ($sysLang) {
                $join->on('localizations.translation_id', '=', 'translations.id');
                $join->where('localizations.sys_lang', '=', $sysLang);
            })
            ->where('platform', $platform)
            ->get();
        return TranslationResource::collection($translations);
    }
}
