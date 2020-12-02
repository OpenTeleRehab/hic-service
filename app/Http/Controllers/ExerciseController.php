<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $exercises = Exercise::all();

        return ['success' => true, 'data' => ExerciseResource::collection($exercises)];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        $title = $request->get('title');
        $includeFeedback = $request->get('include_feedback');

        $exercise = Exercise::create([
            'title' => $title,
            'include_feedback' => $includeFeedback
        ]);

        if ($exercise) {
            return ['success' => true, 'message' => 'success_message.exercise_create'];
        }
        return ['success' => false, 'message' => 'error_message.exercise_create'];
    }

    /**
     * @param \App\Models\Exercise $exercise
     *
     * @return array
     * @throws \Exception
     */
    public function destroy(Exercise $exercise)
    {
        if ($exercise->canDelete()) {
            // Todo: delete media resources.
            $exercise->delete();
            return ['success' => true, 'message' => 'success_message.exercise_delete'];
        }
        return ['success' => false, 'message' => 'error_message.exercise_delete'];
    }
}
