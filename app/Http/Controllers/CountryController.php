<?php

namespace App\Http\Controllers;

use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;

class CountryController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $countries = Country::all();
        $userCountryCode = null;
        $clientIps = explode(',', \request()->ip());
        $publicIp = trim(end($clientIps));
        if ($publicIp && $position = Location::get($publicIp)) {
            $userCountryCode = $position->countryCode;
        }

        return [
            'success' => true,
            'data' => CountryResource::collection($countries),
            'user_country_code' => $userCountryCode,
        ];
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
