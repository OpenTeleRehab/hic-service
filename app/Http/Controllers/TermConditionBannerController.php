<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\TermConditionBanner;
use Illuminate\Http\Request;

class TermConditionBannerController extends Controller
{
    /**
     *
     * @return array
     */
    public function index()
    {
        $termConditionBanner = TermConditionBanner::first();
        return ['success' => true, 'data' => $termConditionBanner && $termConditionBanner->file ? new FileResource($termConditionBanner->file) : []];
    }

    /**
     * @param TermConditionBanner $termConditionBanner
     *
     * @return FileResource
     */
    public function show(TermConditionBanner $termConditionBanner)
    {
        return new FileResource($termConditionBanner);
    }

    /**
     *
     * @param Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $uploadedFile = $request->file('file');
        $newFile = null;
        if ($uploadedFile) {
            $newFile = FileHelper::createFile($uploadedFile, File::TERM_CONDITION_PATH);
        }
        $termConditionBanner = TermConditionBanner::first();
        if ($termConditionBanner) {
            if ($newFile && $termConditionBanner->file) {
                $termConditionBanner->file->delete();
            }
            $termConditionBanner->update([
                'file_id' => $newFile ? $newFile->id : null,
            ]);
        } else {
            TermConditionBanner::create([
                'file_id' => $newFile ? $newFile->id : null,
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
        return ['success' => true, 'data' => $termConditionBanner && $termConditionBanner->file ? new FileResource($termConditionBanner->file) : []];
    }
}
