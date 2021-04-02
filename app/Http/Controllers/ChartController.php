
<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $clinicAdmins = User::where('type', User::ADMIN_GROUP_CLINIC_ADMIN)->get();
        $patientData = [];

        $response = Http::get(env('PATIENT_SERVICE_URL') . '/api/chart/get-data-for-global-admin');

        if (!empty($response) && $response->successful()) {
            if ($response->json()['data']) {
                $patientData = $response->json()['data'];
            }
        }

        $data = [
            'globalAdminTotal' => $globalAdminTotal,
            'countryAdminTotal' => $countryAdminTotal,
            'clinicAdminTotal' => count($clinicAdmins),
            'patientData' => $patientData,
        ];
        return ['success' => true, 'data' => $data];
    }
}
