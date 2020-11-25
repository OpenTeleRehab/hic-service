<?php

namespace App\Http\Controllers;

use App\Helpers\KeycloakHelper;
use App\Models\User;
use Illuminate\Http\Request;

define("KEYCLOAK_USERS", env('KEYCLOAK_URL') . '/auth/admin/realms/' . env('KEYCLOAK_REAMLS_NAME') . '/users');

class ProfileController extends Controller
{
    /**
     * @param string $username
     * @param \Illuminate\Http\Request $request
     *
     * @return array|bool[]
     */
    public function updatePassword($username, Request $request)
    {
        $password = $request->get('current_password');
        $userResponse = KeycloakHelper::getLoginUser($username, $password);
        if ($userResponse->successful()) {
            // TODO: use own user token.
            $token = KeycloakHelper::getKeycloakAccessToken();
            $userUrl = KEYCLOAK_USERS . '/' . $request->get('user_id');
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
     * @param int $id
     *
     * @return array
     */
    public function updateUserProfile(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $data = $request->all();
            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'gender' => $data['gender'],
                'country_id' => $data['country_id'],
                'clinic_id' => $data['clinic_id'],
            ]);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'message' => 'success_message.profile_update'];
    }
}
