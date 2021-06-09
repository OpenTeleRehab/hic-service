<?php

namespace App\Http\Controllers;

use App\Http\Resources\InternationalClassificationDiseaseResource;
use App\Models\InternationalClassificationDisease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InternationalClassificationDiseaseController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $internationalClassificationDiseases = InternationalClassificationDisease::all();
        return ['success' => true, 'data' => InternationalClassificationDiseaseResource::collection($internationalClassificationDiseases)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array|void
     */
    public function store(Request $request)
    {
        $name = $request->get('name');
        $existedDisease = InternationalClassificationDisease::where('name', $name)
            ->count();

        if ($existedDisease) {
            return abort(409, 'error_message.disease_exists');
        }

        InternationalClassificationDisease::create([
            'name' => $name
        ]);

        return ['success' => true, 'message' => 'success_message.disease_create'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\InternationalClassificationDisease $disease
     *
     * @return array|void
     */
    public function update(Request $request, InternationalClassificationDisease $disease)
    {
        $name = $request->get('name');
        $existedDisease = InternationalClassificationDisease::where('id', '<>', $disease->id)
            ->where('name', $name)
            ->count();

        if ($existedDisease) {
            return abort(409, 'error_message.disease_exists');
        }

        $disease->update([
            'name' => $name,
        ]);

        return ['success' => true, 'message' => 'success_message.disease_update'];
    }

    /**
     * @param \App\Models\InternationalClassificationDisease $disease
     *
     * @return array
     * @throws \Exception
     */
    public function destroy(InternationalClassificationDisease $disease)
    {
        $isUsed = false;
        $response = Http::get(env('PATIENT_SERVICE_URL') . '/api/treatment-plan/get-used-disease?disease_id=' . $disease->id);

        if (!empty($response) && $response->successful()) {
            $isUsed = $response->json();
        }

        if (!$isUsed) {
            $disease->delete();

            return ['success' => true, 'message' => 'success_message.disease_delete'];
        }

        return ['success' => false, 'message' => 'error_message.disease_delete'];
    }

    /**
     * @param Request $request
     * @return int
     */
    public function getDiseaseNameById(Request $request)
    {
        $id = $request->get('disease_id');
        $disease = InternationalClassificationDisease::where('id', $id)->first();

        return $disease ? $disease->name : '';
    }
}
