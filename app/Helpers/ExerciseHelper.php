<?php

namespace App\Helpers;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ExerciseHelper
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public static function generateFilterQuery(Request $request)
    {
        $query = Exercise::select('exercises.*');
        $filter = json_decode($request->get('filter'), true);

        if (!empty($filter['search_value'])) {
            $locale = App::getLocale();
            $query->whereRaw("JSON_EXTRACT(LOWER(title), \"$.$locale\") LIKE ?", ['%' . strtolower($filter['search_value']) . '%']);
        }

        if ($request->get('categories')) {
            $categories = $request->get('categories');
            foreach ($categories as $category) {
                $query->whereHas('categories', function ($query) use ($category) {
                    $query->where('categories.id', $category);
                });
            }
        }

        return $query;
    }
}
