<?php

namespace App\Http\Controllers;

use App\Events\ApplyNewLanguageTranslationEvent;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Spec as OA;

class LanguageController extends Controller
{
    /**
     * @param Request $request
     *
     * @return array
     */
    #[OA\Operation\Get(
        path: '/api/language',
        tags: ['Language'],
        summary: 'Lists all languages',
        operationId: 'languageList',
        security: [new OA\Security\Requirement(scheme: 'oauth2_security')],
        responses: [
            new OA\Response(response: '200', description: 'successful operation'),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 404, description: 'Resource Not Found'),
            new OA\Response(response: 401, description: 'Authentication is required'),
        ],
    )]
    public function index(Request $request)
    {
        $data = $request->all();
        $query = Language::select('languages.*');
        if (isset($data['search_value'])) {
            $query->where(function ($query) use ($data) {
                $query->where('name', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('code', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('id', $data['search_value']);
            });
        }

        if (isset($data['filters'])) {
            $filters = $request->input('filters');
            $query->where(function ($query) use ($filters) {
                foreach ($filters as $filter) {
                    $filterObj = json_decode($filter);
                    if ($filterObj->columnName === 'id') {
                        $query->where('id', $filterObj->value);
                    } else {
                        $query->where($filterObj->columnName, 'LIKE', '%' . strtolower($filterObj->value) . '%');
                    }
                }
            });
        }

        $info = [];
        if (isset($data['page_size'])) {
            $languages = $query->paginate($data['page_size']);
            $info = [
                'current_page' => $languages->currentPage(),
                'total_count' => $languages->total(),
            ];
        } else {
            $languages = $query->get();
        }

        return [
            'success' => true,
            'data' => LanguageResource::collection($languages),
            'info' => $info,
        ];
    }

    /**
     * @param Request $request
     *
     * @return array|void
     */
    #[OA\Operation\Post(
        path: '/api/language',
        tags: ['Language'],
        summary: 'Create language',
        operationId: 'createlanguage',
        security: [new OA\Security\Requirement(scheme: 'oauth2_security')],
        parameters: [
            new OA\Parameter(
                name: 'name',
                in: 'query',
                description: 'Name',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'code',
                in: 'query',
                description: 'Code',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'rtl',
                in: 'query',
                description: 'RTL',
                required: false,
                schema: new OA\Schema(type: 'boolean'),
            ),
        ],
        responses: [
            new OA\Response(response: '200', description: 'successful operation'),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 404, description: 'Resource Not Found'),
            new OA\Response(response: 401, description: 'Authentication is required'),
        ],
    )]
    public function store(Request $request)
    {
        $code = $request->input('code');
        $rtl = $request->boolean('rtl');
        $availableLanguage = Language::where('code', $code)->count();
        if ($availableLanguage) {
            return abort(409, 'error_message.language_exists');
        }

        Language::create([
            'name' => $request->input('name'),
            'code' => $code,
            'rtl' => $rtl,
        ]);

        event(new ApplyNewLanguageTranslationEvent($code));

        return ['success' => true, 'message' => 'success_message.language_add'];
    }

    /**
     * @param Request $request
     * @param Language $language
     *
     * @return array|void
     */
    #[OA\Operation\Put(
        path: '/api/language/{id}',
        tags: ['Language'],
        summary: 'Update language',
        operationId: 'updateLanguage',
        security: [new OA\Security\Requirement(scheme: 'oauth2_security')],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Language id',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'name',
                in: 'query',
                description: 'Name',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'code',
                in: 'query',
                description: 'Code',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'rtl',
                in: 'query',
                description: 'RTL',
                required: false,
                schema: new OA\Schema(type: 'boolean'),
            ),
        ],
        responses: [
            new OA\Response(response: '200', description: 'successful operation'),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 404, description: 'Resource Not Found'),
            new OA\Response(response: 401, description: 'Authentication is required'),
        ],
    )]
    public function update(Request $request, Language $language)
    {
        $code = $request->input('code');
        $rtl = $request->boolean('rtl');
        $availableLanguage = Language::where('id', '<>', $language->id)
            ->where('code', $code)
            ->count();
        if ($availableLanguage) {
            return abort(409, 'error_message.language_exists');
        }

        $language->update([
            'name' => $request->input('name'),
            'code' => $code,
            'rtl' => $rtl,
        ]);

        return ['success' => true, 'message' => 'success_message.language_update'];
    }

    /**
     * @param Language $language
     *
     * @return array
     * @throws Exception
     */
    #[OA\Operation\Delete(
        path: '/api/language/{id}',
        tags: ['Language'],
        summary: 'Delete language',
        operationId: 'deleteLanguage',
        security: [new OA\Security\Requirement(scheme: 'oauth2_security')],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Language id',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: '200', description: 'successful operation'),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 404, description: 'Resource Not Found'),
            new OA\Response(response: 401, description: 'Authentication is required'),
        ],
    )]
    public function destroy(Language $language)
    {
        if (!$language->isUsed()) {
            $language->delete();
            return ['success' => true, 'message' => 'success_message.language_delete'];
        }
        return ['success' => false, 'message' => 'error_message.language_delete'];
    }
}
