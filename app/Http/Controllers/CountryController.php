<?php

namespace App\Http\Controllers;

use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $publicIp = trim(current($clientIps));
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
            'language_id' => $request->get('language'),
            'therapist_limit' => $request->get('therapist_limit')
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
            'language_id' => $request->get('language'),
            'therapist_limit' => $request->get('therapist_limit')
        ]);

        return ['success' => true, 'message' => 'success_message.country_update'];
    }

    /**
     * @param \App\Models\Country $country
     *
     * @return array
     * @throws \Exception
     */
    public function destroy(Country $country)
    {
        if (!$country->isUsed()) {
            $country->delete();

            return ['success' => true, 'message' => 'success_message.country_delete'];
        }

        return ['success' => false, 'message' => 'error_message.country_delete'];
    }

    /**
     * @return array
     */
    public function getDefinedCountries()
    {
        $json = Storage::get("country/countries.json");
        $data = json_decode($json, TRUE) ?? [];

        return [
            'success' => true,
            'data' => $data
        ];
    }
}
