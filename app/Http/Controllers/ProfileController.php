<?php

namespace App\Http\Controllers;

use App\Helpers\KeycloakHelper;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

define("KEYCLOAK_USERS", env('KEYCLOAK_URL') . '/auth/admin/realms/' . env('KEYCLOAK_REAMLS_NAME') . '/users');

class ProfileController extends Controller
{
    /**
     * @return \App\Http\Resources\UserResource
     */
    public function getUserProfile()
    {
        $user = Auth::user();
        // Update enabled to true when first login.
        if (!$user->last_login) {
            $user->update([
                'last_login' => now(),
                'enabled' => true
            ]);
        }

        return new UserResource($user);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array|bool[]
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        $password = $request->get('current_password');
        $userResponse = KeycloakHelper::getLoginUser($user->email, $password);
        if ($userResponse->successful()) {
            // TODO: use own user token.
            $token = KeycloakHelper::getKeycloakAccessToken();
            $userUrl = KEYCLOAK_USERS . '/' .  KeycloakHelper::getUserUuid();
            $newPassword = $request->get('new_password');
            $isCanSetPassword = KeycloakHelper::resetUserPassword(
                $token,
                $userUrl,
                $newPassword,
                false
            );

            if ($isCanSetPassword) {
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'error_message.cannot_change_password'];
        }

        return ['success' => false, 'message' => 'error_message.wrong_password'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function updateUserProfile(Request $request)
    {
        try {
            $user = Auth::user();
            $data = $request->all();
            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'language_id' => $data['language_id']
            ]);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'message' => 'success_message.profile_update'];
    }

    /**
     * @return array
     */
    public function updateLastAccess()
    {
        try {
            $user = Auth::user();
            $user->update([
                'last_login' => now(),
                'enabled' => true,
            ]);
            return ['success' => true, 'message' => 'Successful'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
