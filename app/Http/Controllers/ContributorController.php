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

class ContributorController extends Controller
{
    /**
     * @return array
     */
    public function index()
    {
        $contributors = Contributor::all();

        return ['success' => true, 'data' => ContributorResource::collection($contributors)];
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

        // Handle update status
        if ($query_exercise->count()) {
            $exercise_expired = Exercise::where('hash', $hash)->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->count();
            $query_exercise->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->update(['status' => Exercise::STATUS_PENDING]);
        }

        if ($query_education->count()) {
            $education_expired = EducationMaterial::where('hash', $hash)->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->count();
            $query_education->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->update(['status' => EducationMaterial::STATUS_PENDING]);
        }

        $query_exercise = Exercise::where('hash', $hash);
        $query_education = EducationMaterial::where('hash', $hash);

        // Handle response message
        if ($query_exercise->count() || $query_education->count()) {
            if ((isset($exercise_expired) && $exercise_expired === 0) || (isset($education_expired) && $education_expired === 0)) {
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
}
