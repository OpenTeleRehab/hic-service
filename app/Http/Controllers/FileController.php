<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\File;
use Illuminate\Http\Request;

class FileController extends Controller
{
    /**
     * @param \App\Models\File $file
     * @param \Illuminate\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function show(File $file, Request $request)
    {
        if ($request->boolean('thumbnail')) {
            return response()->file(storage_path('app/' . $file->thumbnail));
        }

        return response()->file(storage_path('app/' . $file->path));
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function uploadFile(Request $request)
    {
        if ($request->hasFile('file')) {
            if ($request->file('file')->isValid()) {
                $file = FileHelper::createFile($request->file('file'), File::FILE);

                return ['success' => true, 'message' => 'success_message.file_upload', 'data' => $file];
            }
        }

        return ['success' => false, 'message' => 'error_message.file_upload'];
    }
}
