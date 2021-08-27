<?php

namespace App\Http\Controllers;

use App\Models\EducationMaterial;
use App\Models\Exercise;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * @return array
     */
    public function getStatistics()
    {
        // TODO: Count translation submission
        $totalExercise = Exercise::where('status', Exercise::STATUS_APPROVED)->count();
        $exerciseSubmission = Auth::check() ? Exercise::where('status', Exercise::STATUS_PENDING)->count() : 0;
        $exerciseTranslation = Auth::check() ? 0 : 0;

        $totalEducationMaterial = EducationMaterial::where('status', Exercise::STATUS_APPROVED)->count();
        $educationSubmission = Auth::check() ? EducationMaterial::where('status', Exercise::STATUS_PENDING)->count() : 0;
        $educationTranslation = Auth::check() ? 0 : 0;

        $totalQuestionnaire = Questionnaire::where('status', Exercise::STATUS_APPROVED)->count();
        $questionnaireSubmission = Auth::check() ? Questionnaire::where('status', Exercise::STATUS_PENDING)->count() : 0;
        $questionnaireTranslation = Auth::check() ? 0 : 0;

        $data = [
            'exercise' => [
                'total' => $totalExercise,
                'submission' => $exerciseSubmission,
                'translation' => $exerciseTranslation
            ],
            'education' => [
                'total' => $totalEducationMaterial,
                'submission' => $educationSubmission,
                'translation' => $educationTranslation
            ],
            'questionnaire' => [
                'total' => $totalQuestionnaire,
                'submission' => $questionnaireSubmission,
                'translation' => $questionnaireTranslation
            ]
        ];

        return ['success' => true, 'data' => $data];
    }
}
