<?php

namespace App\Http\Controllers;

use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CountryController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $countries = Country::all();

        return ['success' => true, 'data' => CountryResource::collection($countries)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array|void
     */
    public function store(Request $request)
    {
        $isoCode = $request->get('iso_code');
        $availableCountry = Country::where('iso_code', $isoCode)->count();
        if ($availableCountry) {
            return abort(409, 'error_message.country_exists');
        }

        Country::create([
            'iso_code' => $isoCode,
            'name' => $request->get('name'),
            'phone_code' => $request->get('phone_code'),
            'language_id' => $request->get('language')
        ]);

        return ['success' => true, 'message' => 'success_message.country_add'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Country $country
     *
     * @return array|void
     */
    public function update(Request $request, Country $country)
    {
        $isoCode = $request->get('iso_code');
        $availableCountry = Country::where('id', '<>', $country->id)
            ->where('iso_code', $isoCode)
            ->count();
        if ($availableCountry) {
            return abort(409, 'error_message.country_exists');
        }

        $country->update([
            'iso_code' => $isoCode,
            'name' => $request->get('name'),
            'phone_code' => $request->get('phone_code'),
            'language_id' => $request->get('language')
        ]);

        return ['success' => true, 'message' => 'success_message.country_update'];
    }
}
