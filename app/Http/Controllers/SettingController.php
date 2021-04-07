<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfessionResource;
use App\Http\Resources\SystemLimitResource;
use App\Models\Profession;
use App\Models\SystemLimit;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function getDefaultLimitedPatient(Request $request)
    {
        $defaultLimitedPatient = ['value' => 15];

        return ['success' => true, 'data' => $defaultLimitedPatient];
    }

    /**
     * @return array
     */
    public function index()
    {
        $systemLimits = SystemLimit::all();

        return ['success' => true,
            'data' => [
                'system_limits' => SystemLimitResource::collection($systemLimits)
            ]
        ];
    }

}
