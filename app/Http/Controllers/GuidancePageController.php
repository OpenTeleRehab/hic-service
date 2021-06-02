<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\GuidancePageResource;
use App\Models\Guidance;
use App\Models\StaticPage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Models\File;

class GuidancePageController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $guidancePages = Guidance::all();

        return ['success' => true, 'data' => GuidancePageResource::collection($guidancePages)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $lastOrderingIndex = Guidance::all()->count() + 1;

        Guidance::create([
            'title' => $request->get('title'),
            'content' => $request->get('content'),
            'order' => $lastOrderingIndex
        ]);

        return ['success' => true, 'message' => 'success_message.guidance_add'];
    }

    /**
     * @param \App\Models\Guidance $guidancePage
     *
     * @return \App\Http\Resources\GuidancePageResource
     */
    public function show(Guidance $guidancePage)
    {
        return new GuidancePageResource($guidancePage);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Guidance $guidancePage
     *
     * @return array
     */
    public function update(Request $request, Guidance $guidancePage)
    {
        $guidancePage->update([
            'title' => $request->get('title'),
            'content' => $request->get('content')
        ]);

        return ['success' => true, 'message' => 'success_message.guidance.update'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function updateOrder(Request $request)
    {
        $data = json_decode($request->get('data'));
        $guidancePages = $data->guidancePages;
        foreach ($guidancePages as $index => $guidencePage) {
            $guidancePage = Guidance::updateOrCreate(
                [
                    'id' => isset($guidencePage->id) ? $guidencePage->id : null,
                ],
                [
                    'order' => $index,
                ]
            );
        }

        return ['success' => true, 'message' => 'success_message.guidance.update'];
    }
}
