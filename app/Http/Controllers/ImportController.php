<?php

namespace App\Http\Controllers;

use App\Imports\ImportExercise;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

        $file = $request->file('file');
        Excel::import(new ImportExercise(), $file);

        return ['success' => true, 'message' => 'success_message.exercise_bulk_upload'];
    }
}
