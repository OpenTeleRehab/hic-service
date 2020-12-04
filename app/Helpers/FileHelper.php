<?php

namespace App\Helpers;

use App\Models\File;
use \Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @package App\Helpers
 */
class FileHelper
{
    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $uploadPath
     *
     * @return mixed
     */
    public static function createFile(UploadedFile $file, $uploadPath)
    {
        $path = $file->store($uploadPath);

        return File::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'content_type' => $file->getMimeType(),
        ]);
    }

    /**
     * @param \App\Models\File $file
     *
     * @return bool
     */
    public static function removeFile(File $file)
    {
        try {
            Storage::delete($file->path);
            $file->delete();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}
