<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Imports\ImportExercise;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

class ImportController extends Controller
{

    /**
     * @param Request $request
     *
     * @return array
     */
    public function importExercises(Request $request)
    {
        if (!$this->isValidSpreadsheet($request)) {
            return ['success' => false, 'message' => 'error_message.exercise_bulk_upload'];
        }

        $importExercise = new ImportExercise();
        try {
            $file = $request->file('file');
            Excel::import($importExercise, $file);
            return [
                'success' => true,
                'message' => 'success_message.exercise_bulk_upload',
                'info' => $importExercise->getImportInfo(),
            ];
        } catch (ValidationException $e) {
            $failures = $e->failures();
            return [
                'success' => false,
                'message' => 'error_message.exercise_bulk_upload',
                'errors' => [
                    'failures' => $failures,
                    'sheet' => $importExercise->getCurrentSheetName()
                ],
            ];
        } catch (SpreadsheetException $e) {
            // The file passed the type check but cannot be read as a workbook (e.g. a zip renamed to .xlsx).
            return ['success' => false, 'message' => 'error_message.exercise_bulk_upload'];
        }
    }

    /**
     * Only accept Excel spreadsheets, checked against the file content (not just the name).
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    private function isValidSpreadsheet(Request $request)
    {
        $file = $request->file('file');

        return $file instanceof UploadedFile
            && FileHelper::isAllowedFileType($file, FileHelper::ALLOWED_SPREADSHEET_TYPES);
    }
}
