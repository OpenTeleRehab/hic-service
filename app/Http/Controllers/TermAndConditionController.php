<?php

namespace App\Http\Controllers;

use App\Http\Resources\TermAndConditionResource;
use App\Models\TermAndCondition;
use Illuminate\Http\Request;

class TermAndConditionController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $termAndConditions = TermAndCondition::all();

        return ['success' => true, 'data' => TermAndConditionResource::collection($termAndConditions)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        TermAndCondition::create([
            'version' => $request->get('version'),
            'content' => $request->get('content'),
        ]);

        return ['success' => true, 'message' => 'success_message.team_and_condition_add'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TermAndCondition $termAndCondition
     *
     * @return array
     */
    public function update(Request $request, TermAndCondition $termAndCondition)
    {
        $termAndCondition->update([
            'version' => $request->get('version'),
            'content' => $request->get('content'),
        ]);

        return ['success' => true, 'message' => 'success_message.team_and_condition_update'];
    }

    /**
     * @return \App\Http\Resources\TermAndConditionResource
     */
    public function getUserTermAndCondition()
    {
        return TermAndCondition::whereNotNull('published_date')
            ->orderBy('published_date', 'desc')
            ->first();
    }
}
