<?php

namespace App\Helpers;

use App\Models\File;
use \Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Lakshmaji\Thumbnail\Facade\Thumbnail;

/**
 * @package App\Helpers
 */
class FileHelper
{
    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $uploadPath
     * @param string $thumbnailPath
     *
     * @return mixed
     */
    public static function createFile(UploadedFile $file, $uploadPath, $thumbnailPath = null)
    {
        $path = $file->store($uploadPath);
        $record = File::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'content_type' => $file->getMimeType(),
        ]);
        if ($thumbnailPath && $file->getMimeType() === 'video/mp4') {
            $thumbnailFilePath = self::generateVideoThumbnail($record->id, $path);

            if ($thumbnailFilePath) {
                $record->update([
                    'thumbnail' => $thumbnailFilePath,
                ]);
            }
        }

        return $record;
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
            if ($file->thumbnail) {
                Storage::delete($file->thumbnail);
            }
            $file->delete();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @param string $fileName
     * @param string $filePath
     *
     * @return string
     */
    private static function generateVideoThumbnail($fileName, $filePath)
    {
        $destinationPath = storage_path('app') . '/' . $filePath;
        $thumbnailPath = storage_path('app') . '/' . File::EXERCISE_THUMBNAIL_PATH;
        $thumbnailImage = $fileName . '.jpg';

        if (!file_exists($thumbnailPath)) {
            mkdir($thumbnailPath);
        }

        Thumbnail::getThumbnail($destinationPath, $thumbnailPath, $thumbnailImage, 1);
        return File::EXERCISE_THUMBNAIL_PATH . '/' . $thumbnailImage;
    }
}
