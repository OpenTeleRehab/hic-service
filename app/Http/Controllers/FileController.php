<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * @param \App\Models\File $file
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function show(File $file)
    {
        return response()->file(storage_path('app/' .$file->path));
    }
}
