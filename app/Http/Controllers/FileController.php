<?php

namespace App\Http\Controllers;

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
}
