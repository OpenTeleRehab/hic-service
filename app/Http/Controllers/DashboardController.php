<?php

namespace App\Http\Controllers;

use App\Models\EducationMaterial;
use App\Models\Exercise;
use App\Models\Questionnaire;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * @return array
     */
    public function getStatistics()
    {
        $totalExercise = Exercise::where('status', Exercise::STATUS_APPROVED)->whereNull('edit_translation')->count();
        $exerciseSubmission = Auth::check() ? Exercise::where('status', Exercise::STATUS_PENDING)->whereNull('edit_translation')->count() : 0;
        $exerciseTranslation = Auth::check() ? Exercise::where('status', Exercise::STATUS_DRAFT)->whereNotNull('edit_translation')->count() : 0;

        $totalEducationMaterial = EducationMaterial::where('status', Exercise::STATUS_APPROVED)->whereNull('edit_translation')->count();
        $educationSubmission = Auth::check() ? EducationMaterial::where('status', Exercise::STATUS_PENDING)->whereNull('edit_translation')->count() : 0;
        $educationTranslation = Auth::check() ? EducationMaterial::where('status', EducationMaterial::STATUS_DRAFT)->whereNotNull('edit_translation')->count() : 0;

        $totalQuestionnaire = Questionnaire::where('status', Exercise::STATUS_APPROVED)->whereNull('edit_translation')->count();
        $questionnaireSubmission = Auth::check() ? Questionnaire::where('status', Exercise::STATUS_PENDING)->whereNull('edit_translation')->count() : 0;
        $questionnaireTranslation = Auth::check() ? Questionnaire::where('status', Questionnaire::STATUS_DRAFT)->whereNotNull('edit_translation')->count() : 0;

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
