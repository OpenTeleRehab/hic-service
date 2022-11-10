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

/**
 * @OA\Info(title="OpentRehab Admin API", version="0.1")
 *
 * Class Controller
 * @package App\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Instantiate a new Controller instance.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $languageId = $request->get('lang');
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
}
