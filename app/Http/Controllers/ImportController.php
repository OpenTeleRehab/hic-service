<?php

namespace App\Http\Controllers;

use App\Imports\ImportDisease;
use App\Imports\ImportExercise;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ImportController extends Controller
{

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function importExercises(Request $request)
    {
        if (!$request->has('file')) {
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
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function importDiseases(Request $request)
    {
        if (!$request->has('file')) {
            return ['success' => false, 'message' => 'error_message.disease_upload'];
        }

        $importDisease = new ImportDisease();
        try {
            $file = $request->file('file');
            Excel::import($importDisease, $file);
            return [
                'success' => true,
                'message' => 'success_message.disease_upload',
                'info' => $importDisease->getImportInfo(),
            ];
        } catch (ValidationException $e) {
            $failures = $e->failures();
            return [
                'success' => false,
                'message' => 'error_message.disease_upload',
                'errors' => $failures[0],
            ];
        }
    }
}
