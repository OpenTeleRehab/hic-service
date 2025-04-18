<?php

namespace App\Helpers;

use App\Models\File;
use \Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Spatie\PdfToImage\Pdf;

/**
 * @package App\Helpers
 */
class FileHelper
{
    const DEFAULT_EXT = ['image', 'audio', 'video', 'pdf'];

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $uploadPath
     * @param string $thumbnailPath
     *
     * @return mixed
     */
    public static function createFile(UploadedFile $file, $uploadPath, $thumbnailPath = null)
    {
        if (!self::validateMimeType($file)) {
            return false;
        };

        $path = $file->store($uploadPath);

        $record = File::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'content_type' => $file->getMimeType(),
            'size' => $file->getSize()
        ]);

        if ($thumbnailPath && $file->getMimeType() === 'video/mp4') {
            $thumbnailFilePath = self::generateVideoThumbnail($record->id, $path, $thumbnailPath);

            if ($thumbnailFilePath) {
                $record->update([
                    'thumbnail' => $thumbnailFilePath,
                ]);
            }
        }

        if ($thumbnailPath && $file->getMimeType() === 'application/pdf') {
            $thumbnailFilePath = self::generatePdfThumbnail($record->id, $path, $thumbnailPath);

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
     * @return \App\Models\File
     */
    public static function replicateFile(File $file)
    {
        $fileName = pathinfo($file->path, PATHINFO_FILENAME);
        $newFilePath = str_replace($fileName, Str::random(40), $file->path);
        Storage::copy($file->path, $newFilePath);

        $newFile = $file->replicate();
        $newFile->path = $newFilePath;
        $newFile->save();

        return $newFile;
    }

    /**
     * @param string $fileName
     * @param string $filePath
     * @param string $thumbnailFilePath
     *
     * @return string
     */
    public static function generateVideoThumbnail($fileName, $filePath, $thumbnailFilePath)
    {
        $thumbnailPath = storage_path('app') . '/' . $thumbnailFilePath;

        if (!file_exists($thumbnailPath)) {
            mkdir($thumbnailPath);
        }

        FFMpeg::open($filePath)
            ->getFrameFromSeconds(1)
            ->export()
            ->save("$thumbnailFilePath/$fileName.jpg");

        return "$thumbnailFilePath/$fileName.jpg";
    }

    /**
     * @param string $fileName
     * @param string $filePath
     * @param string $thumbnailFilePath
     *
     * @return string
     */
    public static function generatePdfThumbnail($fileName, $filePath, $thumbnailFilePath)
    {
        $destinationPath = storage_path('app') . '/' . $filePath;
        $thumbnailPath = storage_path('app') . '/' . $thumbnailFilePath;
        $thumbnailImage = $fileName . '.jpg';

        if (!file_exists($thumbnailPath)) {
            mkdir($thumbnailPath);
        }

        $pdf = new Pdf($destinationPath);
        $pdf->setResolution(48);
        $pdf->saveImage($thumbnailPath . '/' . $thumbnailImage);

        return $thumbnailFilePath . '/' . $thumbnailImage;
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @return boolean
     */
    private static function validateMimeType(UploadedFile $file)
    {
        foreach (self::DEFAULT_EXT as $value) {
            if (str_contains($file->getMimeType(), $value)) {
                return true;
            }
        }

        return false;
    }
}
