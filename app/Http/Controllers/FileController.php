<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * @param \App\Models\File $file
     *
     * @return string
     */
    public function show(File $file)
    {
        return Storage::get($file->path);
    }
}
