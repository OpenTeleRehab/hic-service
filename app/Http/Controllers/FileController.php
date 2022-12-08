<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Models\File;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param integer $id
     *
     * @return BinaryFileResponse
     */
    public function show(Request $request, $id)
    {
        $file = File::find($id);

        if ($request->boolean('thumbnail')) {
            return response()->file(storage_path('app/' . $file->thumbnail));
        }

        return response()->file(storage_path('app/' . $file->path));
    }

    /**
     * @param Request $request
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
