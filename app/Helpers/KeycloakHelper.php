<?php

namespace App\Helpers;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

define("KEYCLOAK_TOKEN_URL", env('KEYCLOAK_URL') . '/auth/realms/' . env('KEYCLOAK_REAMLS_NAME') . '/protocol/openid-connect/token');
define("KEYCLOAK_USER_URL", env('KEYCLOAK_URL') . '/auth/admin/realms/' . env('KEYCLOAK_REAMLS_NAME') . '/users');
define("KEYCLOAK_WE_USER_URL", env('KEYCLOAK_URL') . '/auth/realms/' . env('KEYCLOAK_REAMLS_NAME') . '/userapi-rest/users');
define("KEYCLOAK_GROUPS_URL", env('KEYCLOAK_URL') . '/auth/admin/realms/' . env('KEYCLOAK_REAMLS_NAME') . '/groups');
define("KEYCLOAK_EXECUTE_EMAIL", '/execute-actions-email?client_id=' . env('KEYCLOAK_BACKEND_CLIENT') . '&redirect_uri=' . env('REACT_APP_BASE_URL'));

define("GADMIN_KEYCLOAK_TOKEN_URL", env('KEYCLOAK_URL') . '/auth/realms/' . env('GADMIN_KEYCLOAK_REAMLS_NAME') . '/protocol/openid-connect/token');

/**
 * Class KeycloakHelper
 * @package App\Helpers
 */
class KeycloakHelper
{
    const GADMIN_ACCESS_TOKEN = 'gadmin_access_token';

    /**
     * @return mixed|null
     */
    public static function getKeycloakAccessToken()
    {
        $response = Http::asForm()->post(KEYCLOAK_TOKEN_URL, [
            'grant_type' => 'password',
            'client_id' => env('KEYCLOAK_BACKEND_CLIENT'),
            'client_secret' => env('KEYCLOAK_BACKEND_SECRET'),
            'username' => env('KEYCLOAK_BACKEND_USERNAME'),
            'password' => env('KEYCLOAK_BACKEND_PASSWORD')
        ]);

        if ($response->successful()) {
            $result = $response->json();
            return $result['access_token'];
        }
        return null;
    }

    /**
     * @return mixed|null
     */
    public static function getGAdminKeycloakAccessToken()
    {
        $access_token = Cache::get(self::GADMIN_ACCESS_TOKEN);

        if ($access_token) {
            $token_arr = explode('.', $access_token);
            $token_obj = json_decode(JWT::urlsafeB64Decode($token_arr[1]), true);
            $token_exp_at = $token_obj['exp'];
            $current_timestamp = Carbon::now()->timestamp;
            if ($current_timestamp > $token_exp_at) {
                return self::generateKeycloakToken(KEYCLOAK_TOKEN_URL, env('KEYCLOAK_BACKEND_SECRET'), self::GADMIN_ACCESS_TOKEN);
            }
        }

        return self::generateKeycloakToken(KEYCLOAK_TOKEN_URL, env('KEYCLOAK_BACKEND_SECRET'), self::GADMIN_ACCESS_TOKEN);
    }

