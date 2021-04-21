<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClinicResource;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ClinicController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $query = Clinic::select('clinics.*');

        $countryId = $request->get('country_id');
        if (!$countryId && Auth::user()) {
            $countryId = Auth::user()->country_id;
        }

        if ($countryId) {
            $query->where('clinics.country_id', $countryId);
        }

        $clinics = $query->get();

        return ['success' => true, 'data' => ClinicResource::collection($clinics)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array|void
     */
    public function store(Request $request)
    {
        Clinic::create([
            'name' => $request->get('name'),
            'country_id' => $request->get('country'),
            'region' => $request->get('region'),
            'province' => $request->get('province'),
            'city' => $request->get('city'),
            'therapist_limit' => $request->get('therapist_limit')
        ]);

        return ['success' => true, 'message' => 'success_message.clinic_add'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Clinic $clinic
     *
     * @return array
     */
    public function update(Request $request, Clinic $clinic)
    {
        $clinic->update([
            'name' => $request->get('name'),
            'region' => $request->get('region'),
            'province' => $request->get('province'),
            'city' => $request->get('city'),
            'therapist_limit' => $request->get('therapist_limit')
        ]);

        return ['success' => true, 'message' => 'success_message.clinic_update'];
    }

    /**
     * @param \App\Models\Clinic $clinic
     *
     * @return array
     * @throws \Exception
     */
    public function destroy(Clinic $clinic)
    {
        if (!$clinic->is_used) {
            $clinic->delete();

            return ['success' => true, 'message' => 'success_message.clinic_delete'];
        }
        return ['success' => false, 'message' => 'error_message.clinic_delete'];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function countTherapistLimitByCountry(Request $request)
    {
        $countryId = $request->get('country_id');
        $therapistLimitTotal = DB::table('clinics')
            ->select(DB::raw('
                SUM(therapist_limit) AS total
            '))
            ->where('country_id', $countryId)
            ->get()->first();

        return [
            'success' => true,
            'data' => $therapistLimitTotal
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function countTherapistByClinic(Request $request)
    {
        $clinicId = $request->get('clinic_id');

        $therapistData = [];
        $response = Http::get(env('THERAPIST_SERVICE_URL') . '/api/chart/get-data-for-clinic-admin', [
            'clinic_id' => [$clinicId]
        ]);

        if (!empty($response) && $response->successful()) {
            $therapistData = $response->json();
        }

        return [
            'success' => true,
            'data' => $therapistData
        ];
    }
}
