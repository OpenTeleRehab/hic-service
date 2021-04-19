<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        return ['success' => true, 'message' => 'success_message.exercise_bulk_upload'];
    }
}
