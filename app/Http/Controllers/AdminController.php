<?php

namespace App\Http\Controllers;

use App\Helpers\KeycloakHelper;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

define("KEYCLOAK_USERS", env('KEYCLOAK_URL') . '/auth/admin/realms/hi/users');

class AdminController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    public function index(Request $request)
    {
        $type = $request->get('admin_type');
        $users = User::where('type', $type)->get();

        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        $keycloakUserUuid = null;

        try {
            $firstName = $request->get('first_name');
            $lastName = $request->get('last_name');
            $type = $request->get('type');
            $countryId = $request->get('country_id');
            $hospitalId = $request->get('hospital_id');
            $email = $request->get('email');

            $availableEmail = User::where('email', $email)->count();
            if ($availableEmail) {
                return abort(409);
            }

            $user = User::create([
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'type' => $type,
                'country_id' => $countryId,
                'hospital_id' => $hospitalId,
            ]);

            //create keycloak user
            $keycloakUserUuid = self::createKeycloakUser($user, $email, true, 'a874c900-56b5-43c9-84cc-e2232d01accf');

            if (!$user || !$keycloakUserUuid) {
                DB::rollBack();
                return abort(500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return ['error message' => $e->getMessage()];
        }

        DB::commit();
        return ['user' => $user];
    }

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

    private static function assignUserToGroup($token, $userUrl, $userGroup, $isUnassigned = false)
    {
        $url = $userUrl . '/groups/' . $userGroup;
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