    /**
     * @param string $url
     * @param string $client_secret
     *
     * @return void
     */
    private static function generateKeycloakToken($url, $client_secret, $cache_key)
    {
        $response = Http::asForm()->post(GADMIN_KEYCLOAK_TOKEN_URL, [
            'grant_type' => 'password',
            'client_id' => env('KEYCLOAK_BACKEND_CLIENT'),
            'client_secret' => env('GADMIN_KEYCLOAK_BACKEND_SECRET'),
            'username' => env('KEYCLOAK_BACKEND_USERNAME'),
            'password' => env('KEYCLOAK_BACKEND_PASSWORD')
        ]);

        if ($response->successful()) {
            $result = $response->json();

            Cache::forever($cache_key, $result['access_token']);

            return $result['access_token'];
        }

        return null;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return \Illuminate\Http\Client\Response
     */
    public static function getLoginUser($username, $password)
    {
        return Http::asForm()->post(KEYCLOAK_TOKEN_URL, [
            'grant_type' => 'password',
            'client_id' => env('KEYCLOAK_BACKEND_CLIENT'),
            'client_secret' => env('KEYCLOAK_BACKEND_SECRET'),
            'username' => $username,
            'password' => $password,
        ]);
    }

    /**
     * @param string $token
     * @param string $url
     * @param string $password
     * @param bool $isTemporary
     *
     * @return bool
     */
    public static function resetUserPassword($token, $url, $password, $isTemporary = true)
    {
        $response = Http::withToken($token)->put($url . '/reset-password', [
            'value' => $password,
            'type' => 'password',
            'temporary' => $isTemporary
        ]);
        if ($response->successful()) {
            return true;
        }
        return false;
    }

    /**
     * @param string $token
     * @param string $url
     *
     * @return bool
     */
    public static function deleteUser($token, $url)
    {
        $response = Http::withToken($token)->delete($url);

        if ($response->successful()) {
            return true;
        }

        return false;
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    public static function hasRealmRole($role)
    {
        $decodedToken = json_decode(Auth::token(), true);
        $authRoles = $decodedToken['realm_access']['roles'];
        if (in_array($role, $authRoles)) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public static function getUserUuid()
    {
        $decodedToken = json_decode(Auth::token(), true);
        return $decodedToken['sub'];
    }

    /**
     * @return array
     */
    public static function getUserGroup()
    {
        $authUser = Auth::user();
        $userGroupUrl = KEYCLOAK_USER_URL . '/' . $authUser->keycloak_user_uuid . '/groups';
        $token = self::getKeycloakAccessToken();
        $response = Http::withToken($token)->get($userGroupUrl);
        $userGroups = [];
        if ($response->successful()) {
            $groups = $response->json();
            foreach ($groups as $group) {
                array_push($userGroups, $group['name']);
            }
        }

        return $userGroups;
    }

    /**
     * @param string $token
     *
     * @return array
     */
    public static function getUserGroups($token)
    {
        $response = Http::withToken($token)->get(KEYCLOAK_GROUPS_URL);
        $userGroups = [];
        if ($response->successful()) {
            $groups = $response->json();
            foreach ($groups as $group) {
                $userGroups[$group['name']] = $group['id'];
            }
        }

        return $userGroups;
    }

    /**
     * @param \App\Models\User $user
     * @param string $password
     * @param bool $isTemporaryPassword
     * @param string $userGroup
     *
     * @return false|mixed|string
     * @throws \Exception
     */
    public static function createUser($user, $password, $isTemporaryPassword, $userGroup)
    {
        $token = self::getKeycloakAccessToken();
        if ($token) {
            try {
                $response = Http::withToken($token)->withHeaders([
                    'Content-Type' => 'application/json'
                ])->post(KEYCLOAK_USER_URL, [
                    'username' => $user->email,
                    'email' => $user->email,
                    'enabled' => true,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
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
                        self::sendEmailToNewUser($userKeycloakUuid);
                        return $userKeycloakUuid;
                    }
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        throw new \Exception('no_token');
    }

    /**
     * @param string $token
     * @param string $userUrl
     * @param string $userGroup
     * @param false $isUnassigned
     *
     * @return bool
     */
    public static function assignUserToGroup($token, $userUrl, $userGroup, $isUnassigned = false)
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

    /**
     * @param string $userId
     *
     * @return \Illuminate\Http\Client\Response
     */
    public static function sendEmailToNewUser($userId)
    {
        $token = KeycloakHelper::getKeycloakAccessToken();
        $url = KEYCLOAK_USER_URL . '/'. $userId . KEYCLOAK_EXECUTE_EMAIL;
        $response = Http::withToken($token)->put($url, ['UPDATE_PASSWORD']);

        return $response;
    }

    /**
     * @param string $userId
     *
     * @return \Illuminate\Http\Client\Response
     */
    public static function sendForgotPasswordEmailToUser($userId)
    {
        $token = KeycloakHelper::getKeycloakAccessToken();
        $url = KEYCLOAK_WE_USER_URL . '/'. $userId . KEYCLOAK_EXECUTE_EMAIL;
        $response = Http::withToken($token)->put($url, ['UPDATE_PASSWORD']);

        return $response;
    }
}
