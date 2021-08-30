<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\TermAndConditionResource;
use App\Models\File;
use App\Models\TermAndCondition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TermAndConditionController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $termAndCondition = TermAndCondition::first();

        return ['success' => true, 'data' => $termAndCondition ? new TermAndConditionResource($termAndCondition) : []];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $uploadedFile = $request->file('file');
        $file = null;
        if ($uploadedFile) {
            $file = FileHelper::createFile($uploadedFile, File::TERM_CONDITION_PATH);
        }

        TermAndCondition::create([
            'title' => $request->get('title'),
            'content' => $request->get('content'),
            'file_id' => $file !== null ? $file->id : $file
        ]);

        return ['success' => true, 'message' => 'success_message.team_and_condition_add'];
    }

    /**
     * @param string $id
     *
     * @return \App\Http\Resources\TermAndConditionResource
     */
    public function show($id)
    {
        $termAndCondition = TermAndCondition::findOrFail($id);
        return new TermAndConditionResource($termAndCondition);
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
        $uploadedFile = $request->file('file');

        if ($uploadedFile) {
            $oldFile = File::find($termAndCondition->file_id);
            if ($oldFile) {
                $oldFile->delete();
            }

            $newFile = FileHelper::createFile($uploadedFile, File::TERM_CONDITION_PATH);
            $termAndCondition->update([
                'file_id' => $newFile->id,
            ]);
        }

        if ($request->get('file') === 'undefined') {
            $oldFile = File::find($termAndCondition->file_id);
            if ($oldFile) {
                $oldFile->delete();
            }
        }
        $termAndCondition->update([
            'title' => $request->get('title'),
            'content' => $request->get('content'),
        ]);

        return ['success' => true, 'message' => 'success_message.team_and_condition_update'];
    }

    /**
     * @return \App\Http\Resources\TermAndConditionResource
     */
    public function getUserTermAndCondition()
    {
        $termAndCondition = TermAndCondition::where('status', TermAndCondition::STATUS_PUBLISHED)
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

        // Add required action to all users.
        Http::get(env('THERAPIST_SERVICE_URL') . '/api/term-condition/send-re-consent');

        return ['success' => true, 'message' => 'success_message.team_and_condition_publish'];
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getTermAndConditionPage()
    {
        $page = TermAndCondition::where('status', TermAndCondition::STATUS_PUBLISHED)
            ->orderBy('published_date', 'desc')
            ->firstOrFail();

        $title = 'Terms of Services - OpenRehab';

        return view('templates.public', compact('page', 'title'));
    }
}
