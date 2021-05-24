<?php

namespace App\Http\Controllers;

use App\Http\Resources\PrivacyPolicyResource;
use App\Http\Resources\TermAndConditionResource;
use App\Models\PrivacyPolicy;
use App\Models\TermAndCondition;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $privacyPolicies = PrivacyPolicy::all();

        return ['success' => true, 'data' => PrivacyPolicyResource::collection($privacyPolicies)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        PrivacyPolicy::create([
            'version' => $request->get('version'),
            'content' => $request->get('content'),
            'status' => PrivacyPolicy::STATUS_DRAFT
        ]);

        return ['success' => true, 'message' => 'success_message.privacy_policy_add'];
    }

    /**
     * @param \App\Models\PrivacyPolicy $privacyPolicy
     *
     * @return \App\Http\Resources\PrivacyPolicyResource
     */
    public function show(PrivacyPolicy $privacyPolicy) {
        return new PrivacyPolicyResource($privacyPolicy);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @return array
     */
    public function update(Request $request, $id)
    {
        $privacyPolicy = PrivacyPolicy::findOrFail($id);
        $privacyPolicy->update([
            'version' => $request->get('version'),
            'content' => $request->get('content'),
        ]);

        return ['success' => true, 'message' => 'success_message.privacy_policy_update'];
    }

    /**
     * @return \App\Http\Resources\TermAndConditionResource
     */
    public function getUserPrivacyPolicy()
    {
        $privacyPolicy = PrivacyPolicy::where('status', TermAndCondition::STATUS_PUBLISHED)
            ->orderBy('published_date', 'desc')
            ->firstOrFail();

        return new TermAndConditionResource($privacyPolicy);
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function publish($id)
    {
        // Update the all previous published terms to expired.
        PrivacyPolicy::where('status', PrivacyPolicy::STATUS_PUBLISHED)
            ->update(['status' => PrivacyPolicy::STATUS_EXPIRED]);

        // Set the current term to published.
        PrivacyPolicy::findOrFail($id)
            ->update([
                'status' => PrivacyPolicy::STATUS_PUBLISHED,
                'published_date' => Carbon::now()
            ]);

        return ['success' => true, 'message' => 'success_message.privacy_policy_publish'];
    }
}
