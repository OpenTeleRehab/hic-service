<?php

namespace App\Http\Controllers;

use App\Http\Resources\TermAndConditionResource;
use App\Models\TermAndCondition;
use Carbon\Carbon;
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
            'status' => TermAndCondition::STATUS_DRAFT
        ]);

        return ['success' => true, 'message' => 'success_message.team_and_condition_add'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @return array
     */
    public function update(Request $request, $id)
    {
        $termAndCondition = TermAndCondition::findOrFail($id);
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
        $termAndCondition = TermAndCondition::status(TermAndCondition::STATUS_PUBLISHED)
            ->orderBy('published_date', 'desc')
            ->firstOrFail();

        return new TermAndConditionResource($termAndCondition);
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function publish($id)
    {
        // Update the all previous published terms to expired.
        TermAndCondition::where('status', TermAndCondition::STATUS_PUBLISHED)
            ->update(['status' => TermAndCondition::STATUS_EXPIRED]);

        // Set the current term to published.
        TermAndCondition::findOrFail($id)
            ->update([
                'status' => TermAndCondition::STATUS_PUBLISHED,
                'published_date' => Carbon::now()
            ]);

        return ['success' => true, 'message' => 'success_message.team_and_condition_publish'];
    }
}
