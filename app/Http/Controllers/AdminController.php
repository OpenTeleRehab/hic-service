<?php

namespace App\Http\Controllers;

use App\Helpers\KeycloakHelper;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

define("KEYCLOAK_USERS", env('KEYCLOAK_URL') . '/auth/admin/realms/' . env('KEYCLOAK_REAMLS_NAME') . '/users');

class AdminController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $data = $request->all();
        $query = User::select('users.*')
            ->leftJoin('countries', 'countries.id', 'users.country_id')
            ->leftJoin('clinics', 'clinics.id', 'users.clinic_id')
            ->where('type', $data['admin_type'])
            ->where(function ($query) use ($data) {
                $query->where('first_name', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('last_name', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('email', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('countries.name', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('clinics.name', 'like', '%' . $data['search_value'] . '%');
            });

        if (isset($data['filters'])) {
            $filters = $request->get('filters');
            $query->where(function ($query) use ($filters) {
                foreach ($filters as $filter) {
                    $filterObj = json_decode($filter);
                    if ($filterObj->columnName === 'status') {
                        $query->where('enabled', $filterObj->value);
                    } elseif ($filterObj->columnName === 'country') {
                        $query->where('countries.name', $filterObj->value);
                    } elseif ($filterObj->columnName === 'clinic') {
                        $query->where('clinics.name', $filterObj->value);
                    } elseif ($filterObj->columnName === 'last_login') {
                        $dates = explode(' - ', $filterObj->value);
                        $startDate = date_create_from_format('d/m/Y', $dates[0]);
                        $endDate = date_create_from_format('d/m/Y', $dates[1]);
                        $startDate->format('Y-m-d');
                        $endDate->format('Y-m-d');
                        $query->where('created_at', '>=', $startDate)
                            ->where('created_at', '<=', $endDate);
                    } else {
                        $query->where($filterObj->columnName, 'like', '%' .  $filterObj->value . '%');
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
     * @param \Illuminate\Http\Request $request
     *
     * @return array|void
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        $firstName = $request->get('first_name');
        $lastName = $request->get('last_name');
        $type = $request->get('type');
        $countryId = $request->get('country_id');
        $clinicId = $request->get('clinic_id');
        $email = $request->get('email');

        $availableEmail = User::where('email', $email)->count();
        if ($availableEmail) {
            // Todo: message will be replaced.
            return abort(409, 'error_message.email_exists');
        }
        try {
            $user = User::create([
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'type' => $type,
                'country_id' => $countryId,
                'clinic_id' => $clinicId,
            ]);

            // Create keycloak user.
            $keycloakUserUuid = self::createKeycloakUser($user, $email, true, $type);

            if (!$user || !$keycloakUserUuid) {
                DB::rollBack();
                return abort(500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }

        DB::commit();
        return ['success' => true, 'message' => 'success_message.user_add'];
    }

    /**
     * @param \Illuminate\Http\Request $request
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
            ]);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'message' => 'success_message.user_update'];
    }

    /**
     * @param \App\Models\User $user
     * @param string $password
     * @param bool $isTemporaryPassword
     * @param string $userGroup
     *
     * @return false|mixed|string
     */
    private static function createKeycloakUser($user, $password, $isTemporaryPassword, $userGroup)
    {
        $token = KeycloakHelper::getKeycloakAccessToken();
        if ($token) {
            $response = Http::withToken($token)->withHeaders([
                'Content-Type' => 'application/json'
            ])->post(KEYCLOAK_USERS, [
                'username' => $user->email,
                'email' => $user->email,
                'enabled' => true,
            ]);

            if ($response->successful()) {
                $createdUserUrl = $response->header('Location');
                $lintArray = explode('/', $createdUserUrl);
                $userKeycloakUuid = end($lintArray);
                $isCanSetPassword = true;
                if ($password) {
                    $isCanSetPassword = KeycloakHelper::resetUserPassword(
                        $token,
                        $createdUserUrl,
                        $password,
                        $isTemporaryPassword
                    );
                }
                $isCanAssignUserToGroup = self::assignUserToGroup($token, $createdUserUrl, $userGroup);
                if ($isCanSetPassword && $isCanAssignUserToGroup) {
                    return $userKeycloakUuid;
                }
            }
        }
        return false;
    }

    /**
     * @param string $token
     * @param string $userUrl
     * @param string $userGroup
     * @param false $isUnassigned
     *
     * @return bool
     */
    private static function assignUserToGroup($token, $userUrl, $userGroup, $isUnassigned = false)
    {
        $userGroups = KeycloakHelper::getUserGroups($token);
        $url = $userUrl . '/groups/' . $userGroups[$userGroup];
        if ($isUnassigned) {
            $response = Http::withToken($token)->delete($url);
        } else {
            $response = Http::withToken($token)->put($url);
        }
        if ($response->successful()) {
            return true;
        }
        return false;
    }
}
