<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfessionResource;
use App\Models\Profession;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function getLanguage(Request $request)
    {
        $languages = [
            ['id' => 1, 'name' => 'English'],
            ['id' => 2, 'name' => 'Khmer'],
            ['id' => 3, 'name' => 'Vietnamese']
        ];
        return ['success' => true, 'data' => $languages];
    }

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
}
