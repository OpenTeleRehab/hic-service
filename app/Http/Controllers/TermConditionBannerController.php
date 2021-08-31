<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\TermConditionBanner;
use Illuminate\Http\Request;
use App\Models\File;
use App\Http\Resources\FileResource;

class TermConditionBannerController extends Controller
{
    /**
     *
     * @return array
     */
    public function index()
    {
        $termConditionBanner = TermConditionBanner::first();
        return ['success' => true, 'data' => $termConditionBanner ? new FileResource($termConditionBanner->file) : [] ];
    }

    /**
     * @param \App\Models\TermConditionBanner $termConditionBanner
     *
     * @return \App\Http\Resources\FileResource
     */
    public function show(TermConditionBanner $termConditionBanner)
    {
        return new FileResource($termConditionBanner);
    }

    /**
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $uploadedFile = $request->file('file');
        $newFile = FileHelper::createFile($uploadedFile, File::TERM_CONDITION_PATH);
        $termConditionBanner = TermConditionBanner::first();
        if ($termConditionBanner) {
            $termConditionBanner->file->delete();
            $termConditionBanner->update([
                'file_id' => $newFile->id,
            ]);
        } else {
            TermConditionBanner::create([
                'file_id' => $newFile->id,
            ]);
        }
        return ['success' => true, 'message' => 'success_message.term_and_condition_banner.save'];
    }

    /**
     * @return array
     */
    public function getTermConditionBanner()
    {
        $termConditionBanner = TermConditionBanner::first();
        return ['success' => true, 'data' => $termConditionBanner ? new FileResource($termConditionBanner->file) : [] ];
    }
}
