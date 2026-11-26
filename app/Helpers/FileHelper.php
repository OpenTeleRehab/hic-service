<?php

namespace App\Helpers;

use App\Models\File;
use \Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Spatie\PdfToImage\Pdf;

/**
 * @package App\Helpers
 */
class FileHelper
{
    /**
     * Allowed upload types, as groups of file extensions and of MIME types detected from the file content.
     *
     * A file is allowed when its extension and its detected MIME type are in the same group. The security
     * check is the MIME type: active formats such as SVG/HTML/PHP are in no group, and a file is stored
     * under a name whose extension is guessed from its content (UploadedFile::hashName()), never the client's.
     * Inside a group a mismatch is accepted, because renamed files (a PNG screenshot saved as .jpg, an
     * iPhone .mp4 detected as QuickTime) are common and harmless. Audio and video share a group since
     * their containers overlap (an .m4a is detected as video/mp4, an .ogg as video/ogg).
     */
    const ALLOWED_FILE_TYPES = [
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff', 'heic', 'heif'],
            'mime_types' => [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/x-ms-bmp',
                'image/tiff', 'image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence',
            ],
        ],
        'audio_video' => [
            'extensions' => [
                'mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac',
                'mp4', 'm4v', 'mov', 'webm', '3gp', 'avi', 'mkv', 'mpeg', 'mpg', 'wmv',
            ],
            'mime_types' => [
                'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/vnd.wave', 'audio/ogg', 'video/ogg', 'application/ogg',
                'audio/mp4', 'audio/x-m4a', 'audio/aac', 'audio/x-aac', 'audio/x-hx-aac-adts', 'audio/flac',
                'audio/x-flac', 'audio/webm', 'audio/3gpp', 'video/mp4', 'video/x-m4v', 'video/quicktime',
                'video/webm', 'video/3gpp', 'video/x-msvideo', 'video/x-matroska', 'video/mpeg', 'video/x-ms-wmv',
                'video/x-ms-asf',
            ],
        ],
        'pdf' => [
            'extensions' => ['pdf'],
            'mime_types' => ['application/pdf'],
        ],
    ];

    /**
     * Allowed spreadsheet types for the exercise bulk import. Excel's legacy .xls is an OLE
     * container that libmagic may report as CDFV2, and some tools write .xlsx files that it
     * only recognises as a plain zip archive. The extension picks the reader, so the groups
     * are kept apart: an .xlsx must contain a zip, an .xls an OLE file.
     */
    const ALLOWED_SPREADSHEET_TYPES = [
        'xlsx' => [
            'extensions' => ['xlsx'],
            'mime_types' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        ],
        'xls' => [
            'extensions' => ['xls'],
            'mime_types' => ['application/vnd.ms-excel', 'application/CDFV2', 'application/x-ole-storage'],
        ],
    ];

    const ERROR_FILE_UPLOAD = 'error_message.file_upload';
    const ERROR_FILE_TYPE_NOT_ALLOWED = 'error_message.file_type_not_allowed';

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $uploadPath
     * @param string $thumbnailPath
     *
     * @return \App\Models\File
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function createFile(UploadedFile $file, $uploadPath, $thumbnailPath = null)
    {
        // Callers validate up front; this keeps a file that skipped that check from being stored.
        if ($error = self::validateUploadedFiles([$file])) {
            throw ValidationException::withMessages(['file' => $error]);
        }

        $path = $file->store($uploadPath);
        $mimeType = $file->getMimeType();

        $record = File::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'content_type' => $mimeType,
            'size' => $file->getSize()
        ]);

        if ($thumbnailPath && $mimeType === 'video/mp4') {
            $thumbnailFilePath = self::generateVideoThumbnail($record->id, $path, $thumbnailPath);

            if ($thumbnailFilePath) {
                $record->update([
                    'thumbnail' => $thumbnailFilePath,
                ]);
            }
        }

        if ($thumbnailPath && $mimeType === 'application/pdf') {
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
     * The extension and the MIME type detected from the file content must be in the same allowed group.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param array $allowedTypes groups of ['extensions' => [...], 'mime_types' => [...]]
     *
     * @return boolean
     */
    public static function isAllowedFileType(UploadedFile $file, array $allowedTypes = self::ALLOWED_FILE_TYPES)
    {
        // A failed upload (e.g. over upload_max_filesize) has no temporary file to inspect.
        if (!$file->isValid()) {
            return false;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        foreach ($allowedTypes as $group) {
            if (in_array($extension, $group['extensions'], true) && in_array($mimeType, $group['mime_types'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a stored file's content type is one that may be uploaded today. Files stored before the
     * allow-list (e.g. SVG) are not, and must not be rendered inline by the browser.
     *
     * @param string|null $mimeType
     *
     * @return boolean
     */
    public static function isAllowedMimeType($mimeType)
    {
        foreach (self::ALLOWED_FILE_TYPES as $group) {
            if (in_array($mimeType, $group['mime_types'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check every uploaded file of a request (e.g. $request->allFiles()).
     *
     * @param array $files
     *
     * @return string|null translation key of the first error, or null when all files are allowed
     */
    public static function validateUploadedFiles(array $files)
    {
        foreach (Arr::flatten($files) as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            if (!$file->isValid()) {
                return self::ERROR_FILE_UPLOAD;
            }

            if (!self::isAllowedFileType($file)) {
                return self::ERROR_FILE_TYPE_NOT_ALLOWED;
            }
        }

        return null;
    }
}
