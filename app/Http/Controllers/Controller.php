<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Spec as OA;

/**
 * Class Controller
 * @package App\Http\Controllers
 */
#[OA\Info(title: 'OpentRehab Admin API', version: '0.1')]
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Returned as the response message when request input fails validation.
     */
    const ERROR_INVALID_INPUT = 'error_message.invalid_input';

    /**
     * Instantiate a new Controller instance.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $languageId = $request->input('lang');
        if (!$request->has('lang') && Auth::user()) {
            $languageId = Auth::user()->language_id;
        }

        if ($languageId) {
            $language = Language::find($languageId);
            if ($language) {
                App::setLocale(strtolower($language->code));
            }
        }
    }

    /**
     * Contributor details are sent by anonymous users on the public submission form and shown to reviewers,
     * so they must be plain text: no markup in names and a real e-mail address.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function isValidContributorInput(Request $request)
    {
        return Validator::make($request->all(), [
            'first_name' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            'last_name' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ])->passes();
    }
}
