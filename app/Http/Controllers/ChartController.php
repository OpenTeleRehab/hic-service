<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ChartController extends Controller
{

    /**
     * @return array
     */
    public function getDataForAdminDashboard()
    {
        $globalAdminTotal = User::where('type', User::ADMIN_GROUP_GLOBAL_ADMIN)->count();
        $countryAdminTotal = User::where('type', User::ADMIN_GROUP_COUNTRY_ADMIN)->count();
        $clinicAdminTotal = User::where('type', User::ADMIN_GROUP_CLINIC_ADMIN)->count();
        $clinicAdminsByCountry = DB::table('users')
            ->select(DB::raw('
                country_id,
                COUNT(*) AS total
            '))->where('type', User::ADMIN_GROUP_CLINIC_ADMIN)
            ->groupBy('country_id')
            ->get();
        $patientData = [];
        $therapistData = [];

        $response = Http::get(env('PATIENT_SERVICE_URL') . '/api/chart/get-data-for-global-admin');

        if (!empty($response) && $response->successful()) {
            $patientData = $response->json();
        }

        $response = Http::get(env('THERAPIST_SERVICE_URL') . '/api/chart/get-data-for-global-admin');

        if (!empty($response) && $response->successful()) {
            $therapistData = $response->json();
        }

        $data = [
            'globalAdminTotal' => $globalAdminTotal,
            'countryAdminTotal' => $countryAdminTotal,
            'clinicAdminTotal' => $clinicAdminTotal,
            'clinicAdminsByCountry' => $clinicAdminsByCountry,
            'patientData' => $patientData,
            'therapistData' => $therapistData,
        ];
        return ['success' => true, 'data' => $data];
    }
}
