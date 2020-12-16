<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClinicResource;
use App\Models\Clinic;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $clinics = Clinic::all();

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
            'city' => $request->get('city')
        ]);

        return ['success' => true, 'message' => 'success_message.clinic_add'];
    }
}
