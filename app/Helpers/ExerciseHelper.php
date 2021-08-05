<?php

namespace App\Helpers;

use App\Models\Contributor;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ExerciseHelper
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public static function generateFilterQuery(Request $request)
    {
        $query = Exercise::select('exercises.*');
        $data = $request->all();

        if (isset($data['filters'])) {
            $filters = $request->get('filters');
            $query->where(function ($query) use ($filters) {
                foreach ($filters as $filter) {
                    $filterObj = json_decode($filter);
                    if ($filterObj->columnName === 'status') {
                        $query->where('status', $filterObj->value);
                    } elseif ($filterObj->columnName === 'uploaded_date') {
                        $dates = explode(' - ', $filterObj->value);
                        $startDate = date_create_from_format('d/m/Y', $dates[0]);
                        $endDate = date_create_from_format('d/m/Y', $dates[1]);
                        $startDate->format('Y-m-d');
                        $endDate->format('Y-m-d');
                        $query->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
                    } else {
                        $query->where($filterObj->columnName, 'LIKE', '%' . strtolower($filterObj->value) . '%');
                    }
                }
            });
        }

        $filter = json_decode($request->get('filter'), true);
        if (!empty($filter['search_value'])) {
            $locale = App::getLocale();
            $query->whereRaw("JSON_EXTRACT(LOWER(title), \"$.$locale\") LIKE ?", ['%' . strtolower($filter['search_value']) . '%']);
        }

        if ($request->get('categories')) {
            $categories = $request->get('categories');
            foreach ($categories as $category) {
                $query->whereHas('categories', function ($query) use ($category) {
                    $query->where('categories.id', $category);
                });
            }
        }

        if (Auth::user()) {
            $query->where('status', '!=', Exercise::STATUS_DRAFT);
        } else {
            $query->where('status', Exercise::STATUS_APPROVED);
        }

        return $query;
    }

    /**
     * @param string $email
     * @param string $first_name
     * @param string $comfirmationLink
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function sendEmailNotification($email, $first_name, $comfirmationLink)
    {
        $data = [
            'subject' => 'OpenTeleRehab Library Resource Submission Confirmation',
            'email' => $email,
            'first_name' => $first_name,
            'url' => $comfirmationLink,
        ];

        Mail::send('emails.submission_confirmation', $data, function ($message) use ($data) {
            $message->to($data['email'])->subject($data['subject']);
        });

        return back()->with(['message' => 'Email successfully sent!']);
    }

    /**
     * @param string $first_name
     * @param string $last_name
     * @param string $email
     *
     * @return mixed
     */
    public static function updateOrCreateContributor($first_name, $last_name, $email)
    {
        $contributor = Contributor::updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'first_name' => $first_name,
                'last_name' => $last_name
            ]
        );

        return $contributor;
    }

    /**
     * Validate the email for the given request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    protected function validateEmail(Request $request)
    {
        $request->validate(['email' => 'required|regex:/^([a-zA-Z0-9_\-\.]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z]{2,5})$/i',]);
    }
}
