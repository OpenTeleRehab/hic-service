<?php

namespace App\Http\Middleware;

use App\Helpers\FileHelper;
use Closure;
use Illuminate\Http\Request;

/**
 * Rejects a request when any uploaded file is not an allowed media type (see FileHelper::ALLOWED_FILE_TYPES).
 *
 * Runs on every API route, so a new upload endpoint is covered without having to remember the check.
 * A route that accepts other types (e.g. the spreadsheet import) opts out with withoutMiddleware()
 * and checks its files itself.
 */
class ValidateUploadedFileTypes
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($error = FileHelper::validateUploadedFiles($request->allFiles())) {
            // Same shape as the controllers' own error responses.
            return response()->json(['success' => false, 'message' => $error]);
        }

        return $next($request);
    }
}
