<?php

namespace App\Http\Controllers;

use App\Helpers\ContributeHelper;
use App\Http\Resources\ContributorResource;
use App\Models\Contributor;
use App\Models\EducationMaterial;
use App\Models\Exercise;
use App\Models\Questionnaire;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContributorController extends Controller
{
    /**
     *
     * @return array
     */
    public function index()
    {
        $contributors = Contributor::all();

        return ['success' => true, 'data' => ContributorResource::collection($contributors)];
    }

    /**
     * @param \App\Models\Contributor $contributor
     *
     * @return \App\Http\Resources\ContributorResource
     */
    public function show(Contributor $contributor)
    {
        return new ContributorResource($contributor);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function confirmSubmission(Request $request)
    {
        $hash = $request->get('hash');
        $query_exercise = Exercise::where('hash', $hash);
        $query_education = EducationMaterial::where('hash', $hash);
        $query_questionnaire = Questionnaire::where('hash', $hash);

        // Handle update status
        if ($query_exercise->count()) {
            $exercise_expired = Exercise::where('hash', $hash)->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->count();
            $query_exercise->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->update(['status' => Exercise::STATUS_PENDING]);
        }

        if ($query_education->count()) {
            $education_expired = EducationMaterial::where('hash', $hash)->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->count();
            $query_education->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->update(['status' => EducationMaterial::STATUS_PENDING]);
        }

        if ($query_questionnaire->count()) {
            $questionnaire_expired = Questionnaire::where('hash', $hash)->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->count();
            $query_questionnaire->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->update(['status' => Questionnaire::STATUS_PENDING]);
        }

        $query_exercise = Exercise::where('hash', $hash);
        $query_education = EducationMaterial::where('hash', $hash);
        $query_questionnaire = Questionnaire::where('hash', $hash);

        // Handle response message
        if ($query_exercise->count() || $query_education->count() || $query_questionnaire->count()) {
            if ((isset($exercise_expired) && $exercise_expired === 0) || (isset($education_expired) && $education_expired === 0) || (isset($questionnaire_expired) && $questionnaire_expired === 0)) {
                return ['success' => false, 'message' => [
                    'title' => 'contribute.submission_expired.title',
                    'text' => 'contribute.submission_expired.text'
                ]];
            }

            return ['success' => true, 'message' => [
                'title' => 'contribute.submission_success.title',
                'text' => 'contribute.submission_success.text'
            ]];
        } else {
            return ['success' => false, 'message' => [
                'title' => 'contribute.submission_incorrect.title',
                'text' => 'contribute.submission_incorrect.text'
            ]];
        }
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function sendNotification(Request $request)
    {
        $email = $request->get('email');
        $first_name = $request->get('first_name');
        $hash = $request->get('hash');

        // Send email notification with hash link validity
        $url = env('REACT_APP_CONTRIBUTE_CONFIRM_URL') . '?hash=' . $hash;
        ContributeHelper::sendEmailNotification($email, $first_name, $url);

        return ['success' => true, 'message' => 'success_message.contribute_create'];
    }

    /**
     *
     * @return array
     */
    public function getContributorStatistics () {
        $totalExerciseUpload = DB::table('exercises')
        ->select(DB::raw('
            uploaded_by,
            COUNT(*) AS total_upload
        '))
        ->where('status',Exercise::STATUS_APPROVED)
        ->where('edit_translation', null)
        ->groupBy('uploaded_by')
        ->get();

        $totalMaterialUpload = DB::table('education_materials')
        ->select(DB::raw('
            uploaded_by,
            COUNT(*) AS total_upload
        '))
        ->where('status',Exercise::STATUS_APPROVED)
        ->where('edit_translation', null)
        ->groupBy('uploaded_by')
        ->get();

        $totalQuestionnaireUpload = DB::table('questionnaires')
        ->select(DB::raw('
            uploaded_by,
            COUNT(*) AS total_upload
        '))
        ->where('status',Exercise::STATUS_APPROVED)
        ->where('edit_translation', null)
        ->groupBy('uploaded_by')
        ->get();


        $totalExerciseTranslation = DB::table('exercises')
        ->select(DB::raw('
            uploaded_by,
            COUNT(*) AS total_translation
        '))
        ->where('status',Exercise::STATUS_APPROVED)
        ->where('edit_translation', '<>', null)
        ->groupBy('uploaded_by')
        ->get();

        $totalMaterialTranslation = DB::table('education_materials')
        ->select(DB::raw('
            uploaded_by,
            COUNT(*) AS total_translation
        '))
        ->where('status',Exercise::STATUS_APPROVED)
        ->where('edit_translation', '<>', null)
        ->groupBy('uploaded_by')
        ->get();

        $totalQuestionnaireTranslation = DB::table('questionnaires')
        ->select(DB::raw('
            uploaded_by,
            COUNT(*) AS total_translation
        '))
        ->where('status',Exercise::STATUS_APPROVED)
        ->where('edit_translation', '<>', null)
        ->groupBy('uploaded_by')
        ->get();

        $data = [
            'exercise' => [
                'totalUpload' => $totalExerciseUpload,
                'totalTranslation' => $totalExerciseTranslation
            ],
            'education' => [
                'totalUpload' => $totalMaterialUpload,
                'totalTranslation' => $totalMaterialTranslation
            ],
            'questionnaire' => [
                'totalUpload' => $totalQuestionnaireUpload,
                'totalTranslation' => $totalQuestionnaireTranslation
            ]
        ];

        return ['success' => true, 'data' => $data];

    }

    /**
     * @param \Illuminate\App\Models\Contrutor $contributor
     *
     * @param \Illuminate\Http\Request $request
     */
    public function updateContributorIncludedStatus(Contributor $contributor, Request $request) {
        $includedInAcknowledgment = $request->boolean('included_in_acknowledgment');
        $contributor->update(['included_in_acknowledgment' => $includedInAcknowledgment]);
        return ['success' => true];
    }
}
