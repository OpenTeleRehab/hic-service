<?php

namespace App\Http\Controllers;

use App\Helpers\ContentHelper;
use App\Http\Resources\ProfessionResource;
use App\Http\Resources\SystemLimitResource;
use App\Models\Profession;
use App\Models\SystemLimit;
use Illuminate\Http\Request;

class SystemLimitController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $staticPages = SystemLimit::all();

        return ['success' => true, 'data' => SystemLimitResource::collection($staticPages)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\SystemLimit $systemLimit
     *
     * @return array
     */
    public function update(Request $request, SystemLimit $systemLimit)
    {
        if (!is_null($request->get('value'))) {
            $systemLimit->update([
                'value' => $request->get('value')
            ]);
        }

        return ['success' => true, 'message' => 'success_message.system_limit_update'];
    }

    /**
     * @param Request $request
     * @return int
     */
    public function getContentLimitForLibrary(Request $request)
    {
        $type = $request->get('type');
        $contentLimit = ContentHelper::getContentLimitLibray($type);

        return $contentLimit;
    }
}
