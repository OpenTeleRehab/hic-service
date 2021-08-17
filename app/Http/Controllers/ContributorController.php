<?php

namespace App\Http\Controllers;

use App\Helpers\ContributeHelper;
use App\Http\Resources\ContributorResource;
use App\Models\Contributor;
use App\Models\Exercise;
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
        $query = Exercise::where('hash', $hash);

        if ($query->count()) {
            $exercises = $query->where('created_at', '>', Carbon::now()->subHour(config('settings.link_expiration')))->get();

            if (count($exercises) === 0) {
                return ['success' => false, 'message' => [
                    'title' => 'contribute.submission_expired.title',
                    'text' => 'contribute.submission_expired.text'
                ]];
            } else {
                foreach ($exercises as $exercise) {
                    try {
                        $exercise->update([
                            'status' => Exercise::STATUS_PENDING
                        ]);
                    } catch (\Exception $e) {
                        return ['success' => false, 'message' => $e->getMessage()];
                    }
                }

                return ['success' => true, 'message' => [
                    'title' => 'contribute.submission_confirmed.title',
                    'text' => 'contribute.submission_confirmed.text'
                ]];
            }
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
