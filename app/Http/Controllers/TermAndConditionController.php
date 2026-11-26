<?php

namespace App\Http\Controllers;

use App\Helpers\HtmlSanitizer;
use App\Http\Resources\TermAndConditionResource;
use App\Models\TermAndCondition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

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
     * @param Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        if (!$this->isValidPolicyInput($request)) {
            return ['success' => false, 'message' => self::ERROR_INVALID_INPUT];
        }

        TermAndCondition::create([
            'version' => $request->input('version'),
            'content' => HtmlSanitizer::clean($request->input('content')),
            'status' => TermAndCondition::STATUS_DRAFT,
            'title' => $request->input('title'),
        ]);

        return ['success' => true, 'message' => 'success_message.term_and_condition_add'];
    }

    /**
     * @param string $id
     *
     * @return TermAndConditionResource
     */
    public function show($id)
    {
        $termAndCondition = TermAndCondition::findOrFail($id);
        return new TermAndConditionResource($termAndCondition);
    }

    /**
     * @param Request $request
     * @param string $id
     *
     * @return array
     */
    public function update(Request $request, $id)
    {
        if (!$this->isValidPolicyInput($request)) {
            return ['success' => false, 'message' => self::ERROR_INVALID_INPUT];
        }

        $termAndCondition = TermAndCondition::findOrFail($id);
        $termAndCondition->update([
            'version' => $request->input('version'),
            'content' => HtmlSanitizer::clean($request->input('content')),
            'title' => $request->input('title'),
        ]);

        return ['success' => true, 'message' => 'success_message.term_and_condition_update'];
    }

    /**
     * @return TermAndConditionResource
     */
    public function getUserTermAndCondition()
    {
        $termAndCondition = TermAndCondition::where('status', TermAndCondition::STATUS_PUBLISHED)
            ->orderBy('published_date', 'desc')
            ->first();

        return $termAndCondition ? new TermAndConditionResource($termAndCondition) : [];
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

    /**
     * @return View
     */
    public function getTermAndConditionPage()
    {
        $page = TermAndCondition::where('status', TermAndCondition::STATUS_PUBLISHED)
            ->orderBy('published_date', 'desc')
            ->firstOrFail();

        $title = 'Terms of Services - OpenRehab';

        return view('templates.public', compact('page', 'title'));
    }

    /**
     * Content is rich text (HTML) that gets sanitized, so it must be a single string.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isValidPolicyInput(Request $request)
    {
        return Validator::make($request->all(), [
            'version' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
        ])->passes();
    }
}
