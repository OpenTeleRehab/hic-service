<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfessionResource;
use App\Models\Profession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ProfessionController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $countryId = $request->get('country_id');

        if (!$countryId && Auth::user()) {
            $countryId = Auth::user()->country_id;
        }

        $professions = Profession::where('professions.country_id', $countryId)->get();
        return ['success' => true, 'data' => ProfessionResource::collection($professions)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array|void
     */
    public function store(Request $request)
    {
        $countryId = Auth::user()->country_id;
        $name = $request->get('name');
        $existedProfession = Profession::where('country_id', $countryId)
            ->where('name', $name)
            ->count();

        if ($existedProfession) {
            return abort(409, 'error_message.profession_exists');
        }

        Profession::create([
            'name' => $name,
            'country_id' => $countryId,
        ]);

        return ['success' => true, 'message' => 'success_message.profession_create'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Profession $profession
     *
     * @return array|void
     */
    public function update(Request $request, Profession $profession)
    {
        $countryId = Auth::user()->country_id;
        $name = $request->get('name');
        $existedProfession = Profession::where('id', '<>', $profession->id)
            ->where('country_id', $countryId)
            ->where('name', $name)
            ->count();

        if ($existedProfession) {
            return abort(409, 'error_message.profession_exists');
        }

        $profession->update([
            'name' => $name,
            'country_id' => $countryId,
        ]);

        return ['success' => true, 'message' => 'success_message.profession_update'];
    }

    /**
     * @param \App\Models\Profession $profession
     *
     * @return array
     * @throws \Exception
     */
    public function destroy(Profession $profession)
    {
        $isUsed = false;
        $response = Http::get(env('THERAPIST_SERVICE_URL') . '/api/therapist/get-used-profession?profession_id=' . $profession->id);

        if (!empty($response) && $response->successful()) {
            $isUsed = $response->json();
        }

        if (!$isUsed) {
            $profession->delete();

            return ['success' => true, 'message' => 'success_message.profession_delete'];
        }

        return ['success' => false, 'message' => 'error_message.profession_delete'];
    }
}
