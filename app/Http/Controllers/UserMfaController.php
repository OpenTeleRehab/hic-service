<?php

namespace App\Http\Controllers;

use App\Helpers\KeycloakHelper;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class UserMfaController extends Controller
{
    public function resetMfaOtp(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $success = KeycloakHelper::deleteUserCredentialByType($user->email, 'otp');

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'mfa.reset.success',
                ];
            }

            return [
                'success' => false,
                'message' => 'mfa.reset.failed',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
