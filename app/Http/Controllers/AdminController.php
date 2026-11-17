<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\KeycloakHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use OpenApi\Spec as OA;

define("KEYCLOAK_USERS", env('KEYCLOAK_URL') . '/auth/admin/realms/' . env('KEYCLOAK_REAMLS_NAME') . '/users');

class AdminController extends Controller
{
    /**
     * @param Request $request
     *
     * @return array
     */
    #[OA\Operation\Get(
        path: '/api/admin',
        tags: ['Admin'],
        summary: 'Lists all users',
        operationId: 'userList',
        security: [new OA\Security\Requirement(scheme: 'oauth2_security')],
        parameters: [
            new OA\Parameter(
                name: 'search_value',
                in: 'query',
                description: 'Search value',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'admin_type',
                in: 'query',
                description: 'Amin type',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'page_size',
                in: 'query',
                description: 'Limit',
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
    public function index(Request $request)
    {
        $data = $request->all();
        $query = User::select('users.*');
        if (isset($data['search_value'])) {
            $query->where(function ($query) use ($data) {
                $query->where('first_name', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('last_name', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('email', 'like', '%' . $data['search_value'] . '%');
            });
        }

        if (isset($data['filters'])) {
            $filters = $request->input('filters');
            $query->where(function ($query) use ($filters) {
                foreach ($filters as $filter) {
                    $filterObj = json_decode($filter);
                    if ($filterObj->columnName === 'user_status') {
                        $query->where('enabled', $filterObj->value);
                    } elseif ($filterObj->columnName === 'last_login') {
                        $dates = explode(' - ', $filterObj->value);
                        $startDate = date_create_from_format('d/m/Y', $dates[0]);
                        $endDate = date_create_from_format('d/m/Y', $dates[1]);
                        $startDate->format('Y-m-d');
                        $endDate->format('Y-m-d');
                        $query->whereDate('last_login', '>=', $startDate)
                            ->whereDate('last_login', '<=', $endDate);
                    } elseif ($filterObj->columnName === 'gender') {
                        $query->where('gender', $filterObj->value);
                    } else {
                        $query->where($filterObj->columnName, 'like', '%' . $filterObj->value . '%');
                    }
                }
            });
        }

        $users = $query->paginate($data['page_size']);
        $info = [
            'current_page' => $users->currentPage(),
            'total_count' => $users->total(),
        ];
        return ['success' => true, 'data' => UserResource::collection($users), 'info' => $info];
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getReviewer(Request $request)
    {
        $users = User::where('enabled', 1)->get();

        return ['success' => true, 'data' => UserResource::collection($users)];
    }

    /**
     * @param Request $request
     *
     * @return array|void
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        $firstName = $request->input('first_name');
        $lastName = $request->input('last_name');
        $type = $request->input('type');
        $email = $request->input('email');
        $gender = $request->input('gender');

        $availableEmail = User::where('email', $email)->count();
        if ($availableEmail) {
            return abort(409, 'error_message.email_exists');
        }

        $user = User::create([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'type' => $type,
            'gender' => $gender
        ]);

        if (!$user) {
            return ['success' => false, 'message' => 'error_message.user_add'];
        }

        try {
            KeycloakHelper::createUser($user, null, true, $type);
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }

        DB::commit();
        return ['success' => true, 'message' => 'success_message.user_add'];
    }

    /**
     * @param Request $request
     * @param int $id
     *
     * @return array
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $data = $request->all();
            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'type' => $data['type']
            ]);
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'message' => 'success_message.user_update'];
    }

    /**
     * @param Request $request
     * @param User $user
     * @return array
     */
    public function updateStatus(Request $request, User $user)
    {
        try {
            $enabled = $request->boolean('enabled');
            $token = KeycloakHelper::getKeycloakAccessToken();
            $userUrl = KEYCLOAK_USERS . '?email=' . $user->email;
            $user->update(['enabled' => $enabled]);

            $response = Http::withToken($token)->get($userUrl);
            $keyCloakUsers = $response->json();
            $url = KEYCLOAK_USERS . '/' . $keyCloakUsers[0]['id'];

            $userUpdated = Http::withToken($token)
                ->put($url, ['enabled' => $enabled]);

            if ($userUpdated) {
                return ['success' => true, 'message' => 'success_message.user_update'];
            }
            return ['success' => false, 'message' => 'error_message.user_update'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param integer $id
     *
     * @return false|mixed|string
     * @throws Exception
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $token = KeycloakHelper::getKeycloakAccessToken();

            $userUrl = KEYCLOAK_USERS . '?email=' . $user->email;
            $response = Http::withToken($token)->get($userUrl);

            if ($response->successful()) {
                $keyCloakUsers = $response->json();

                $isDeleted = KeycloakHelper::deleteUser($token, KEYCLOAK_USERS . '/' . $keyCloakUsers[0]['id']);
                if ($isDeleted) {
                    $user->delete();
                    return ['success' => true, 'message' => 'success_message.user_delete'];
                }
            }
            return ['success' => false, 'message' => 'error_message.user_delete'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param User $user
     * @return array
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function resendEmailToUser(User $user)
    {
        $federatedDomains = array_map(fn($d) => strtolower(trim($d)), explode(',', env('FEDERATED_DOMAINS', '')));
        $email = strtolower($user->email);

        if (Str::endsWith($email, $federatedDomains)) {
            return ['success' => false, 'message' => 'error_message.cannot_resend_email'];
        }

        $token = KeycloakHelper::getKeycloakAccessToken();

        $response = Http::withToken($token)->withHeaders([
            'Content-Type' => 'application/json'
        ])->get(KEYCLOAK_USERS, [
            'username' => $user->email,
        ]);

        if ($response->successful()) {
            $userUid = $response->json()[0]['id'];
            if (KeycloakHelper::sendEmailToNewUser($userUid)->successful()) {
                return ['success' => true, 'message' => 'success_message.resend_email'];
            }
        }

        return ['success' => false, 'message' => 'error_message.cannot_resend_email'];
    }
}
